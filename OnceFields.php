<?php
/*
The OnceForm - Write once HTML5 forms processing for PHP.

Copyright (C) 2012-2013  adcSTUDIO LLC

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/
class FieldType
{
	public $tag_name;
	public $field_class;
	public $validator_class;
	public $enumerable;
	public $xpath_query;

	public function __construct( $tag_name, $field_class,
		$validator_class = 'OnceValidator', $enumerable = true,
		$xpath_query = NULL )
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

	public function extract( DOMXpath $xpath )
	{
		$fields = array();
		foreach( $xpath->query( $this->xpath_query ) as $node )
		{
			$r = new ReflectionClass( $this->field_class );
			$fields[ $node->getAttribute('name') ] =
				$r->newInstanceArgs( array( $node, $this ) );
		}
		return $fields;
	}
}
class SubFieldType extends FieldType
{
	public $type;

	public function __construct( $tag_name, $type, $field_class,
		$validator_class = 'OnceValidator', $enumerable = true )
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
	public function validator( iOnceValidator $validator = NULL );
	public function required( $required = NULL );
	public function field_type( FieldType $field_type = NULL );
	public function validity();
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
	public function validator( iOnceValidator $validator = NULL )
	{
		if ( ! is_null( $validator ) )
			$this->validator = $validator;
		elseif ( $validator === false )
			$this->validator = NULL;

		if ( is_null( $this->validator ) ) {
			if ( is_null( $this->field_type ) ) {
				$this->validator = new OnceValidator( $this );
			} else {
				$r = new ReflectionClass( $this->field_type->validator_class );
				$this->validator = $r->newInstanceArgs( array( $this ) );
			}
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

	public function __construct( DOMNode $node = NULL,
								 FieldType $field_type = NULL )
	{
		$this->node( $node );
		$this->field_type( $field_type );
	}

	protected $field_type;
	public function field_type( FieldType $field_type = NULL )
	{
		if ( !is_null( $field_type ) )
			$this->field_type = $field_type;
		return $this->field_type;
	}

	protected $node;
	public function node( DOMNode $node = NULL )
	{
		if ( !is_null( $node ) )
			$this->node = $node;
		$this->default_value = $this->value();
		return $this->node;
	}

	public function validity() {
		return $this->validator()->isValid();
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
		$node = $this->node;
		if ( !is_null( $value ) ) {
			// remove all child nodes (including text nodes)
			foreach ( $node->childNodes as $child )
				$node->removeChild( $child );

			// create and append a new text node
			$node->appendChild(
				$node->ownerDocument->createTextNode( $value )
			);
		}
		return ( $value = $node->nodeValue ) ? $value : '';
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

		// if nothing is selected return '' default value
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

/**
 * The Checkbox Field. This field
 */
class CheckboxField extends InputField
{
	protected $checked;
	public function checked( $checked = NULL )
	{
		$node = $this->node;
		if ( !is_null( $checked ) ) {
			if ( $checked )
				$node->setAttribute('checked', 'checked');
			else
				$node->removeAttribute('checked');
		}
		return $node->hasAttribute('checked');
	}

	/**
	 * In order to simulate the way checkbox elements post to the server
	 * to some extent, the value property cannot be changed here. This
	 * property can only accept and return the value of the value
	 * attribute, or an empty string if the checkbox is not checked.
	 * If you must set or get the actual value of the value field, use
	 * raw_value. Think of the value property of a OnceField as a
	 * request_value property. It's meant to normalize the request
	 * value, and not just to expose the value property of the DOMNode.
	 * @param  string $value The value of the value attribute.
	 * @return string        The value of the value attribute if check
	 *                       or an empty string if unchecked.
	 */
	public function value( $value = NULL )
	{
		if ( !is_null( $value ) ) {
			$this->checked( !empty( $value ) );
		}
		return ( $this->checked() )? parent::value(): '';
	}

	public function raw_value( $value = NULL ) {
		return parent::value( $value );
	}
}

class RadioSetFieldType extends SubFieldType
{
	public function __construct( $validator_class = 'OnceValidator',
	                             $enumerable = true )
	{
		parent::__construct( 'input', 'radio', 'RadioSetField',
			$validator_class, $enumerable, "//input[@type='radio' and @name]"
		);
	}

	public function extract( DOMXpath $xpath )
	{
		$fields = array();
		// We need to loop through all named elements, just as we do for other
		// field types...
		foreach( $xpath->query( $this->xpath_query ) as $node )
		{
			$name = $node->getAttribute('name');

			if ( !isset( $fields[ $name ] ) ) {
				// but we need to make sure to send a list of all radios with
				// the same name to RadioSetFields, rather than single nodes.
				$fields[ $name ] = new RadioSetField(
				  $xpath->query( "//input[@type='radio' and @name='$name']" ),
				$this );
			}
		}
		return $fields;
	}
}
class RadioSetField extends OnceField
{
	public function name()
	{
		$name = '';
		if ( 0 < $this->nodes->length )
			$name = $this->nodes->item(0)->getAttribute('name');
		return $name;
	}

	public function required( $required = NULL )
	{
		if ( !is_null( $required ) ) {
			if ( !$required )
				foreach( $this->nodes as $node )
					$node->removeAttribute('required');
			else
				foreach( $this->nodes as $node )
					$node->setAttribute('required', 'required');
		}
		$required = false;
		foreach( $this->nodes as $node )
			if ( $node->hasAttribute('required') )
				$required = true;
		return $required;
	}

	public function __construct( DOMNodeList $nodes = NULL,
								 FieldType $field_type = NULL )
	{
		$this->nodes( $nodes );
		$this->field_type( $field_type );
	}

	protected $nodes;
	public function nodes( DOMNodeList $nodes = NULL )
	{
		if ( !is_null( $nodes ) ) {
			$this->nodes = $nodes;
			$this->default_value = $this->value();
			if ( 0 < $nodes->length ) {
				$this->name = $nodes->item(0)->getAttribute('name');
			}
		}
		return $nodes;
	}

	public function value( $value = NULL )
	{
		if ( !is_null( $value ) ) {
			foreach( $this->nodes as $node )
				$node->removeAttribute('checked');
			foreach( $this->nodes as $node )
				if ( $node->hasAttribute('value') &&
						$value == $node->getAttribute('value') )
					$node->setAttribute('checked', 'checked');
		}

		$value = '';
		foreach( $this->nodes as $node ) {
			if ( $node->hasAttribute('checked') ) {
				$value = ( $node->hasAttribute('value') )
					? $node->getAttribute('value') : '';
				break;
			}
		}
		return $value;
	}
}
