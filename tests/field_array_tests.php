<?php
require_once('simpletest/autorun.php');
require_once('../OnceFields.php');

class InputFieldTest extends UnitTestCase
{
	protected $onceform;

	function setUp() {
		$this->onceform = new OnceForm( $this->html );
	}
	function tearDown() {
		$this->onceform = null;
	}

	function test_field_default_value()
	{
		$this->assertEqual(array(, $this->field->default_value() );
	}

	function test_field_name()
	{
		$this->assertEqual('test_01', $this->field->name() );
	}

	function test_field_value()
	{
		$field = $this->field;
		$this->assertEqual('default', $field->value() );
		$field->value('changed');
		$this->assertEqual('changed', $field->value() );
	}

	protected $html = '<form action="./" method="post">
		<input type="text"
			name="test[]" value="1" required>
		<input type="text"
			name="test[]" value="2" required>
	</form>';
}
