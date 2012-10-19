<?php
require_once('simpletest/autorun.php');
require_once('../OnceForm.php');

// tests here
class InputValidatorTest extends UnitTestCase
{
	public function test_defaults_no_request()
	{
		$onceform = new OnceForm();
		$onceform->parse_form( '<form action="./" method="post">
			<input type="text" value="start value" name="test" id="test" required>
		</form>' );

		$node = $onceform->doc->getElementById( 'test' );
		
		$validator = new InputValidator( $node );

		// Name prop should match the value defined in HTML.
		$this->assertEqual( $validator->name, 'test' );

		// Value prop should match the value defined in HTML.
		$this->assertEqual( $validator->value, 'start value' );

		// Requied should be true, because it's defined in HTML.
		$this->assertTrue( $validator->required );
	}

	public function test_valid_base_validator_no_request()
	{
		$onceform = new OnceForm();
		$onceform->parse_form( '<form action="./" method="post">
			<input type="text" value="start value" name="test" id="test" required>
		</form>' );

		$node = $onceform->doc->getElementById( 'test' );
		
		$validator = new InputValidator( $node );

		// Should be a valid field, because there is a value.
		$this->assertTrue( $validator->validate() );
	}

	public function test_empty_validator()
	{
		$validator = new InputValidator();

		$this->assertNull( $validator->validate() );
	}
}
