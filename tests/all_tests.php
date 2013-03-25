<?php
require_once('simpletest/autorun.php');

class AllTests extends TestSuite
{
	function AllTests()
	{
		$this->TestSuite('All Tests');
		$this->addFile('field_tests.php');
		$this->addFile('core_test.php');
		$this->addFile('data_test.php');
		$this->addFile('validator_tests.php');
	}
}
