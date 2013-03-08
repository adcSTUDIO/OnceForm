<?php
class FieldType
{
	public $tag_name;
	public $field;
	public $validator;
	public $enumerable;
	public $xpath_query;

	public function __construct( $tag_name, $field_class,
		$validator_class, $enumerable = true, $xpath_query = NULL )
	{
		$this->tag_name = $tag_name;
		$this->field = $field_class;
		$this->validator = $validator_class;
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
		parent::__construct( $tag_name, $validator_class, $enumerable,
			"//{$tag_name}[@type='$type' and @name]"
		);
		$this->type = $type;
	}
}
interface iOnceField {
	public function default_value();
	public function value( $value = NULL );
	public function name();
	public function validator( $validator = NULL );
	public function required( $required = NULL );
}
abstract class OnceField implements iOnceField
{
	protected $default_value;
	public function default_value() {
		return $this->default_value;
	}

	abstract public function value( $value = NULL );

	public function name() {
		return $this->node->getAttribute( 'name' );
	}

	protected $validator;
	public function validator( $validator = NULL ) {
		if ( !is_null( $validator ) )
			$this->validator = $validator;
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
		return $this->node->hasAttribute($attr);
	}

	protected $field_type;
	public function __construct( DOMNode $node = NULL, FieldType $field_type )
	{
		$this->node( $node );
		$this->field_type;
	}

	protected $node;
	public function node( DOMNode $node = NULL ) {
		if ( !is_null( $node ) )
			$this->node = $node;
		$this->default_value = $this->value();
		// :TODO: extract properties values
		return $this->node;
	}
}
class InputField extends OnceField
{
	public function value( $value = NULL )
	{
		if ( !is_null( $value ) )
			$this->node->setAttribute( 'value', $value );
		return $this->node->getAttribute( 'value' );
	}
}
class TextareaField extends InputField
{
	public function value( $value = NULL )
	{
		if ( !is_null( $value ) )
		{
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
		$values = array();

		if ( !is_null( $value ) ) {
			foreach( $options as $option ) {
				if ( $option->hasAttribute('selected') )
					$option->removeAttribute('selected');
				if ( $this->get_option_value( $option ) == $value )
					$option->setAttribute('selected', 'selected');
			}
		}

		foreach( $options as $option ) {
			if ( $option->hasAttribute('selected') )
				$values[] = $this->get_option_value( $option );
		}

		if ( 0 == count($values) )
			$value = '';
		elseif ( 1 == count( $values ) )
			$value = $value[0];
		else
			$value = $values;

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
