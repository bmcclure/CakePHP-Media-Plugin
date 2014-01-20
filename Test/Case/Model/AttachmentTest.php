<?php
/**
 * Attachment Test Case File
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
 * @package       Media.Test.Case.Model
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

App::uses('Attachment', 'Media.Model');

require_once dirname(dirname(__FILE__)) . DS . 'constants.php';
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DS . 'Config' . DS . 'bootstrap.php';
require_once dirname(dirname(dirname(__FILE__))) . DS . 'Fixture' . DS . 'TestData.php';
require_once 'models.php';
require_once 'Media/Process.php';
require_once 'Media/Info.php';

/**
 * Attachment Test Case Class
 *
 * @package       Media.Test.Case.Model
 */
class AttachmentTest extends CakeTestCase {

	public $fixtures = array(
		'plugin.media.movie',
		'plugin.media.actor',
		'plugin.media.attachment',
		'plugin.media.pirate'
	);

/**
 * @var TestData
 */
	public $Data;

	public function setUp() {
		parent::setUp();

		$this->Data = new TestData();
	}

	public function tearDown() {
		parent::tearDown();

		$this->Data->cleanUp();
		ClassRegistry::flush();
	}

	public function testHasOne() {
		$Model = $this->_model('hasOne');

		$file = $this->Data->getFile(
			array('image-jpg.jpg' => $this->Data->settings['transfer'] . 'ta.jpg'
		));
		$data = array(
			'Movie'      => array('title' => 'Weekend', 'director' => 'Jean-Luc Godard'),
			'Attachment' => array('file' => $file, 'model' => 'Movie')
		);

		$Model->create();
		$this->assertTrue($Model->saveAll($data, array('validate' => 'first')));
		$file = $Model->Attachment->transferred();
		$this->assertTrue(file_exists($file));

		$result = $Model->find('first', array('conditions' => array('title' => 'Weekend')));
		$expected = array(
			'id'          => '1',
			'model'       => 'Movie',
			'foreign_key' => '4',
			'dirname'     => 'img',
			'basename'    => 'ta.jpg',
			'checksum'    => '073addc9c90e4d20a9a19d8a31e01b39',
			'group'       => null,
			'alternative' => null,
			'path'        => 'img/ta.jpg'
		);
		$this->assertEqual($result['Attachment'], $expected);

		$result = $Model->delete($Model->getLastInsertID());
		$this->assertTrue($result);
		$this->assertFalse(file_exists($this->Data->settings['transfer'] . 'img' . DS . 'ta.jpg'));
	}

	public function testHasMany() {
		$Model = $this->_model('hasMany');

		$fileA = $this->Data->getFile(array('image-jpg.jpg' => 'ta.jpg'));
		$fileB = $this->Data->getFile(array('image-png.png' => 'tb.png'));
		$data = array(
			'Movie'      => array('title' => 'Weekend', 'director' => 'Jean-Luc Godard'),
			'Attachment' => array(
				array('file' => $fileA, 'model' => 'Movie'),
				array('file' => $fileB, 'model' => 'Movie')
			)
		);

		$Model->create();
		$result = $Model->saveAll($data, array('validate' => 'first'));
		$this->assertTrue($result);
		$this->assertTrue(file_exists($this->Data->settings['transfer'] . 'img' . DS . 'ta.jpg'));
		$this->assertTrue(file_exists($this->Data->settings['transfer'] . 'img' . DS . 'tb.png'));

		$result = $Model->find('first', array('conditions' => array('title' => 'Weekend')));
		$expected = array(
			0 => array(
				'id'          => '1',
				'model'       => 'Movie',
				'foreign_key' => '4',
				'dirname'     => 'img',
				'basename'    => 'ta.jpg',
				'checksum'    => '073addc9c90e4d20a9a19d8a31e01b39',
				'group'       => null,
				'alternative' => null,
				'path'        => 'img/ta.jpg'
			),
			1 => array(
				'id'          => '2',
				'model'       => 'Movie',
				'foreign_key' => '4',
				'dirname'     => 'img',
				'basename'    => 'tb.png',
				'checksum'    => '0ec724ac451b85f09e4652d29eff943e',
				'group'       => null,
				'alternative' => null,
				'path'        => 'img/tb.png'
			)
		);
		$this->assertEqual($result['Attachment'], $expected);

		$result = $Model->delete($Model->getLastInsertID());
		$this->assertTrue($result);
		$this->assertFalse(file_exists($this->Data->settings['transfer'] . 'ta.jpg'));
		$this->assertFalse(file_exists($this->Data->settings['transfer'] . 'tb.jpg'));
	}

	public function testHasManyWithMissingMediaAdapters() {
		$_backupConfig = Configure::read('Media');
		$_backupProcess = Media_Process::config();
		$_backupInfo = Media_Info::config();

		$s = array('convert' => 'image/png', 'zoomCrop' => array(100, 100));
		$m = array('convert' => 'image/png', 'fitCrop' => array(300, 300));
		$l = array('convert' => 'image/png', 'fit' => array(600, 440));

		Configure::write('Media.filter', array(
			'audio'    => compact('s', 'm'),
			'document' => compact('s', 'm'),
			'generic'  => array(),
			'image'    => compact('s', 'm', 'l'),
			'video'    => compact('s', 'm')
		));

		Media_Process::config(array('image' => null));
		Media_Info::config(array('image' => null));

		$Model = $this->_model('hasMany');

		$file = $this->Data->getFile(array('image-jpg.jpg' => 'ta.jpg'));
		$data = array(
			'Movie'      => array('title' => 'Weekend', 'director' => 'Jean-Luc Godard'),
			'Attachment' => array(
				array('file' => $file, 'model' => 'Movie'),
			)
		);

		$Model->create();
		$result = false;
		$expected = null;

		try {
			$result = $Model->saveAll($data, array('validate' => 'first'));
		} catch (Exception $exception) {
			$expected = $exception;
		}
		if ($expected === null) {
			$this->fail('Expected Model::saveAll to raise an error.');
		}

		$this->assertFalse($result);
		$this->assertTrue(file_exists($this->Data->settings['transfer'] . 'img' . DS . 'ta.jpg'));

		$result = $Model->find('first', array('conditions' => array('title' => 'Weekend')));
		$expected = array(
			0 => array(
				'id'          => '1',
				'model'       => 'Movie',
				'foreign_key' => '4',
				'dirname'     => 'img',
				'basename'    => 'ta.jpg',
				'checksum'    => '073addc9c90e4d20a9a19d8a31e01b39',
				'group'       => null,
				'alternative' => null,
				'path'        => 'img/ta.jpg'
			)
		);
		$this->assertEqual($result['Attachment'], $expected);

		Media_Process::config($_backupProcess);
		Media_Info::config($_backupInfo);
		Configure::write('Media', $_backupConfig);
	}

	public function testGroupedHasMany() {
		$assoc = array(
			'Photo' => array(
				'className'  => 'Media.Attachment',
				'foreignKey' => 'foreign_key',
				'conditions' => array('Photo.model' => 'Movie', 'Photo.group' => 'photo'),
				'dependent'  => true
			)
		);
		$Model = $this->_model('hasMany', $assoc);

		$fileA = $this->Data->getFile(array('image-png.png' => 'ta.png'));
		$fileB = $this->Data->getFile(array('image-png.png' => 'tb.png'));
		$data = array(
			'Movie' => array('title' => 'Weekend', 'director' => 'Jean-Luc Godard'),
			'Photo' => array(
				array('file' => $fileA, 'model' => 'Movie', 'group' => 'photo'),
				array('file' => $fileB, 'model' => 'Movie', 'group' => 'photo'),
		));

		$Model->create();
		$result = $Model->saveAll($data, array('validate' => 'first'));
		$this->assertTrue($result);
		$this->assertTrue(file_exists($this->Data->settings['transfer'] . 'img' . DS . 'ta.png'));
		$this->assertTrue(file_exists($this->Data->settings['transfer'] . 'img' . DS . 'tb.png'));

		$result = $Model->find('first', array('conditions' => array('title' => 'Weekend')));
		$expected = array(
			'Movie' => array(
				'id'       => '4',
				'title'    => 'Weekend',
				'director' => 'Jean-Luc Godard',
			),
			'Actor' => array(),
			'Photo' => array(
				0 => array(
					'id'          => '1',
					'model'       => 'Movie',
					'foreign_key' => '4',
					'dirname'     => 'img',
					'basename'    => 'ta.png',
					'checksum'    => '0ec724ac451b85f09e4652d29eff943e',
					'group'       => 'photo',
					'alternative' => null,
					'path'        => 'img/ta.png'
				),
				1 => array(
					'id'          => '2',
					'model'       => 'Movie',
					'foreign_key' => '4',
					'dirname'     => 'img',
					'basename'    => 'tb.png',
					'checksum'    => '0ec724ac451b85f09e4652d29eff943e',
					'group'       => 'photo',
					'alternative' => null,
					'path'        => 'img/tb.png'
				)
			)
		);
		$this->assertEqual($result, $expected);

		$result = $Model->delete($Model->getLastInsertID());
		$this->assertTrue($result);
		$this->assertFalse(file_exists($this->Data->settings['transfer'] . 'photo' . DS . 'ta.png'));
		$this->assertFalse(file_exists($this->Data->settings['transfer'] . 'photo' . DS . 'tb.png'));
	}

	protected function _model($assocType, $assoc = null) {
		$Model = ClassRegistry::init('Movie');

		if ($assoc === null) {
			$assoc = array(
				'Attachment' => array(
					'className'  => 'Media.Attachment',
					'foreignKey' => 'foreign_key',
					'conditions' => array('Attachment.model' => 'Movie'),
					'dependent'  => true,
			));
		}
		$Model->bindModel(array($assocType => $assoc), false);
		$assocModelName = key($assoc);

		$Model->{$assocModelName}->validate['file']['location']['rule'][1][] = $this->Data->settings['base'];

		$Model->{$assocModelName}->Behaviors->load('Media.Transfer', array(
			'transferDirectory' => $this->Data->settings['transfer']
		));
		$Model->{$assocModelName}->Behaviors->load('Media.Generator', array(
			'baseDirectory'   => $this->Data->settings['transfer'],
			'filterDirectory' => $this->Data->settings['filter']
		));
		$Model->{$assocModelName}->Behaviors->load('Media.Coupler');
		$Model->{$assocModelName}->Behaviors->load('Media.Polymorphic');
		$Model->{$assocModelName}->Behaviors->load('Media.Meta', array(
			'level' => 2
		));
		return $Model;
	}

}
