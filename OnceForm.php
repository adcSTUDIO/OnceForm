<?php
include 'lib/ganon.php';
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
	
	protected $form;
	
	protected $user_validator;
	
	public function __toString() {
		$form = $this->form;
		return $form->toString();
	}
	
	/**
	 * Creates a OnceForm.
	 * 
	 * @param function $form_func A function set up to output (echo) an 
	 * HTML5 to the user. This function's output will be captured to an
	 * output buffer. Note: If a form func is passed to the constructor,
	 * the OnceForm will automatically check and validate the request.
	 */
	public function __construct( $form_func = NULL )
	{
		if ( !is_null( $form_func ) )
		{
			$this->add_form_func( $form_func );
			
			// get the request data
			$data = &$this->get_request();
			
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
		// Padding with div to workaround a limitation in ganon, where root element
		// attributes are not accessible.
		$parser = new HTML_Parser_HTML5( '<div>' . $html . '</div>' );
		
		$root = $parser->root;
		
		$forms = $root->select('form');
		
		$form = $this->form = $forms[0];
		
		// fixes HTML5 self closing issue with ganon.
		foreach( $form->select('*') as $elem ) {
			$elem->self_close_str="";
		}
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
		
		if ( 'POST' == strtoupper( $form->method ) )
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
	protected function resolve_request( &$data )
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
		
		$inputs = $form->select('input');
		
		$data = array();
		
		foreach ( $inputs as $input ) {
			switch( $input->type ) {
				default:
					$data[ $input->name ] = $input->value;
			}
		}
		
		return $data;
	}
	
	/**
	 * Normalizes the request data (polyfills anything missing) and 
	 * sets the form value props to the request data.
	 * @param the request dat to filter.
	 */
	public function set_request_data( $data )
	{
		$form = $this->form;
		
		$this->set_inputs( $form->select('input[name]'), $data );
		$this->set_selects( $form->select('select[name]'), $data );
		$this->set_textareas( $form->select('textarea[name]'), $data );
		
		return $data;
	}
	
	private function set_inputs( $inputs, $data )
	{
		// Every field might be skipped for various reasons
		// so we'll normalize the request data.
		foreach( $inputs as $input )
		{
			switch( $input->type )
			{
				// These are a bit special, they may need the checked prop added
				case 'radio':
				case 'checkbox':
					if ( isset( $data[ $input->name ] ) )
					{
						// if the request data contains the element name
						// and is a checkbox, then it's checked
						if ( $input->type == 'checkbox' ) {
							$input->addAttribute( 'checked', 'checked' );
						}
						// radios are only marked "checked" if the value matches
						else if (
							$input->type == 'radio' &&
							$input->value == $data[ $input->name ]
						)
							$input->addAttribute( 'checked', 'checked' );
					}
					else {
						// poly fill to prevent php errors
						$data[ $input->name ] = '';
						
						// the box is unchecked
						$input->deleteAttribute( 'checked' );
					}
					
					// :TODO: Figure out if more polyfilling is required for sets
					
				break;
				
				// These may be blank if the disabled flag is set
				case 'email':
				case 'text':
				case 'hidden':
				default:
					// set value prop to request value
					if ( isset( $data[ $input->name ] ) )
						$input->value = $data[ $input->name ];
					// or set request value to elem.value prop
					else if ( isset( $input->value ) )
						$data[ $input->name ] = $input->value;
					// or default to empty
					else
						$data[ $input->name ] = '';
				break;
			}
		}
	}
	
	private function set_selects( $selects, $data )
	{
		// sets select box defaults to submitted values
		// This has the side effect of sanitizing the input data against the 
		// specified options list.
		foreach( $selects as $select )
		{
			$options = $select->select( "option" );
			
			// if there is no sbumitted value, don't mess with the select box
			if ( !isset( $data[ $select->name ] ) )
				continue;
			
			// find the posted option, and unset the default
			foreach( $options as $option )
			{
				// unset the default
				if ( $option->hasAttribute( 'selected' ) )
					$option->deleteAttribute( 'selected' );
				
				// get the value - it's either the value prop, or the innertext.
				$value = $option->value;
				if ( is_null( $value ) )
					$value = $option->getInnerText();
				
				// set the new selected item
				if ( $value == $data[ $select->name ] )
					$option->addAttribute( 'selected', 'selected' );
				
			}
		}
	}
	
	private function set_textareas( $textareas, $data )
	{
		// sets default textarea content
		foreach( $textareas as $textarea )
		{
			if ( isset( $data[ $textarea->name ] ) )
				$textarea->setInnerText( $data[ $textarea->name ] );
			else if ( $value = $textarea->getInnerText() )
				$data[ $textarea->name ] = $value;
			else
				$data[ $textarea->name ] = '';
		}
	}
	
	public function add_validator( $name, $validator ) {
		$this->validators[ $name ] = $validator;
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
		
		$form = $this->form;
		
		$valid = true;
		
		if ( !$this->validate_inputs( $form->select('input[name]'), $data ) )
			$valid = false;
		
		if ( !$this->validate_selects( $form->select('select[name]'), $data ) )
			$valid = false;
		
		if ( !$this->validate_textareas( $form->select('textarea[name]'), $data ) )
			$valid = false;
		
		if ( $this->user_validator && !call_user_func( $this->user_validator ) )
			$valid = false;
		
		return $valid;	
	}
	
	private function validate_inputs( $inputs, $data )
	{
		$valid = true;
		
		foreach( $inputs as $input )
		{
			// Don't override provided validators
			if ( !isset( $this->validators[ $input->name ] ) )
			{
				switch( $input->type )
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
				
				$this->validators[ $input->name ] = $validator;
			}
			else
			{
				$validator = $this->validators[ $input->name ];
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
			if ( !isset( $this->validators[ $select->name ] ) )
			{
				$validator = new SelectValidator( $select );
				
				$this->validators[ $select->name ] = $validator;
			}
			else
			{
				$validator = $this->validators[ $select->name ];
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
			if ( !isset( $this->validators[ $textarea->name ] ) )
			{
				$validator = new TextareaValidator( $textarea );
				
				$this->validators[ $textarea->name ] = $validator;
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
