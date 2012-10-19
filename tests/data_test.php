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

		$data = $onceform->get_request();
		$onceform->resolve_request( $data );

		$this->assertEqual( $onceform->data['test'], 'start value' );
	}

	function test_data_resolve_request()
	{
		$onceform = new OnceForm();

		$onceform->parse_form( $this->form_html );

		$onceform->resolve_request( array(
			'test'	=> 'changed value'
		) );

		$this->assertEqual( $onceform->data['test'], 'changed value' );
	}

}
