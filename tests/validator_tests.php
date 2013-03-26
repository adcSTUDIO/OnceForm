<?php
require_once('simpletest/autorun.php');
require_once('../OnceForm.php');

// tests here
class InputValidatorTest extends UnitTestCase
{
	public function test_with_null_to_constructor()
	{
		$validator = new OnceValidator();
		$this->assertIsA( $validator->errors(), 'array');
		$this->assertNull( $validator->field() );
	}

	public function test_oncevalidator_with_input()
	{
		$onceform = new OnceForm();
		$onceform->form_html = '<form action="./" method="post">
			<input type="text" value="start value" name="test" id="test" required>
		</form>';
		$onceform->init();

		$field = $onceform->fields['test'];
		$validator = $field->validator();

		$this->assertTrue( $validator->isValid() );
		$this->assertEqual( count( $validator->errors() ), 0 );

		$field->value('');

		$this->assertFalse( $validator->isValid() );
		$this->assertEqual( count( $validator->errors() ), 1 );
	}

	public function test_oncevalidator_with_select()
	{
		$onceform = new OnceForm();
		$onceform->form_html = '<form action="./" method="post">
			<select id="test_field" name="test" required>
				<option value="">Select Something</option>
				<option value="blue">Blue</option>
				<option value="default" selected>Red</option>
				<option>Yellow</option>
			</select>
		</form>';
		$onceform->init();

		$field = $onceform->fields['test'];
		$validator = $field->validator();

		$this->assertTrue( $validator->isValid() );
		$this->assertEqual( count( $validator->errors() ), 0 );

		$field->value('');

		$this->assertFalse( $validator->isValid() );
		$this->assertEqual( count( $validator->errors() ), 1 );
	}

	public function test_emailvalidator()
	{
		$onceform = new OnceForm();
		$onceform->form_html = '<form action="./" method="post">
			<input type="email" name="test" id="test" required>
		</form>';
		$onceform->init();

		$field = $onceform->fields['test'];
		$validator = $field->validator();

		$this->assertFalse( $validator->isValid() );

		$field->value('test');
		$this->assertFalse( $validator->isValid() );

		$field->value('test@test.com');
		$this->assertTrue( $validator->isValid() );
	}
}
