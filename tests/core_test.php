<?php
require_once('simpletest/autorun.php');
require_once('../OnceForm.php');

// tests here
class CoreTest extends UnitTestCase
{
	private $onceform;
	private $form_html = '<form action="./" method="post"></form>';

	function setUp()
	{
		$this->onceform = new OnceForm();
	}

	function tearDown()
	{
		unset( $this->onceform );
	}

	function test_parse_form()
	{
		$this->onceform->parse_form( $this->form_html );

		$this->assertIsA( $this->onceform->doc, 'DOMDocument' );
		$this->assertIsA( $this->onceform->form, 'DOMElement' );

		// The onceform should spit out what it got.
		$this->assertEqual( $this->form_html, $this->onceform->__toString() );
	}
	function test_add_form_func()
	{
		$this->onceform->add_form_func( 'the_form' );

		// The onceform should spit out what it got.
		$this->assertEqual( $this->form_html, $this->onceform->toString() );
	}
}
?>
<?php function the_form() { ?>
<form action="./" method="post"></form>
<?php } ?>