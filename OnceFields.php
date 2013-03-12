<?php
class FieldType
{
	public $tag_name;
	public $field_class;
	public $validator_class;
	public $enumerable;
	public $xpath_query;

	public function __construct( $tag_name, $field_class,
		$validator_class, $enumerable = true, $xpath_query = NULL )
	{
		$this->tag_name = $tag_name;
		$this->field_class = $field_class;
		$this->validator_class = $validator_class;
		$this->enumerable = $enumerable;
		if ( is_null( $xpath_query ) )
			$this->xpath_query = "//{$tag_name}[@name]";
		else
			$this->xpath_query = $xpath_query;
		$this->enumerable = $enumerable;
	}
}
class SubFieldType extends FieldType
{
	public $type;

	public function __construct( $tag_name, $type, $field_class,
		$validator_class, $enumerable = true )
	{
		parent::__construct( $tag_name, $field_class, $validator_class,
			$enumerable, "//{$tag_name}[@type='$type' and @name]"
		);
		$this->type = $type;
	}
}
interface iOnceField {
	public function default_value();
	public function value( $value = NULL );
	public function name();
	public function validator();
	public function required( $required = NULL );
	public function validate();
	public function validation();
}
abstract class OnceField implements iOnceField
{
	protected $default_value;
	public function default_value() {
		return $this->default_value;
	}

	abstract public function value( $value = NULL );

	public function name() {
		return $this->node->getAttribute('name');
	}

	protected $validator;
	public function validator() {
		if ( is_null( $this->validator ) ) {
			$r = new ReflectionClass( $fieldType->field );
			$this->validator = $r->newInstanceArgs( array( $this->node ) );
		}
		return $this->validator;
	}

	public function required( $required = NULL )
	{
		$attr = 'required';
		if ( !is_null($required) ) {
			if ( $required )
				$this->node->addAttribute($attr,$attr);
			else
				$this->node->removeAttribute($attr);
		}
		return (bool)$this->node->hasAttribute($attr);
	}

	public function __construct( DOMNode $node = NULL )
	{
		$this->node( $node );
	}

	protected $node;
	public function node( DOMNode $node = NULL ) {
		if ( !is_null( $node ) )
			$this->node = $node;
		$this->default_value = $this->value();
		return $this->node;
	}

	public function validate() {
		return $this->validator()->validate();
	}

	public function validation() {
		return $this->validator()->errors;
	}
}
class InputField extends OnceField
{
	public function value( $value = NULL )
	{
		if ( !is_null( $value ) )
			$this->node->setAttribute('value', $value );
		return $this->node->getAttribute('value');
	}
}
class TextareaField extends InputField
{
	public function value( $value = NULL )
	{
		if ( !is_null( $value ) ) {
			// remove all child nodes (including text nodes)
			foreach ( $this->node->childNodes as $node )
				$this->node->removeChild( $node );

			// create and append a new text node
			$textarea->appendChild(
				$this->node->ownerDocument->createTextNode( $value )
			);
		}
		return ( $value = $this->node->nodeValue ) ? $value : '';
	}
}
class SelectField extends OnceField
{
	public function multiple( $multiple = NULL )
	{
		return $this->node->hasAttribute('multiple');
	}

	public function value( $value = NULL )
	{
		$options = $this->node->getElementsByTagName('option');

		// :TODO: add support for array $value arg
		if ( !is_null( $value ) ) {
			foreach( $options as $option ) {
				if ( $option->hasAttribute('selected') )
					$option->removeAttribute('selected');
				if ( $this->get_option_value( $option ) == $value )
					$option->setAttribute('selected', 'selected');
			}
		}

		$values = array();
		foreach( $options as $option ) {
			if ( $option->hasAttribute('selected') )
				$values[] = $this->get_option_value( $option );
		}

		// matches if nothing is selected - returns '' default value
		if ( 0 == count($values) )
			$value = '';
		// only returns the single selected item
		elseif ( 1 == count( $values ) )
			$value = $values[0];
		// if there is more than one, return an array
		else $value = $values;

		return $value;
	}

	/**
	 * Gets the value of an option, which can be either a value attribute
	 * or the actual contents (nodeValue) of the optino.
	 * @param  DOMNode $option The DOMNode of the option.
	 * @return string          The value of the option.
	 */
	protected function get_option_value( DOMNode $option )
	{
		return ( $option->hasAttribute('value') )
			? $option->getAttribute('value')
			: $option->nodeValue;
	}
}
