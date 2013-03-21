<?php
require_once('simpletest/autorun.php');
require_once('../OnceFields.php');

class InputFieldTest extends UnitTestCase
{
	protected $doc;
	protected $field;

	function setUp()
	{
		$encoding = mb_detect_encoding( $this->html );
		$this->doc = new DOMDocument('', $encoding );
		$this->doc->loadHTML('<html><head>
			<meta http-equiv="content-type" content="text/html; charset=' .
			$encoding . '"></head><body>' . $this->html . '</body></html>'
		);
		// :TODO: May need to actuall test this somewhere
		$node = $this->doc->getElementByID('test_field');
		$this->field = new InputField( $node );
	}
	function make_field( DOMNode $node ) {
	}
	function tearDown() {
		$this->doc = null;
		$this->field = null;
	}

	function test_field_default_value()
	{
		$this->assertEqual('default', $this->field->default_value() );
	}

	function test_field_required()
	{
		$field = $this->field;
		$this->assertTrue( $field->required() );
		$this->assertTrue( $field->node()->hasAttribute('required') );
		$field->required( false );
		$this->assertFalse( $field->required() );
		$this->assertFalse( $field->node()->hasAttribute('required') );
	}

	function test_field_name()
	{
		$this->assertEqual('test_01', $this->field->name() );
	}

	function test_field_value()
	{
		$field = $this->field;
		$this->assertEqual('default', $field->value() );
		$this->assertEqual('default', $field->node()->getAttribute('value') );
		$field->value('changed');
		$this->assertEqual('changed', $field->value() );
		$this->assertEqual('changed', $field->node()->getAttribute('value') );
	}

	protected $html = '<form action="./" method="post">
		<input type="text" id="test_field"
			name="test_01" value="default" required>
	</form>';
}

class SelectFieldTest extends UnitTestCase
{
	protected $doc;
	protected $field;

	function setUp()
	{
		$encoding = mb_detect_encoding( $this->html );
		$this->doc = new DOMDocument('', $encoding );
		$this->doc->loadHTML('<html><head>
			<meta http-equiv="content-type" content="text/html; charset=' .
			$encoding . '"></head><body>' . $this->html . '</body></html>'
		);
		// :TODO: May need to actuall test this somewhere
		$node = $this->doc->getElementByID('test_field');
		$this->field = new SelectField( $node );
	}
	function tearDown() {
		$this->doc = null;
		$this->field = null;
	}

	function test_field_default_value()
	{
		$this->assertEqual('default', $this->field->default_value() );
	}

	function test_field_required()
	{
		$field = $this->field;
		$this->assertTrue( $field->required() );
		$this->assertTrue( $field->node()->hasAttribute('required') );
		$field->required( false );
		$this->assertFalse( $field->required() );
		$this->assertFalse( $field->node()->hasAttribute('required') );
	}

	function test_field_name()
	{
		$this->assertEqual('test_01', $this->field->name() );
	}

	function test_field_value()
	{
		$field = $this->field;
		$value = $this->assertEqual('default', $field->value() );
		$field->value('changed');
		$this->assertNotEqual('changed', $field->value() );
		$field->value('blue');
		$this->assertEqual('blue', $field->value() );
	}

	function test_field_multiple()
	{
		$node = $this->doc->getElementByID('multiple_field');
		$field = new SelectField( $node );
		$this->assertIsA( $field->value(), 'array');
		$this->assertEqual( array('blue','Red'), $field->value() );
	}

	protected $html = '<form action="./" method="post">
		<select id="test_field" name="test_01" required>
			<option value="">Select Something</option>
			<option value="blue">Blue</option>
			<option value="default" selected>Red</option>
			<option>Yellow</option>
		</select>
		<select id="multiple_field" name="test_02" required>
			<option value="">Select Something</option>
			<option value="blue" selected>Blue</option>
			<option selected>Red</option>
			<option>Yellow</option>
		</select>
	</form>';
}

class TextareaFieldTest extends UnitTestCase
{
	protected $doc;
	protected $field;

	function setUp()
	{
		$encoding = mb_detect_encoding( $this->html );
		$this->doc = new DOMDocument('', $encoding );
		$this->doc->loadHTML('<html><head>
			<meta http-equiv="content-type" content="text/html; charset=' .
			$encoding . '"></head><body>' . $this->html . '</body></html>'
		);
		// :TODO: May need to actuall test this somewhere
		$node = $this->doc->getElementByID('test_field');
		$this->field = new TextareaField( $node );
	}
	function tearDown() {
		$this->doc = null;
		$this->field = null;
	}

	function test_field_default_value()
	{
		$this->assertEqual('default', $this->field->default_value() );
	}

	function test_field_required()
	{
		$field = $this->field;
		$this->assertTrue( $field->required() );
		$this->assertTrue( $field->node()->hasAttribute('required') );
		$field->required( false );
		$this->assertFalse( $field->required() );
		$this->assertFalse( $field->node()->hasAttribute('required') );
	}

	function test_field_name()
	{
		$this->assertEqual('test_01', $this->field->name() );
	}

	function test_field_value()
	{
		$field = $this->field;
		$value = $this->assertEqual('default', $field->value() );
		$field->value('changed');
		$this->assertEqual('changed', $field->value() );
	}

	protected $html = '<form action="./" method="post">
		<textarea id="test_field" name="test_01" required>default</textarea>
	</form>';
}

class CheckboxFieldTest extends UnitTestCase
{
	protected $doc;
	protected $field;

	function setUp()
	{
		$encoding = mb_detect_encoding( $this->html );
		$this->doc = new DOMDocument('', $encoding );
		$this->doc->loadHTML('<html><head>
			<meta http-equiv="content-type" content="text/html; charset=' .
			$encoding . '"></head><body>' . $this->html . '</body></html>'
		);
		// :TODO: May need to actuall test this somewhere
		$node = $this->doc->getElementByID('test_field');
		$this->field = new CheckboxField( $node );
	}
	function tearDown() {
		$this->doc = null;
		$this->field = null;
	}

	function test_field_default_value()
	{
		$this->assertEqual('default', $this->field->default_value() );
	}

	function test_field_required()
	{
		$field = $this->field;
		$this->assertTrue( $field->required() );
		$field->required( false );
		$this->assertFalse( $field->required() );
	}

	function test_field_name()
	{
		$this->assertEqual('test_01', $this->field->name() );
	}

	function test_field_value()
	{
		$field = $this->field;
		$value = $this->assertEqual('default', $field->value() );
		$field->value('changed');
		$this->assertEqual('default', $field->value() );
		$this->assertTrue( $field->checked() );
		$field->value('');
		$this->assertEqual('', $field->value() );
		$this->assertFalse( $field->checked() );

	}

	protected $html = '<form action="./" method="post">
		<input type="checkbox" id="test_field" name="test_01"
			value="default" required checked>
	</form>';
}

class RadioSetFieldTest extends UnitTestCase
{
	protected $doc;
	protected $field;

	function setUp()
	{
		$encoding = mb_detect_encoding( $this->html );
		$this->doc = new DOMDocument('', $encoding );
		$this->doc->loadHTML('<html><head>
			<meta http-equiv="content-type" content="text/html; charset=' .
			$encoding . '"></head><body>' . $this->html . '</body></html>'
		);
		$fieldType = new RadioSetFieldType('InputValidator');
		$nodes = $fieldType->extract( new DOMXPath($this->doc) );
		$this->field = $nodes['test_01'];
	}
	function tearDown() {
		$this->doc = null;
		$this->field = null;
	}

	function test_field_default_value()
	{
		$this->assertEqual('default', $this->field->default_value() );
	}

	function test_field_required()
	{
		$field = $this->field;
		$this->assertTrue( $field->required() );
		$field->required( false );
		$this->assertFalse( $field->required() );
	}

	function test_field_name()
	{
		$this->assertEqual('test_01', $this->field->name() );
	}

	function test_field_value()
	{
		$field = $this->field;
		$value = $this->assertEqual('default', $field->value() );
		$field->value('changed');
		$this->assertEqual('changed', $field->value() );
	}

	protected $html = '<form action="./" method="post">
		<input type="radio" id="test_field1" name="test_01"
			value="default" required checked>
		<input type="radio" id="test_field2" name="test_01"
			value="changed" required>
	</form>';
}