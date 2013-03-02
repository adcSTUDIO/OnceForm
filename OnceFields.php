<?php
class FieldType
{
	public $tag_name;
	public $validator;
	public $enumerable;
	public $xpath_query;

	public function __construct( $tag_name, $validator, $enumerable = true, $xpath_query = NULL )
	{
		$this->tag_name = $tag_name;
		$this->validator = $validator;
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

	public function __construct( $tag_name, $validator, $type, $enumerable = true )
	{
		parent::__construct( $tag_name, $validator, $enumerable,
			"//{$tag_name}[@type='$type' and @name]"
		);
		$this->type = $type;
	}
}
interface iOnceField {
	public function default_value();
	public function value( $value = NULL );
	public function name( $name = NULL );
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

	protected $validator
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

	protected $node;
	public function node( DOMNode $node = NULL ) {
		if ( !is_null( $node ) )
			$this->node = $node;
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

	public function node( DOMNode $node = NULL )
	{
		parent::node( $node );
		$this->default_value = $this->value();
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
/*
class SelectField extends OnceField
{

}
class TextareaField extends OnceField
{

}
*/