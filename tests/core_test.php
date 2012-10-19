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

	function test_add_form_func()
	{
		$onceform = new OnceForm();
		$onceform->add_form_func( 'the_form' );

		// The onceform should spit out what it got.
		$this->assertEqual( $this->form_html, $onceform->toString() );
	}

}
?>
<?php function the_form() { ?>
<form action="./" method="post"></form>
<?php } ?>