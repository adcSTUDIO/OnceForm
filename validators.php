<?php
/*
The OnceForm - Write once HTML5 forms processing for PHP.

Copyright (C) 2012  adcSTUDIO LLC

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

interface iOnceValidator {
	public function field( iOnceField $field = NULL );
	public function isValid();
	public function errors();
}

/**
 * InputValidatr - validates a basic input type="text" and serves as
 * the base for other validators.
 *
 * @author Kevin Newman <Kevin@adcSTUDIO.com>
 * @package The OnceForm
 * @copyright (C) 2012 adcSTUDIO LLC
 * @license GNU/GPL, see license.txt
 */
class OnceValidator implements iOnceValidator
{
	protected $field;
	public function field( iOnceField $field = NULL )
	{
		if ( !is_null( $field ) ) {
			$this->field = $field;
			$this->validate();
		}
		return $this->field;
	}

	protected $isValid;
	public function isValid() {
		return $this->isValid;
	}

	protected $errors;
	public function errors() {
		return $this->errors;
	}

	public function __construct( iOnceField $field = NULL ) {
		$this->field( $field );
	}

	protected function validate()
	{
		// reset the error count when revalidating
		$this->errors = array();

		$field = $this->field;
		if ( $field->required() && '' == $field->value() )
			$this->errors[] = 'Required field *'.$field->name().'* is empty';

		return $this->isValid = empty( $this->errors );
	}
}
