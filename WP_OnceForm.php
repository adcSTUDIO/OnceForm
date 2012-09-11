<?php
include 'OnceForm.php';

class WP_OnceForm extends OnceForm
{
	protected $action;
	protected $nonce_name;
	
	public function __construct( $form_func = NULL, $action = -1 )
	{
		parent::__construct();
		
		if ( !is_null( $form_func ) )
		{
			$this->add_form_func( $form_func );
			
			$this->insert_nonce( $action );
			
			// $this->data is set here
			$this->check_request();
			
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
		$this->parser = new HTML_Parser_HTML5( '<div>' . $nonce . '</div>' );
		
		// grab the new elements
		$root = $this->parser->root;
		$fields = $root->select('div *');
		
		$form = $this->form;
		
		foreach( $fields as $field )
		{
			// get rid of the self closing business
			$field->self_close_str="";
			
			// monkey patch the `required` flag in to each
			$field->addAttribute( 'required', 'required' );
			
			// manually set the nonce validator
			if ( $field->name == $name )
			{
				$this->add_validator( $field->name, new NonceValidator( $field, $action ) );
				// for testing
				//$field->setAttribute( 'type', 'text' );
			}
			// finally, add the elements
			$form->addChild( $field );
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
