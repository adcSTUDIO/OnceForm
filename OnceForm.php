<?php
require_once 'validators.php';
require_once 'OnceFields.php';

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

	public $form_html;
	public $form_func;

	public $form;
	public $doc;

	public $xpath;
	public $fields = array();

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
		if ( is_callable( $form_func ) )
			$this->form_func = $form_func;
		else
			$this->form_html = $form_func;

		$this->user_validator = $validator;

		if ( !is_null( $form_func ) )
			$this->init();
	}

	/**
	 * Sets up the OnceForm. a form func or form html should be set
	 * before calling init (usually through the constructor).
	 * @return void
	 */
	public function init()
	{
		if ( is_callable( $this->form_func ) )
			$this->capture_form( $this->form_func );

		$this->parse_form();
		$this->extract_fields();

		// get the request data
		$data = $this->get_request_data();

		// verify, and set this new data
		$this->set_data( $data );

		if ( $this->isRequest )
			$this->isValid = $this->validate();
	}

	/**
	 * Runs the form func, and captures the resulting html.
	 *
	 * @param function $func A function setup to output (echo) an
	 * HTML5 to the user. This function's output will be captured to an
	 * output buffer.
	 */
	protected function capture_form( $func )
	{
		ob_start();
		call_user_func( $func );
		$this->form_html = ob_get_clean();
	}

	/**
	 * Set the form to process from an HTML string.
	 *
	 * @param string $html The html5 form to be automatically processed.
	 */
	public function parse_form( $html = NULL )
	{
		if ( !is_null( $html ) )
			$this->form_html = $html;
		$html = $this->form_html;

		// extract encoding from the html string
		$encoding = mb_detect_encoding( $html );

		$this->doc = new DOMDocument( '', $encoding );

		// DOMDocument needs a complete document, along with a charset encoding.
		$this->doc->loadHTML( '<html><head>
		<meta http-equiv="content-type" content="text/html; charset='.
		$encoding.'"></head><body>' . trim( $html ) . '</body></html>' );

		// grab a reference to the form element
		$body = $this->doc->getElementsByTagName( 'body' );
		$this->form = $body->item( 0 )->firstChild;
	}

	/**
	 * Extracts the fields from the form based on the registered
	 * field types.
	 */
	public function extract_fields()
	{
		// setup xpath for individual element extraction.
		$xpath = $this->xpath = new DOMXpath($this->doc);
		$this->fields = array();

		// loop and extract each
		foreach ( self::$fieldTypes as $field_type )
		{
			$nodes = $xpath->query($field_type->xpath_query);
			foreach( $nodes as $node )
			{
				$r = new ReflectionClass( $field_type->field_class );
				$this->fields[ $node->getAttribute('name') ] =
					$r->newInstanceArgs( array( $node, $field_type ) );
			}
		}
	}

	/**
	 * Checks the PHP GP objects, to see if a request has been made.
	 * Called automatically in init.
	 */
	public function get_request_data()
	{
		$form = $this->form;

		if ( 'POST' == strtoupper( $form->getAttribute('method') ) )
			$data = $_POST;
		else
			// the default `method` if none specified is GET
			$data = $_GET;

		// :TODO: This is strangly placed. Find a better home.
		if ( !empty( $data ) )
			$this->isRequest = true;

		return $data;
	}

	/**
	 * Starts with the form's default values, and mixes in the $data.
	 * In this way, the data is "polyfilled" so you don't have to worry
	 * about doing isset on every key.
	 * @param array $data A reference to the array of request data.
	 */
	public function set_data( array $data )
	{
		// First get the default data.
		$default_data = $this->get_default_data();

		// filters out any fields that aren't in the onceform, or
		// are not enumerable (:TODO:).
		foreach( $data as $key => $value ) {
			if ( !array_key_exists( $key, $default_data ) )
				unset( $data[$key] );
		}

		// Mix the request data with the default data, and kill extra keys.
		$data = array_merge( $default_data, $data );

		// set the fields to the new data
		foreach( $this->fields as $field ) {
			$field->value( $data[ $field->name() ] );
		}

		return $this->data = $data;
	}

	/**
	 * Gets the default values (specified in the HTML) of the OnceForm.
	 * @return  array The default data.
	 */
	public function get_default_data()
	{
		$data = array();

		foreach( $this->fields as $field ) {
			$data[ $field->name() ] = $field->default_value();
		}

		return $data;
	}

	/**
	 * Gets the names of the fields in an array.
	 * @return array The names of the fields.
	 */
	public function get_field_names()
	{
		$names = array();

		foreach( $this->fields as $field ) {
			$names[] = $field->name();
		}

		return $names;
	}

	public function add_validator( $name, $validator ) {
		$this->validators[ $name ] = $validator;
	}

	public function set_required( $name, $required = true ) {
		$this->fields[ $name ]->required( $required );
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

		if ( $this->user_validator ) {

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

		foreach( $this->fields as $field ) {
			if ( ! $field->validate() )
				$valid = false;
		}

		return $valid;
	}


	public function set_user_validator( /* callable */ $func )
	{
		$this->user_validator = $func;
	}

	static protected $fieldTypes = array();
	static public function addFieldType( FieldType $field )
	{
		self::$fieldTypes[] = $field;
	}

}
OnceForm::addFieldType( new SubFieldType('input', 'text', 'InputField', 'InputValidator') );
//OnceForm::addFieldType( new FieldType('input', 'number', 'NumberField', 'NumericValidator') );
OnceForm::addFieldType( new FieldType('select', 'SelectField', 'SelectValidator') );
OnceForm::addFieldType( new FieldType('textarea', 'TextareaField', 'TextareaValidator') );
