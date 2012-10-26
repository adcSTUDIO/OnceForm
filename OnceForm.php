<?php
include 'validators.php';

/*
The OnceForm - Write once HTML5 forms processing for PHP.

Copyright (C) 2012  adcSTUDIO LLC

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

/**
 * The OnceForm Write your form once, wrap it in a function.
 * Pass that function name to a new OnceForm and it wires up the
 * request validation!
 *
 * @author Kevin Newman <Kevin@adcSTUDIO.com>
 * @package The OnceForm
 * @copyright (C) 2012 adcSTUDIO LLC
 * @license GNU/GPL, see license.txt
 */
class OnceForm
{
	public $isRequest = false;
	public $isValid = false;
	public $validators = array();
	public $data = array();
	
	public $form;
	public $doc;
	
	protected $user_validator;
	
	public function __toString() {
		return $this->doc->saveHTML( $this->form );
	}

	public function toString() {
		return $this->__toString();
	}
	
	/**
	 * Creates a OnceForm.
	 * 
	 * @param function $form_func A function set up to output (echo) an 
	 * HTML5 to the user. This function's output will be captured to an
	 * output buffer. Note: If a form func is passed to the constructor,
	 * the OnceForm will automatically check and validate the request.
	 */
	public function __construct( $form_func = NULL, $validator = NULL )
	{
		if ( !is_null( $form_func ) )
		{
			if ( is_callable( $form_func ) )
				$this->add_form_func( $form_func );
			elseif ( is_string( $form_func ) )
				$this->parse_form( $form_func );
			
			$this->user_validator = $validator;
			
			// get the request data
			$data = $this->get_request();
			
			// verify, and set this new data
			$this->resolve_request( $data );
			
			if ( $this->isRequest )
				$this->isValid = $this->validate();
		}
	}
	
	/**
	 * Add a form function to the OnceForm.
	 * 
	 * @param function $func A function setup to output (echo) an 
	 * HTML5 to the user. This function's output will be captured to an
	 * output buffer.
	 */
	public function add_form_func( $func )
	{
		ob_start();
		call_user_func( $func );
		
		$this->parse_form( ob_get_clean() );
	}
	
	/**
	 * Set the form to process from an HTML string.
	 *
	 * @param string $html The html5 form to be automatically processed.
	 */
	public function parse_form( $html )
	{
		$encoding = mb_detect_encoding( $html );
		$this->doc = new DOMDocument( '', $encoding );

		// Make DOMDocument use the right encoding.
		$this->doc->loadHTML( '<html><head>
		<meta http-equiv="content-type" content="text/html; charset='.$encoding.'">
		</head><body>' . trim( $html ) . '</body></html>' );
		
		$body = $this->doc->getElementsByTagName( 'body' );
		
		$this->form = $body->item( 0 )->firstChild;
	}
	
	/**
	 * Checks the request object, to see if a request has been made, and sets
	 * the form elements' value props to the submitted data. If a form func
	 * was passed to the constructor, this will be automatically called on
	 * construction. Otherwise, it must be called manually.
	 */
	public function get_request()
	{
		$form = $this->form;
		
		if ( 'POST' == strtoupper( $form->getAttribute('method') ) )
			$data = $_POST;
		else
			// the default `method` if none specified is GET
			$data = $_GET;
		
		if ( !empty( $data ) )
			$this->isRequest = true;
		
		return $data;
	}
	
	/**
	 * Resolves the request data, either sets the form elements, or gets defaults.
	 * This was a specifically divided from get_request to allow subclasses to
	 * filter the request data (such as removing slashes in WordPress).
	 * @param $data A reference to the array of request data.
	 */
	public function resolve_request( $data )
	{
		// If the $data array is empty, nothing was sent to the server,
		// so we aren't doing a postback.
		if ( empty( $data ) )
			$this->data = $this->get_default_data();
		else
			// This checks the form values have return values, and polyfills if not.
			$this->data = $this->set_request_data( $data );
	}
	
	/**
	 * Builds (polyfills) the $data property. Useful when you want to get
	 * the default data when there has been no reqeust.
	 */
	protected function get_default_data()
	{
		$form = $this->form;
		
		$data = array();
		
		$inputs = $form->getElementsByTagName('input');
		foreach ( $inputs as $input )
		{
			switch( $input->getAttribute('type') )
			{
				case 'submit':
				break;
				case 'email':
				case 'hidden':
				case 'text':
					$data[ $input->getAttribute('name') ] = $input->getAttribute('value');
				break;
				case 'radio':
				case 'checkbox':
					// :TODO:
				break;
			}
		}

		$textareas = $form->getElementsByTagName( 'textarea' );
		foreach ( $textareas as $textarea )
		{
			$data[ $textarea->getAttribute('name') ] =
				( $value = $textarea->nodeValue ) ?
					$value : '';
		}
		
		$selects = $form->getElementsByTagName( 'select' );
		foreach( $selects as $select )
		{
			$name = $select->getAttribute('name');
			$options = $select->getElementsByTagName( "option" );
			
			// in case nothing is marked selected by default
			if ( $options->length > 0 )
			{
				$option = $options->item( 0 );
				if ( $option->hasAttribute('value') )
					$data[ $name ] = $option->getAttribute('value');
				else
					$data[ $name ] = $option->nodeValue;
			}
			else {
				$data[ $name ] = '';
			}

			// search for default selected
			foreach( $options as $option )
			{
				// unset the default
				if ( $option->hasAttribute( 'selected' ) )
				{
					// get the value - it's either the value prop, or the innertext.
					if ( $option->hasAttribute('value') )
						$data[ $name ] = $option->getAttribute('value');
					else
						$data[ $name ] = $option->nodeValue;
				}
			}
		}

		return $data;
	}
	
	/**
	 * Normalizes the request data (polyfills anything missing) and 
	 * sets the form value props to the request data.
	 * @param the request dat to filter.
	 */
	public function set_request_data( array $data )
	{
		$form = $this->form;
		
		// First get the default data.
		// :TODO: Optimize this - we really only need a list of items with name attribute
		$default_data = $this->get_default_data();
		
		// If the $data array is empty, nothing was sent to the server,
		// so we aren't doing a postback. 
		if ( !empty( $data ) )
		{
			foreach( $data as $key => $value)
			{
				if ( !array_key_exists( $key, $default_data ) )
					unset( $data[$key] );
			}

			// Mix the request data with the default data, and kill extra keys.
			$data = array_merge( $default_data, $data );
		}
		
		// get inputs
		$inputs = array();
		$nodes = $form->getElementsByTagName('input');
		foreach( $nodes as $node ) {
			if ( $node->hasAttribute( 'name' ) && $node->getAttribute( 'name' ) )
				$inputs[] = $node;
		}
		$this->set_inputs( $inputs, $data );
		
		$selects = array();
		$nodes = $form->getElementsByTagName('select');
		foreach( $nodes as $node ) {
			if ( $node->hasAttribute( 'name' ) && $node->getAttribute( 'name' ) )
				$selects[] = $node;
		}
		$this->set_selects( $selects, $data );

		$textareas = array();
		$nodes = $form->getElementsByTagName('textarea');
		foreach( $nodes as $node ) {
			if ( $node->hasAttribute( 'name' ) && $node->getAttribute( 'name' ) )
				$textareas[] = $node;
		}
		$this->set_textareas( $textareas, $data );

		return $data;
	}
	
	private function set_inputs( array &$inputs, array &$data )
	{
		// Every field might be skipped for various reasons
		// so we'll normalize the request data.
		foreach( $inputs as $input )
		{
			$name = $input->getAttribute('name');
			$type = $input->getAttribute('type');

			switch( $type )
			{
				// special exit for items that shouldn't be set, like submit
				case 'submit':
				break;
				
				// These are a bit special, they may need the checked prop added
				case 'radio':
				case 'checkbox':
					if ( isset( $data[ $name ] ) )
					{
						// if the request data contains the element name
						// and is a checkbox, then it's checked
						if ( 'checkbox' == $type ) {
							$input->setAttribute( 'checked', 'checked' );
						}
						// radios are only marked "checked" if the value matches
						else if ( 'radio' == $type && $input->getAttribute('value') == $data[ $name ] )
							$input->setAttribute( 'checked', 'checked' );
					}
					else {
						// poly fill to prevent php errors
						$data[ $name ] = '';
						
						// the box is unchecked
						$input->removeAttribute( 'checked' );
					}
					
					// :TODO: Figure out if more polyfilling is required for sets
					
				break;
				
				// These may be blank if the disabled flag is set
				case 'email':
				case 'text':
				case 'hidden':
				default:
					// set value prop to request value
					if ( isset( $data[ $name] ) )
						$input->setAttribute('value', $data[ $name ] );
					// or set request value to elem.value prop
					else if ( $input->hasAttribute('value') )
						$data[ $name ] = $input->getAttribute('value');
					// or default to empty
					else
						$data[ $name ] = '';
				break;
			}
		}
	}
	
	private function set_selects( array &$selects, array &$data )
	{
		// sets select box defaults to submitted values
		// This has the side effect of sanitizing the input data against the 
		// specified options list.
		foreach( $selects as $select )
		{
			$name = $select->getAttribute('name');

			$options = $select->getElementsByTagName( "option" );
			
			// if there is no sbumitted value, don't mess with the select box
			if ( !isset( $data[ $name ] ) )
				continue;
			
			// find the posted option, and unset the default
			foreach( $options as $option )
			{
				// unset the default
				if ( $option->hasAttribute( 'selected' ) )
					$option->removeAttribute( 'selected' );
				
				// get the value - it's either the value prop, or the innertext.
				if ( $option->hasAttribute('value') )
					$value = $option->getAttribute('value');
				else
					$value = $option->nodeValue;

				// set the new selected item
				if ( $value == $data[ $name ] )
					$option->setAttribute( 'selected', 'selected' );
			}
		}
	}
	
	private function set_textareas( array &$textareas, array &$data )
	{
		// sets default textarea content
		foreach( $textareas as $textarea )
		{
			$name = $textarea->getAttribute('name');

			if ( isset( $data[ $name ] ) )
			{
				// remove all child nodes (including text nodes)
				foreach ( $textarea->childNodes as $node )
					$textarea->removeChild( $node );
				
				// create and append a new text node
				$textarea->appendChild(
					$this->doc->createTextNode( $data[ $name ] )
				);
			}
			else if ( $value = $textarea->nodeValue )
				$data[ $name ] = $value;
			else
				$data[ $name ] = '';
		}
	}
	
	public function add_validator( $name, $validator ) {
		$this->validators[ $name ] = $validator;
	}
	
	public function set_required( $name, $required = true )
	{
		$form = $this->form;
		
		$xpath = new DOMXpath($this->doc);
		$elms = $xpath->query('//input[@name]|//select[@name]|//textarea[@name]');
		
		foreach ( $elms as $elm )
		{
			if ( $name != $elm->getAttribute( 'name' ) )
				continue;
			if ( $required )
				$elm->setAttribute( 'required', 'required' );
			else
				$elm->removeAttribute( 'required' );
		}
	}
	
	/**
	 * Validates the OnceForm against the data passed. This method will 
	 * create the validator objects and store them in $this->validators
	 * by name. To override a validator for a specific input element
	 * add it to $this->validators before calling validate().
	 *
	 * @param array $data The request data (or other) to validate 
	 * against the OnceForm.
	 * @return boolean Whether or not the data is valid by the 
	 * rules of the OnceForm.
	 */
	public function validate( $data = NULL )
	{
		if ( is_null( $data ) )
			$data = $this->data;
		
		$valid = true;
		
		if ( $this->user_validator )
		{
			$errors = call_user_func( $this->user_validator, $data, $this );
			
			$validator = (object)array(
				'errors' => $errors,
				'name' => 'user validator',
				'isValid' => false
			);
			
			if ( empty( $errors ) )
				$validator->isValid = true;
			else
				$valid = false;
			
			$this->validators[] = $validator;
		}
		
		$xpath = new DOMXpath($this->doc);
		
		if ( !$this->validate_inputs( $xpath->query('//input[@name]'), $data ) )
			$valid = false;
		
		if ( !$this->validate_selects( $xpath->query('//select[@name]'), $data ) )
			$valid = false;
		
		if ( !$this->validate_textareas( $xpath->query('//textarea[@name]'), $data ) )
			$valid = false;
		
		return $valid;	
	}
	
	private function validate_inputs( $inputs, $data )
	{
		$valid = true;
		
		foreach( $inputs as $input )
		{
			// Don't override provided validators
			if ( !isset( $this->validators[ $input->getAttribute('name') ] ) )
			{
				switch( $input->getAttribute('type') )
				{
					case 'email':
						$validator = new EmailValidator( $input );
					break;
					
					case 'radio':
					case 'checkbox':
					case 'text':
					case 'hidden':
					default:
						$validator = new InputValidator( $input );
					break;
				}
				
				$this->validators[ $input->getAttribute('name') ] = $validator;
			}
			else
			{
				$validator = $this->validators[ $input->getAttribute('name') ];
				$validator->setValue( $input );
			}
			
			// do the actual validation
			if ( !$validator->validate() )
				$valid = false;
		}
		
		return $valid;
	}
	
	private function validate_selects( $selects, $data )
	{
		$valid = true;
		
		foreach( $selects as $select )
		{
			if ( !isset( $this->validators[ $select->getAttribute('name') ] ) )
			{
				$validator = new SelectValidator( $select );
				
				$this->validators[ $select->getAttribute('name') ] = $validator;
			}
			else
			{
				$validator = $this->validators[ $select->getAttribute('name') ];
				$validator->setValue( $select );
			}
			
			if ( !$validator->validate() )
				$valid = false;
		}
		
		return $valid;
	}
	
	private function validate_textareas( $textareas, $data )
	{
		$valid = true;
		
		foreach( $textareas as $textarea )
		{
			if ( !isset( $this->validators[ $textarea->getAttribute('name') ] ) )
			{
				$validator = new TextareaValidator( $textarea );
				
				$this->validators[ $textarea->getAttribute('name') ] = $validator;
			}
			else
			{
				$validator = $this->validators[ $input->name ];
				$validator->setValue( $textarea );
			}
			
			if ( !$validator->validate() )
				$valid = false;
		}
		
		return $valid;
	}
	
	public function set_validator( /* callable */ $func )
	{
		$this->user_validator = $func;
	}
	
}
