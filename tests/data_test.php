<?php
require_once('simpletest/autorun.php');
require_once('../OnceForm.php');

// tests here
class DataTest extends UnitTestCase
{
	private $form_html = '<form action="./" method="post">
		<input type="text" value="start value" name="test">
	</form>';

	function test_data_defaults()
	{
		$onceform = new OnceForm();

		$onceform->parse_form( $this->form_html );
		$onceform->extract_fields();

		$data = $onceform->get_default_data();

		$this->assertEqual( $data['test'], 'start value' );
	}

	function test_data_resolve_request()
	{
		$onceform = new OnceForm();

		$onceform->parse_form( $this->form_html );
		$onceform->extract_fields();

		$onceform->set_data( array(
			'test'	=> 'changed value'
		) );

		$this->assertEqual( $onceform->data['test'], 'changed value' );
	}

}
