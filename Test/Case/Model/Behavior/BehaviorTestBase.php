<?php
/**
 * Base Behavior Test Case File
 *
 * Copyright (c) 2007-2012 David Persson
 *
 * Distributed under the terms of the MIT License.
 * Redistributions of files must retain the above copyright notice.
 *
 * PHP 5
 * CakePHP 2
 *
 * @copyright     2007-2012 David Persson <davidpersson@gmx.de>
 * @link          http://github.com/davidpersson/media
 * @package       Media.Test.Case.Model.Behavior
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

App::uses('Model', 'Model');
App::uses('Folder', 'Utility');

require_once dirname(dirname(dirname(__FILE__))) . DS . 'constants.php';
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DS . 'Config' . DS . 'bootstrap.php';
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DS . 'Fixture' . DS . 'TestData.php';
require_once dirname(dirname(__FILE__)) . DS . 'models.php';

/**
 * Base Behavior Test Case Class
 *
 * @package       Media.Test.Case.Model.Behavior
 */
abstract class BaseBehaviorTest extends CakeTestCase {

	public $fixtures = array(
		'plugin.media.song'
	);

	public $behaviorSettings = array();

/**
 * @var TestData
 */
	public $Data;

	public $oldConfig;

	public function setUp() {
		parent::setUp();

		$this->Data = new TestData();
		$this->oldConfig = Configure::read('Media');
	}

	public function tearDown() {
		parent::tearDown();

		$this->Data->cleanUp();
		ClassRegistry::flush();
		Configure::write('Media', $this->oldConfig);
	}

	protected function _isWindows() {
		return strtolower(substr(PHP_OS, 0, 3)) === 'win';
	}

}
