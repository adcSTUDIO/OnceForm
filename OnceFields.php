<?php
abstract class OnceField
{
	protected $default_value;
	abstract public function default_value();
	abstract public function value( $value = NULL );
	abstract public function name( $name = NULL );
	
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
	public function set_node( DOMNode $node )
	{
		$this->node = $node;
	}
	public function node()
	{
		return $this->node;
	}
}
class InputField extends OnceField
{
	
}
class SelectField extends OnceField
{

}
class TextareaField extends OnceField
{

}
