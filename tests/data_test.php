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

	function test_data_defaults_data_prop()
	{
		$onceform = new OnceForm($this->form_html);
		$this->assertEqual( array('test'=>'start value'), $onceform->data );
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

	function test_incomplete_request_data()
	{
		$onceform = new OnceForm('small_form');

		$this->assertIdentical( array( 'one'=>'1', 'two'=>'2', 'three'=>'3'),
								 $onceform->data );

		$onceform->set_data( array( 'one'=>'1','two'=>'2' ) );

		$this->assertIdentical(array( 'one'=>'1', 'two'=>'2', 'three'=>''),
								 $onceform->data );
	}

	function test_is_request()
	{
		$onceform = new OnceForm('small_form');

		$this->assertFalse( $onceform->is_request() );

		$old_post = $_POST;

		$_POST = array('one'=>'1', 'two'=>'2', 'three'=>'3');
		$this->assertTrue( $onceform->is_request() );

		$_POST = $old_post;
	}

}
?>
<?php function small_form() { ?>
<form action="./" method="post">
<input type="text" name="one" value="1">
<input type="text" name="two" value="2">
<input type="text" name="three" value="3">
</form>
<?php } ?>