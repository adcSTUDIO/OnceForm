<?php
require_once('simpletest/autorun.php');
require_once('../OnceForm.php');

// tests here
class CoreTest extends UnitTestCase
{
	private $form_html = '<form action="./" method="post"></form>';

	function test_parse_form()
	{
		$onceform = new OnceForm();
		$onceform->parse_form( $this->form_html );

		$this->assertIsA( $onceform->doc, 'DOMDocument' );
		$this->assertIsA( $onceform->form, 'DOMElement' );

		// The onceform should spit out what it got.
		$this->assertEqual( $this->form_html, $onceform->__toString() );
	}

	function test_parse_form_through_constructor()
	{
		$onceform = new OnceForm( $this->form_html );

		$this->assertIsA( $onceform->doc, 'DOMDocument' );
		$this->assertIsA( $onceform->form, 'DOMElement' );

		// The onceform should spit out what it got.
		$this->assertEqual( $this->form_html, $onceform->__toString() );
	}

	function test_capture_form()
	{
		$onceform = new OnceForm();
		$onceform->form_func = 'the_form';
		$onceform->init();

		// The onceform should spit out what it got.
		$this->assertEqual( 'the_form', $onceform->form_func );
		$this->assertEqual( $this->form_html, $onceform->toString() );
	}

	function test_add_form_func_through_constructor()
	{
		$onceform = new OnceForm( 'the_form' );

		// The onceform should spit out what it got.
		$this->assertEqual( $this->form_html, $onceform->toString() );
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

}
?>
<?php function the_form() { ?>
<form action="./" method="post"></form>
<?php } ?>
<?php function small_form() { ?>
<form action="./" method="post">
<input type="text" name="one" value="1">
<input type="text" name="two" value="2">
<input type="text" name="three" value="3">
</form>
<?php } ?>
