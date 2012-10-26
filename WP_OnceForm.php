<?php
include 'OnceForm.php';

class WP_OnceForm extends OnceForm
{
	protected $action;
	protected $nonce_name;
	
	public function __construct( $form_func = NULL, $validator = NULL, $action = -1 )
	{
		if ( !is_null( $form_func ) )
		{
			$this->add_form_func( $form_func );
			
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
		$nonce_doc = new DOMDocument( $nonce );
		
		// grab the new elements
		$xpath = new DOMXPath( $nonce_doc );
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
