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

require_once '../OnceForm.php';

$form = new OnceForm('my_form');

/*if ( $form->isRequest )
{
	if ( $form->isValid )
	{
		// Do something with the data, or perform more complicated validation
		$form->data;
	}
	else {
		// display an error somewhere
	}
}*/
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
<?php function my_form() { ?>
<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post" name="form1">

<p>
	<input type="email" name="user_email" placeholder="Enter your email address">
</p>
<p>
	<input type="email" name="designer_stuff" required placeholder="Enter your email address">
</p>
<?php /* <p>
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
	<select name="selectTest" id="selectTest" required>
		<option value="">Select Something</option>
		<option value="1">One</option>
		<option>Two</option>
		<option value="3" selected>Three</option>
		<option value="4">Four</option>
	</select>
</p>
<p>
	<label for="textTest">Enter some text</label><br>
	<textarea id="textTest" name="textTest" required></textarea>
</p> */ ?>
<p>
	<input type="submit" value="Go!">
</p>
</form>
<?php } ?>
<?php echo $form ?>
</td>
</tr>
</table>

<pre>
isRequest: <?php var_dump( $form1->isRequest ) ?>
isValid: <?php var_dump( $form1->isValid ) ?>

<?php print_r( $form1->validators ) ?>

<?php print_r( $form1->errors ) ?>

<?php print_r( $_POST ) ?>

Mem: <?php echo memory_get_usage() ?>
</pre>

</body>
</html>