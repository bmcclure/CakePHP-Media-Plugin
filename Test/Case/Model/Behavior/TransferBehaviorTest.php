<?php
/**
 * Transfer Behavior Test Case File
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

App::uses('TransferBehavior', 'Media.Model/Behavior');

require_once dirname(dirname(dirname(__FILE__))) . DS . 'constants.php';
require_once dirname(__FILE__) . DS . 'BehaviorTestBase.php';

/**
 * Test Transfer Behavior Class
 *
 * @package       Media.Test.Case.Model.Behavior
 */
class TestTransferBehavior extends TransferBehavior {

	public function alternativeFile($file, $tries = 100) {
		return $this->_alternativeFile($file, $tries);
	}

}

/**
 * Transfer Behavior Test Case Class
 *
 * @package       Media.Test.Case.Model.Behavior
 */
class TransferBehaviorTest extends BaseBehaviorTest {

	public $fixtures = array(
		'plugin.media.movie',
		'plugin.media.actor'
	);

	protected $_remoteAvailable = false;

	public function setUp() {
		parent::setUp();

		$this->behaviorSettings = array(
			'transferDirectory' => $this->Data->settings['transfer']
		);

		$handle = @fsockopen('cakephp.org', 80);
		if ($handle) {
			$this->_remoteAvailable = true;
			fclose($handle);
		}
	}

	public function testSetupValidation() {
		$Model = ClassRegistry::init('Movie');
		$Model->validate['file'] = array(
			'resource' => array('rule' => 'checkResource')
		);
		$Model->Behaviors->load('Media.Transfer');

		$expected = array(
			'resource' => array(
				'rule'       => 'checkResource',
				'allowEmpty' => true,
				'required'   => false,
				'last'       => true
			)
		);
		$this->assertEqual($Model->validate['file'], $expected);

		$Model = ClassRegistry::init('Movie');
		$Model->validate['file'] = array(
			'resource' => array(
				'rule'     => 'checkResource',
				'required' => true,
			)
		);
		$Model->Behaviors->load('Media.Transfer');

		$expected = array(
			'resource' => array(
				'rule'       => 'checkResource',
				'allowEmpty' => true,
				'required'   => true,
				'last'       => true
			)
		);
		$this->assertEqual($Model->validate['file'], $expected);

		$Model = ClassRegistry::init('Movie');
		$Model->validate['file'] = array(
			'resource' => array(
				'rule'     => 'checkResource',
				'required' => true,
			),
			'access'   => array(
				'rule' => 'checkAccess'
			)
		);
		$Model->Behaviors->load('Media.Transfer');

		$expected = array(
			'resource' => array(
				'rule'       => 'checkResource',
				'allowEmpty' => true,
				'required'   => true,
				'last'       => true
			),
			'access'   => array(
				'rule' => 'checkAccess',
				'allowEmpty' => true,
				'required'   => false,
				'last'       => true
			)
		);
		$this->assertEqual($Model->validate['file'], $expected);

		$Model = ClassRegistry::init('Movie');
		$Model->validate['file'] = array(
			'rule' => 'checkResource'
		);
		$Model->Behaviors->load('Media.Transfer');

		$expected = array(
			'checkResource' => array(
				'rule'       => 'checkResource',
				'allowEmpty' => true,
				'required'   => false,
				'last'       => true
			)
		);
		$this->assertEqual($Model->validate['file'], $expected);

		$Model = ClassRegistry::init('Movie');
		$Model->validate['file'] = array(
			'rule' => 'checkResource',
			'required' => true
		);
		$Model->Behaviors->load('Media.Transfer');

		$expected = array(
			'checkResource' => array(
				'rule'       => 'checkResource',
				'allowEmpty' => true,
				'required'   => true,
				'last'       => true
			)
		);
		$this->assertEqual($Model->validate['file'], $expected);

		$Model = ClassRegistry::init('Movie');
		$Model->validate['file'] = 'checkResource';
		$Model->Behaviors->load('Media.Transfer');

		$expected = array(
			'checkResource' => array(
				'rule'       => 'checkResource',
				'allowEmpty' => true,
				'required'   => false,
				'last'       => true
			)
		);
		$this->assertEqual($Model->validate['file'], $expected);
	}

	public function testFailOnNoResource() {
		$Model = ClassRegistry::init('Movie');
		$Model->validate['file'] = array(
			'resource' => array(
				'rule'       => 'checkResource',
				'required'   => true,
				'allowEmpty' => false,
			)
		);
		$Model->Behaviors->load('Media.Transfer', $this->behaviorSettings);

		$item = array('title' => 'Spiderman I', 'file' => '');
		$Model->create($item);
		$this->assertFalse($Model->save());

		$item = array('title' => 'Spiderman I', 'file' => array());
		$Model->create($item);
		$this->assertFalse($Model->save());

		$item = array(
			'title' => 'Spiderman I',
			'file' => array(
				'name'     => '',
				'type'     => '',
				'tmp_name' => '',
				'error'    => UPLOAD_ERR_NO_FILE,
				'size'     => 0,
			)
		);
		$Model->create($item);
		$this->assertFalse($Model->save());
	}

	public function testDestinationFile() {
		$Model = ClassRegistry::init('Movie');
		$Model->Behaviors->load('Media.Transfer', $this->behaviorSettings);

		$file = $this->Data->getFile(array('image-jpg.jpg' => 'wei³rd$Ö- FILE_name_'));
		$item = array('title' => 'Spiderman I', 'file' => $file);
		$Model->create();
		$this->assertTrue(!!$Model->save($item));
		$expected = $this->Data->settings['transfer'] . 'img' . DS . 'wei_rd_oe_file_name';
		$this->assertEqual($Model->transferred(), $expected);
	}

	public function testTransferred() {
		$Model = ClassRegistry::init('TheVoid');
		$Model->Behaviors->load('Media.Transfer', $this->behaviorSettings);

		$this->assertFalse($Model->transferred());

		$file = $this->Data->getFile('image-jpg.jpg');
		$Model->transfer($file);
		$file = $Model->transferred();
		$expected = $this->Data->settings['transfer'] . 'img' . DS . 'image_jpg.jpg';
		$this->assertEqual($file, $expected);
		$this->assertTrue(file_exists($file));
	}

	public function testFileLocalToFileLocal() {
		$Model = ClassRegistry::init('Movie');
		$Model->Behaviors->load('Media.Transfer', $this->behaviorSettings);

		$file = $this->Data->getFile(array('image-jpg.jpg' => 'ta.jpg'));
		$item = array('title' => 'Spiderman I', 'file' => $file);
		$Model->create();
		$this->assertTrue(!!$Model->save($item));
		$this->assertTrue(file_exists($file));
		$expected = $this->Data->settings['transfer'] . 'img' . DS . 'ta.jpg';
		$this->assertEqual($Model->transferred(), $expected);

		$Model = ClassRegistry::init('TheVoid');
		$Model->Behaviors->load('Media.Transfer', $this->behaviorSettings);

		$file = $this->Data->getFile(array('image-jpg.jpg' => 'tb.jpg'));
		$expected = $this->Data->settings['transfer'] . 'img' . DS . 'tb.jpg';
		$this->assertEquals($Model->transfer($file), $expected);
		$this->assertEquals($Model->transferred(), $expected);
		$this->assertTrue(file_exists($file));

		ClassRegistry::flush();

		$Model = ClassRegistry::init('Movie');
		$Model->Actor->Behaviors->load('Media.Transfer', $this->behaviorSettings);
		$file = $this->Data->getFile(array('image-jpg.jpg' => 'tc.jpg'));
		$data = array(
			'Movie' => array('title' => 'Changeling'),
			'Actor' => array(array('name' => 'John Malkovich', 'file' => $file)),
		);
		$this->assertTrue($Model->saveAll($data));
		$this->assertTrue(file_exists($file));
		$expected = $this->Data->settings['transfer'] . 'img' . DS . 'tc.jpg';
		$this->assertEqual($Model->Actor->transferred(), $expected);
		$this->assertTrue(file_exists($expected));
	}

	public function testUrlRemoteToFileLocal() {
		if ($this->skipIf(!$this->_remoteAvailable, 'Remote server not available.')) {
			return;
		}

		$Model = ClassRegistry::init('Movie');
		$Model->Behaviors->load('Media.Transfer', $this->behaviorSettings);

		$item = array('title' => 'Spiderman I', 'file' => 'http://cakephp.org/img/cake-logo.png');
		$Model->create();
		$this->assertTrue(!!$Model->save($item));
		$expected = $this->Data->settings['transfer'] . 'img' . DS . 'cake_logo.png';
		$this->assertEqual($Model->transferred(), $expected);
		$this->assertTrue(file_exists($expected));

		$Model = ClassRegistry::init('TheVoid');
		$Model->Behaviors->load('Media.Transfer', $this->behaviorSettings);

		$file = 'http://cakephp.org/img/cake-logo.png';
		$expected = $this->Data->settings['transfer'] . 'img' . DS . 'cake_logo_2.png';
		$this->assertEquals($Model->transfer($file), $expected);
		$this->assertEquals($Model->transferred(), $expected);
		$this->assertTrue(file_exists($expected));
	}

	public function testTrustClient() {
		$Model = ClassRegistry::init('TheVoid');
		$Model->Behaviors->load('Media.Transfer', $this->behaviorSettings);

		$file = $this->Data->getFile(array('image-jpg.jpg' => 'ta.jpg'));
		$Model->transfer($file);
		$result = $Model->Behaviors->Transfer->runtime['TheVoid']['source']['mimeType'];
		$this->assertIdentical($result, 'image/jpeg');
		$result = $Model->Behaviors->Transfer->runtime['TheVoid']['destination']['mimeType'];
		$this->assertNull($result);

		$Model = ClassRegistry::init('TheVoid');
		$Model->Behaviors->load('Media.Transfer', array(
			'trustClient' => true
		) + $this->behaviorSettings);

		$file = $this->Data->getFile(array('image-jpg.jpg' => 'tb.jpg'));
		$Model->transfer($file);
		$result = $Model->Behaviors->Transfer->runtime['TheVoid']['source']['mimeType'];
		$this->assertIdentical($result, 'image/jpeg');
		$result = $Model->Behaviors->Transfer->runtime['TheVoid']['destination']['mimeType'];
		$this->assertIdentical($result, 'image/jpeg');
	}

	public function testTrustClientRemote() {
		if ($this->skipIf(!$this->_remoteAvailable, 'Remote server not available.')) {
			return;
		}

		$Model = ClassRegistry::init('TheVoid');
		$Model->Behaviors->load('Media.Transfer', $this->behaviorSettings);

		$file = 'http://cakephp.org/img/cake-logo.png';
		$Model->transfer($file);
		$result = $Model->Behaviors->Transfer->runtime['TheVoid']['source']['mimeType'];
		$this->assertNull($result);
		$result = $Model->Behaviors->Transfer->runtime['TheVoid']['destination']['mimeType'];
		$this->assertNull($result);

		$Model = ClassRegistry::init('TheVoid');
		$Model->Behaviors->load('Media.Transfer', array(
			'trustClient' => true
		) + $this->behaviorSettings);

		$file = 'http://cakephp.org/img/cake-logo.png';
		$Model->transfer($file);
		$result = $Model->Behaviors->Transfer->runtime['TheVoid']['source']['mimeType'];
		$this->assertIdentical($result, 'image/png');
		$result = $Model->Behaviors->Transfer->runtime['TheVoid']['destination']['mimeType'];
		$this->assertIdentical($result, 'image/png');
	}

	public function testAlternativeFile() {
		$Model = ClassRegistry::init('TheVoid');
		$Model->Behaviors->load('Media.TestTransfer', $this->behaviorSettings);
		$file = $this->Data->settings['base'] . 'file.jpg';

		$result = $Model->Behaviors->TestTransfer->alternativeFile($file);
		$expected = $this->Data->settings['base'] . 'file.jpg';
		$this->assertEqual($result, $expected);

		touch($this->Data->settings['base'] . 'file.jpg');

		$result = $Model->Behaviors->TestTransfer->alternativeFile($file);
		$expected = $this->Data->settings['base'] . 'file_2.jpg';
		$this->assertEqual($result, $expected);

		touch($this->Data->settings['base'] . 'file_2.jpg');

		$result = $Model->Behaviors->TestTransfer->alternativeFile($file);
		$expected = $this->Data->settings['base'] . 'file_3.jpg';
		$this->assertEqual($result, $expected);

		touch($this->Data->settings['base'] . 'file_3.png');

		$result = $Model->Behaviors->TestTransfer->alternativeFile($file);
		$expected = $this->Data->settings['base'] . 'file_4.jpg';
		$this->assertEqual($result, $expected);

		touch($this->Data->settings['base'] . 'file_80.jpg');

		$result = $Model->Behaviors->TestTransfer->alternativeFile($file);
		$expected = $this->Data->settings['base'] . 'file_4.jpg';
		$this->assertEqual($result, $expected);

		touch($this->Data->settings['base'] . 'file_4.jpg');

		$result = $Model->Behaviors->TestTransfer->alternativeFile($file, 4);
		$this->assertFalse($result);
	}

}
