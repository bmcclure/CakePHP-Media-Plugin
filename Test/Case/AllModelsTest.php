<?php
/**
 * All models test suite file
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
 * All models test suite class
 *
 * @package       Media.Test.Case
 */
class AllModelsTest extends PHPUnit_Framework_TestSuite {

/**
 * Defines the tests for this suite.
 *
 * @return CakeTestSuite
 */
	public static function suite() {
		$suite = new CakeTestSuite('All Models');

		$path = dirname(__FILE__) . DS . 'Model' . DS;
		$suite->addTestDirectory($path);

		return $suite;
	}

}
