<?php
/**
 * Meta Behavior Test Case File
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

App::uses('Set', 'Utility');
App::uses('MetaBehavior', 'Media.Model/Behavior');

require_once dirname(dirname(dirname(__FILE__))) . DS . 'constants.php';
require_once dirname(__FILE__) . DS . 'BehaviorTestBase.php';

/**
 * Meta Behavior Test Case Class
 *
 * @package       Media.Test.Case.Model.Behavior
 */
class MetaBehaviorTest extends BaseBehaviorTest {

	public $record1File;

	public $oldCacheConfig;

	public function setUp() {
		parent::setUp();

		$this->behaviorSettings = array(
			'Coupler' => array(
				'baseDirectory' => $this->Data->settings['base']
			),
			'Meta' => array(
				'level' => 1
			)
		);

		$this->record1File = $this->Data->getFile(array(
			'image-png.png' => $this->Data->settings['static'] . 'img/image-png.png'
		));

		$this->oldCacheConfig = MetaBehavior::$cacheConfig;
		MetaBehavior::$cacheConfig['keyPrefix'] = 'media_metadata_test_';

		extract(MetaBehavior::$cacheConfig);
		/* @var $config string */
		/* @var $keyPrefix string */
		Cache::delete($keyPrefix . 'Song', $config);
	}

	public function tearDown() {
		parent::tearDown();
		MetaBehavior::$cacheConfig = $this->oldCacheConfig;
	}

	public function testSetup() {
		$Model = ClassRegistry::init('TheVoid');
		$Model->Behaviors->load('Media.Meta');

		$Model = ClassRegistry::init('Song');
		$Model->Behaviors->load('Media.Meta');
	}

	public function testSave() {
		$Model = ClassRegistry::init('Song');
		$Model->Behaviors->load('Media.Meta', $this->behaviorSettings['Meta']);

		$data = array('Song' => array('file' => $this->record1File));
		$this->assertTrue(!!$Model->save($data));
		$Model->Behaviors->unload('Media.Meta');

		$id = $Model->getLastInsertID();
		$result = $Model->findById($id);
		$Model->delete($id);
		$this->assertEqual($result['Song']['checksum'], md5_file($this->record1File));
	}

	public function testFind() {
		$Model = ClassRegistry::init('Song');
		$Model->Behaviors->load('Media.Coupler', $this->behaviorSettings['Coupler']);
		$Model->Behaviors->load('Media.Meta', $this->behaviorSettings['Meta']);
		$result = $Model->find('all');
		$this->assertEqual(count($result), 4);

		/* Virtual */
		$result = $Model->findById(1);
		$this->assertTrue(Set::matches('/Song/size', $result));
		$this->assertTrue(Set::matches('/Song/mime_type', $result));
	}

	public function testRegenerate() {
		$Model = ClassRegistry::init('Song');
		$Model->Behaviors->load('Media.Meta', $this->behaviorSettings['Meta']);

		$data = array('Song' => array('file' => $this->record1File));
		$this->assertTrue(!!$Model->save($data));
		$Model->Behaviors->unload('Media.Meta');

		$id = $Model->getLastInsertID();
		$result = $Model->findById($id);
		$checksum = $result['Song']['checksum'];
		$this->assertEqual($result['Song']['checksum'], md5_file($this->record1File));

		$Model->Behaviors->load('Media.Meta', $this->behaviorSettings['Meta']);

		$file = $this->Data->getFile(
			array('image-jpg.jpg' => $this->Data->settings['transfer'] . 'ta.jpg')
		);

		$data = array('Song' => array('id' => $id, 'file' => $file));
		$this->assertTrue(!!$Model->save($data));
		$Model->Behaviors->unload('Media.Meta');

		$result = $Model->findById($id);
		$this->assertNotEquals($result['Song']['checksum'], $checksum);
		$this->assertEqual($result['Song']['checksum'], md5_file($file));
	}

	public function testMetadata() {
		$Model = ClassRegistry::init('Song');
		$Model->Behaviors->load('Media.Meta', $this->behaviorSettings['Meta']);

		$result = $Model->metadata($this->record1File, 0);
		$expected = array();
		$this->assertEqual($result, $expected);

		$result = $Model->metadata($this->record1File, 1);
		$expected = array(
			'size'      => 2032,
			'mime_type' => 'image/png',
			'checksum'  => '0ec724ac451b85f09e4652d29eff943e',
		);
		$this->assertEqual($result, $expected);

		$result = $Model->metadata($this->record1File, 2);
		$expected = array(
			'ratio'       => 1.2962962962963,
			'known_ratio' => '4:3',
			'megapixel'   => 0,
			'quality'     => 1,
			'width'       => 70,
			'height'      => 54,
			'bits'        => 8,
			'size'        => 2032,
			'mime_type'   => 'image/png',
			'checksum'    => '0ec724ac451b85f09e4652d29eff943e',
		);
		$this->assertEqual($result, $expected);
	}

	public function testCaching() {
		extract(MetaBehavior::$cacheConfig);
		/* @var $config string */
		/* @var $keyPrefix string */

		$Model = ClassRegistry::init('Song');
		$Model->Behaviors->load('Media.Meta', $this->behaviorSettings['Meta']);
		$Model->metadata($this->record1File, 2);

		// remove all references so that the destructor is triggered
		$Model->Behaviors->unload('Media.Meta');
		ClassRegistry::removeObject('Media..MetaBehavior'); // https://github.com/cakephp/cakephp/issues/2306
		ClassRegistry::removeObject('MetaBehavior');

		$result = Cache::read($keyPrefix . $Model->alias, $config);
		$expected = array(
			'0ec724ac451b85f09e4652d29eff943e' => array(
				1 => array(
					'size'      => 2032,
					'mime_type' => 'image/png',
					'checksum'  => '0ec724ac451b85f09e4652d29eff943e'
				),
				2 => array(
					'ratio'       => 1.2962962962963,
					'known_ratio' => '4:3',
					'megapixel'   => 0,
					'quality'     => 1,
					'width'       => 70,
					'height'      => 54,
					'bits'        => 8
				)
			)
		);
		$this->assertEqual($result, $expected);

		$expected['0ec724ac451b85f09e4652d29eff943e'][2]['foo'] = 'bar';
		Cache::write($keyPrefix . $Model->alias, $expected, $config);

		$Model->Behaviors->load('Media.Meta', $this->behaviorSettings['Meta']);

		$result = $Model->metadata($this->record1File, 2);
		$expected = array(
			'ratio'       => 1.2962962962963,
			'known_ratio' => '4:3',
			'megapixel'   => 0,
			'quality'     => 1,
			'width'       => 70,
			'height'      => 54,
			'bits'        => 8,
			'foo'         => 'bar',
			'size'        => 2032,
			'mime_type'   => 'image/png',
			'checksum'    => '0ec724ac451b85f09e4652d29eff943e',
		);
		$this->assertEqual($result, $expected);
	}

}
