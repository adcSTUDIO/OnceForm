<?php
include 'OnceForm.php';

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
 * Automatically adds and validates nonce with no boilerplate.
 */
class WP_OnceForm extends OnceForm
{
	protected $action;
	protected $nonce_name;

	public function __construct( $form_func = NULL, $validator = NULL, $action = -1 )
	{
		if ( !is_null( $form_func ) )
		{
			if ( is_callable( $form_func ) )
				$this->add_form_func( $form_func );
			elseif ( is_string( $form_func ) )
				$this->parse_form( $form_func );

			$this->insert_nonce( $action );

			$this->user_validator = $validator;

			// get the request data
			$data = $this->get_request();

			// clean up Wordpress's slashy mess
			stripslashes_deep( $data );

			// verify, and set this new data
			$this->resolve_request( $data );

			if ( $this->isRequest )
				$this->isValid = $this->validate();
		}
	}

	/**
	 * Adds WP nonce field to the OnceForm.
	 */
	public function insert_nonce( $action = -1, $name = '_wponcenonce', $referer = true )
	{
		$this->action = $action;
		$this->nonce_name = $name;

		// get the nonce fields
		$nonce = wp_nonce_field( $action, $name, $referer, false );

		// parse into nodes, so we can manipulate
		$encoding = mb_detect_encoding( $nonce );
		$doc = new DOMDocument( '', $encoding );

		// Make DOMDocument use the right encoding.
		$doc->loadHTML( '<html><head>
		<meta http-equiv="content-type" content="text/html; charset='.$encoding.'">
		</head><body>' . trim( $nonce ) . '</body></html>' );

		//$nonce_doc = new DOMDocument( $nonce );

		// grab the new elements
		$xpath = new DOMXPath( $doc );
		$fields = $xpath->query('//input[@name]');

		foreach( $fields as $field )
		{
			// monkey patch the `required` flag for each
			$field->setAttribute( 'required', 'required' );

			// manually set the nonce validator
			$fname = $field->getAttribute('name');
			if ( $fname == $name )
				$this->add_validator( $fname, new NonceValidator( $field, $action ) );

			// finally, add the elements
			$field = $this->doc->importNode( $field );
			$this->form->appendChild( $field );
		}
	}

}

class NonceValidator extends InputValidator
{
	public $action = -1;

	public function __construct( $props = NULL, $action = -1 )
	{
		parent::__construct( $props );

		$this->action = $action;
	}

	public function validate()
	{
		$valid = parent::validate();

		if ( !wp_verify_nonce( $this->value, $this->action ) )
			$this->errors[] = 'Invalid WP nonce';

		return $this->isValid = empty( $this->errors ) && $valid;
	}

}
