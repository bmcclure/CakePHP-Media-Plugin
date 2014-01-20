<?php
/**
 * All tests test suite file
 *
 * PHP 5
 * CakePHP 2
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author        Oliver Nowak <info@nowak-media.de>
 * @package       Media.Test.Case
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * All tests test suite class
 *
 * @package       Media.Test.Case
 */
class AllTests extends PHPUnit_Framework_TestSuite {

/**
 * Defines the tests for this suite.
 *
 * @return CakeTestSuite
 */
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('All Tests');

		$path = dirname(__FILE__) . DS;
		$suite->addTestFile($path . 'AllBehaviorsTest.php');
		$suite->addTestFile($path . 'AllHelpersTest.php');
		$suite->addTestFile($path . 'AllLibsTest.php');
		$suite->addTestFile($path . 'AllModelsTest.php');

		return $suite;
	}

}
