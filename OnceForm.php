<?php
require_once 'validators.php';
require_once 'OnceFields.php';

/*
The OnceForm - Write once HTML5 forms processing for PHP.

Copyright (C) 2012-2013  adcSTUDIO LLC

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
		$this->init_form();
		$this->init_request();
	}

	protected function init_form()
	{
		if ( is_callable( $this->form_func ) )
			$this->capture_form( $this->form_func );

		$this->parse_form();
		$this->extract_fields();
	}

	protected function init_request()
	{
		// get the request data
		if ( $data = $this->get_request_data() ) {
			// verify, and set this new data
			$this->set_data( $data );
			$this->isValid = $this->validate();
		}
		else {
			// use the default data if not a request
			$this->data = $this->get_default_data();
		}
	}

	/**
	 * Runs the form func, and captures the resulting html.
	 *
	 * @param function $func A function setup to output (echo) an
	 * HTML5 to the user. This function's output will be captured to an
	 * output buffer.
	 */
	public function capture_form( $func )
	{
		$this->form_func = $func;
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
		foreach ( self::$fieldTypes as $field_type ) {
			$this->fields = array_merge(
				$this->fields, $field_type->extract( $xpath )
			);
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

		return $data;
	}

	public function is_request()
	{
		return $this->get_request_data();
	}

	public function is_valid()
	{
		return $this->isValid;
	}

	/**
	 * Sets all fields with values in $data. Missing fields are set empty.
	 * In this way, the data is "polyfilled" so you don't have to worry
	 * about doing isset on every key. This method will set the field
	 * values of all fields from request data, but only the keys and
	 * values of enumerable fields are retained in the data property.
	 * When loading and setting stored form data, you may wish to avoid
	 * setting the hidden (unemumerable) form fields.
	 * @param array $data An array of complete request data (even
	 *                    non-enumerable fields).
	 * @param bool $include_hidden Whether or not to also set enumerable
	 *                             fields. default: true
	 * @return array An associateive array of request data mixed
	 *               with field data (enumerable fields only).
	 */
	public function set_data( array $data, $include_hidden = true )
	{
		// First get the field names (filtering enumerable fields).
		$field_names = $this->get_field_names( $include_hidden );

		$this->data = array();
		foreach( $field_names as $name ) {

			// get the field for this data
			$field = $this->fields[ $name ];

			// make sure any keys not present in $data are set to empty.
			$value = ( isset( $data[ $name ] ) ) ? $data[ $name ]: '';

			// :HACK: deal with the specific case of multiple select box
			if ('select' == $field->field_type()->tag_name &&
			     strstr( $name, '[]') && $field->multiple() ) {
				$altn = substr( $name, 0, -2 );
				$value = ( isset( $data[ $altn ] ) ) ? $data[ $altn ]: '';
			}

			// set the field value, even when not enumerable (hidden)
			$field->value( $value );

			// if the field is not enumerable (hidden) don't set in $data
			if ( !$field->field_type()->enumerable ) continue;

			$this->data[ $name ] = $value;
		}

		return $this->data;
	}

	/**
	* Starts with the form's default values, and mixes in the $data.
	* In this way, the data is "polyfilled" so you don't have to worry
	* about doing isset on every key.
	* :NOTE: This does not set the data property, only returns
	* @param array $data An array of complete request data (even
	* non-enumerable fields).
	* @return array An associateive array of request data mixed
	* mixed with field data (enumerable fields only).
	*/
	public function mix_data_with_default( array $data, $include_hidden = false )
	{
		// First get the default data.
		$default_data = $this->get_default_data( $include_hidden );

		// Filters out any fields that aren't in the onceform.
		$filtered_data = array();
		foreach( $data as $key => $value ) {
			if ( array_key_exists( $key, $default_data ) )
				$filtered_data[ $key ] = $data[ $key ];
		}

		// Mix the filtered request data with the default data.
		return array_merge( $default_data, $filtered_data );
	}

	/**
	 * Gets the default values (specified in the HTML) of the OnceForm.
	 * @return  array The default data.
	 */
	public function get_default_data( $include_hidden = false )
	{
		$data = array();

		foreach( $this->fields as $field ) {
			if ( $field->field_type()->enumerable || $include_hidden )
				$data[ $field->name() ] = $field->default_value();
		}

		return $data;
	}

	/**
	 * Gets the names of the enumerable fields in an array.
	 * NOTE: Names are in the original array syntax, not nested.
	 * (So, "name[one][two]" etc.)
	 * @return array The names of the fields.
	 */
	public function get_field_names( $include_hidden = false )
	{
		$names = array();

		foreach( $this->fields as $field ) {
			if ( $field->field_type()->enumerable || $include_hidden )
				$names[] = $field->name();
		}

		return $names;
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

			$validator = new UserValidator( $errors );

			if ( !$validator->isValid() )
				$valid = false;

			$this->validators[] = $validator;
		}

		foreach( $this->fields as $field ) {
			$this->validators[] = $field->validator();
			if ( ! $field->validity() )
				$valid = false;
		}

		return $valid;
	}

	public function get_validation_errors()
	{
		$errors = array();
		foreach( $this->validators as $validator )
			$errors += $validator->errors();
		return $errors;
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
OnceForm::addFieldType( new SubFieldType('input', 'text', 'InputField') );
OnceForm::addFieldType( new SubFieldType('input', 'password', 'InputField') );
OnceForm::addFieldType( new SubFieldType('input', 'email', 'InputField', 'EmailValidator') );
//OnceForm::addFieldType( new FieldType('input', 'number', 'NumberField') );
OnceForm::addFieldType( new FieldType('select', 'SelectField') );
OnceForm::addFieldType( new FieldType('textarea', 'TextareaField') );
OnceForm::addFieldType( new SubFieldType('input', 'checkbox', 'CheckboxField') );
OnceForm::addFieldType( new RadioSetFieldType() );
