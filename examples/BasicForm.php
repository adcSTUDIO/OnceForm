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

include '../lib/ganon.php';
include '../OnceForm.php';

$form1 = new OnceForm( 'myForm' );

if ( $form1->isRequest )
{
	if ( $form1->isValid )
	{
		// Do something with the data
		$form1->data;
	}
	else {
		// probably don't have to do anything!!
	}
}
?>
<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title></title>
</head>

<body>
<table>
<tr>
<td valign="top"><p>This is a basic HTML5 form with validation.</p></td>
<td valign="top"><p>This is the same form with no validation.<br>
(Use this to sidestep client side HTML5 validation, and<br>
submit invalid data to the server to demo the OnceForm.)</p></td>
</tr>

<tr><td>
<?php function myform() { ?>
<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post" name="form1">

<p>
	<input type="email" name="user_email" placeholder="Enter your email address">
</p>
<p>
	<input type="email" name="designer_stuff" required placeholder="Enter your email address">
</p>
<p>
	<input name="ckBox1" type="checkbox" id="ckBox1" value="test" required>
	<label for="ckBox1">test 1</label>
	<input name="ckBox2" type="checkbox" id="ckBox2" value="test">
	<label for="ckBox2">test 2</label>
</p>
<p>
	<input type="radio" name="rdSet" id="radio1" value="radio1">
	<label for="radio1">test A</label>
	<input type="radio" name="rdSet" id="radio2" value="radio2">
	<label for="radio2">test B</label>
</p>
<p>
	<label for="selectTest">Select Something</label>
	<select name="selectTest" id="selectTest">
		<option value="1">One</option>
		<option value="2">Two</option>
		<option value="3">Three</option>
	</select>
</p>
<p>
	<input type="submit" value="Go!">
</p>
</form>
<?php } ?>
<?php echo $form1 ?>
</td>
<td>
<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post" name="form1">

<p>
	<input type="text" name="user_email" placeholder="Enter your email address">
</p>
<p>
	<input type="text" name="designer_stuff" placeholder="Enter your email address">
</p>
<p>
	<input name="ckBox1" type="checkbox" id="ckBox1" value="test">
	<label for="ckBox1">test 1</label>
	<input name="ckBox2" type="checkbox" id="ckBox2" value="test">
	<label for="ckBox2">test 2</label>
</p>
<p>
	<input type="radio" name="rdSet" id="radio1" value="radio1">
	<label for="radio1">test A</label>
	<input type="radio" name="rdSet" id="radio2" value="radio2">
	<label for="radio2">test B</label>
</p>
<p>
	<label for="selectTest">Select Something</label>
	<select name="selectTest" id="selectTest">
		<option value="1">One</option>
		<option value="2">Two</option>
		<option value="3">Three</option>
	</select>
</p>
<p>
	<input type="submit" value="Go!">
</p>
</form>
</td>
</tr>
</table>

<pre>
isRequest: <?php var_dump( $form1->isRequest ) ?>
isValid: <?php var_dump( $form1->isValid ) ?>

<?php print_r( $form1->validators ) ?>

<?php print_r( $form1->errors ) ?>

<?php print_r( $_POST ) ?>
</pre>

</body>
</html>