<?php
/**
 * Generator Behavior Test Case File
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

require_once dirname(__FILE__) . DS . 'BehaviorTestBase.php';

/**
 * Generator Behavior Test Case Class
 *
 * @package       Media.Test.Case.Model.Behavior
 */
class GeneratorBehaviorTest extends BaseBehaviorTest {

	protected $_backup;

	public function setUp() {
		parent::setUp();

		$this->_behaviorSettings = array(
			'baseDirectory' => $this->Folder->pwd(),
			'filterDirectory' => $this->Folder->pwd() . 'filter' . DS
		);
		$this->_backup['configMedia'] = Configure::read('Media');
	}

	public function tearDown() {
		parent::tearDown();
		Configure::write('Media', $this->_backup['configMedia']);
	}

	public function testSetup() {
		$Model = ClassRegistry::init('TheVoid');
		$Model->Behaviors->load('Media.Generator');

		$Model = ClassRegistry::init('Song');
		$Model->Behaviors->load('Media.Generator');
	}

	public function testMakeThroughModel() {
		Configure::write('Media.filter.image', array(
			's' => array('convert' => 'image/png', 'fit' => array(5, 5)),
			'm' => array('convert' => 'image/png', 'fit' => array(10, 10))
		));

		$Model = ClassRegistry::init('Unicorn', 'Model'); // has makeVersion mocked
		$Model->Behaviors->load('Media.Generator', array(
			'createDirectory' => true
		) + $this->_behaviorSettings);

		$file = $this->Data->getFile(array(
			'image-jpg.jpg' => $this->Folder->pwd() . 'image-jpg.jpg'
		));

		$expected[] = array(
			$file,
			array(
				'directory'    => $this->Folder->pwd() . 'filter' . DS . 's' . DS,
				'version'      => 's',
				'instructions' => array('convert' => 'image/png', 'fit' => array(5, 5))
		));
		$expected[] = array(
			$file,
			array(
				'directory'    => $this->Folder->pwd() . 'filter' . DS . 'm' . DS,
				'version'      => 'm',
				'instructions' => array('convert' => 'image/png', 'fit' => array(10, 10))
		));
		$Model->make($file);
		$this->assertEqual($Model->makeVersionArgs, $expected);
	}

	public function testCreateDirectory() {
		Configure::write('Media.filter.image', array(
			's' => array('convert' => 'image/png'),
			'm' => array('convert' => 'image/png')
		));

		$Model = ClassRegistry::init('Unicorn', 'Model');
		$Model->Behaviors->load('Media.Generator', array(
			'createDirectory' => false
		) + $this->_behaviorSettings);

		$file = $this->Data->getFile(array(
			'image-jpg.jpg' => $this->Folder->pwd() . 'image-jpg.jpg'
		));

		$expected = null;
		try
		{
			$Model->make($file);
		}
		catch(Exception $exception)
		{
			$expected = $exception;
		}
		if($expected === null)
		{
			$this->fail('Expected Model::make to raise an error.');
		}

		$this->assertFalse(is_dir($this->Folder->pwd() . 'filter' . DS . 's'));
		$this->assertFalse(is_dir($this->Folder->pwd() . 'filter' . DS . 'm'));

		$Model->Behaviors->load('Media.Generator', array(
			'createDirectory' => true
		) +  $this->_behaviorSettings);

		$Model->make($file);

		$this->assertTrue(is_dir($this->Folder->pwd() . 'filter' . DS . 's'));
		$this->assertTrue(is_dir($this->Folder->pwd() . 'filter' . DS . 'm'));
	}

	public function testCreateDirectoryMode() {
		if ($this->skipIf($this->_isWindows(), 'Modes are not supported on Windows.')) {
			return;
		}

		Configure::write('Media.filter.image', array(
			's' => array('convert' => 'image/png'),
		));

		$Model = ClassRegistry::init('Unicorn', 'Model');
		$Model->Behaviors->load('Media.Generator', array(
			'createDirectoryMode' => 0755
		) + $this->_behaviorSettings);

		$file = $this->Data->getFile(array(
			'image-jpg.jpg' => $this->Folder->pwd() . 'image-jpg.jpg'
		));

		$Model->make($file);
		$this->assertEqual(decoct(fileperms($this->Folder->pwd() . 'filter' . DS . 's')), 40755);

		$Model->Behaviors->load('Media.Generator', array(
			'createDirectoryMode' => 0777
		) + $this->_behaviorSettings);

		$Model->make($file);
		$this->assertEqual(decoct(fileperms($this->Folder->pwd() . 'filter' . DS . 's')), 40755);

		rmdir($this->Folder->pwd() . 'filter' . DS . 's');

		$Model->make($file);
		$this->assertEqual(decoct(fileperms($this->Folder->pwd() . 'filter' . DS . 's')), 40777);
	}

	public function testMakeVersion() {
		$config = Media_Process::config();

		$message = 'Need media processing adapter for image.';
		$skipped = $this->skipIf(!isset($config['image']), $message);

		if ($skipped) {
			return;
		}

		$Model = ClassRegistry::init('Unicorn', 'Model');
		$Model->Behaviors->load('Media.Generator', $this->_behaviorSettings);

		$file = $this->Data->getFile(array(
			'image-jpg.jpg' => $this->Folder->pwd() . 'image-jpg.jpg'
		));
		$directory = $this->Folder->pwd() . 'filter' . DS . 's' . DS;
		mkdir($directory);

		$result = $Model->Behaviors->Generator->makeVersion($Model, $file, array(
			'version'      => 's',
			'directory'    => $directory,
			'instructions' => array(
				'convert' => 'image/png'
			)
		));
		$this->assertTrue($result);
		$this->assertTrue(file_exists($directory . 'image-jpg.png'));
	}

	public function testMakeVersionAccrossMedia() {
		$config = Media_Process::config();

		$message = 'Need media processing adapters configured for both image and document.';
		$skipped = $this->skipIf(!isset($config['image'], $config['document']), $message);

		if ($skipped) {
			return;
		}

		$Model = ClassRegistry::init('Unicorn', 'Model');
		$Model->Behaviors->load('Media.Generator', $this->_behaviorSettings);

		$file = $this->Data->getFile(array(
			'application-pdf.pdf' => $this->Folder->pwd() . 'application-pdf.pdf'
		));
		$directory = $this->Folder->pwd() . 'filter' . DS . 's' . DS;
		mkdir($directory);

		$result = $Model->Behaviors->Generator->makeVersion($Model, $file, array(
			'version'      => 's',
			'directory'    => $directory,
			'instructions' => array(
				'convert' => 'image/png'
			)
		));
		$this->assertTrue($result);
		$this->assertTrue(file_exists($directory . 'application-pdf.png'));
	}

	public function testMakeVersionCloning() {
		$Model = ClassRegistry::init('Unicorn', 'Model');
		$Model->Behaviors->load('Media.Generator', $this->_behaviorSettings);

		$directory = $this->Folder->pwd() . 'filter' . DS . 's' . DS;
		mkdir($directory);

		$file = $this->Data->getFile(array(
			'image-jpg.jpg' => $this->Folder->pwd() . 'copied.jpg'
		));
		$result = $Model->Behaviors->Generator->makeVersion($Model, $file, array(
			'version'      => 's',
			'directory'    => $directory,
			'instructions' => array(
				'clone' => 'copy'
			)
		));
		$this->assertTrue($result);
		$this->assertTrue(file_exists($directory . 'copied.jpg'));
		$this->assertTrue(is_file($directory . 'copied.jpg'));

		if($this->skipIf($this->_isWindows(), 'For some reason on Windows symlink does not work, and hard links cannot be unlinked immediately.')) {
			return;
		}

		$file = $this->Data->getFile(array(
			'image-jpg.jpg' => $this->Folder->pwd() . 'symlinked.jpg'
		));
		$result = $Model->Behaviors->Generator->makeVersion($Model, $file, array(
			'version'      => 's',
			'directory'    => $directory,
			'instructions' => array(
				'clone' => 'symlink'
			)
		));
		$this->assertTrue($result);
		$this->assertTrue(file_exists($directory . 'symlinked.jpg'));
		$this->assertTrue(is_link($directory . 'symlinked.jpg'));
		$this->assertEqual(readlink($directory . 'symlinked.jpg'), $file);
		unlink($directory . 'symlinked.jpg');

		$file = $this->Data->getFile(array(
			'image-jpg.jpg' => $this->Folder->pwd() . 'hardlinked.jpg'
		));
		$result = $Model->Behaviors->Generator->makeVersion($Model, $file, array(
			'version'      => 's',
			'directory'    => $directory,
			'instructions' => array(
				'clone' => 'link'
			)
		));
		$this->assertTrue($result);
		$this->assertTrue(file_exists($directory . 'hardlinked.jpg'));
		$this->assertTrue(is_file($directory . 'hardlinked.jpg'));
		unlink($directory . 'hardlinked.jpg');
	}

	public function testMakeVersionUnkownMethodArePassedthru() {
		$config = Media_Process::config();

		$message = 'Need imagick media processing adapters configured for both image.';
		$skipped = $this->skipIf(!isset($config['image']) || $config['image'] != 'Imagick', $message);

		if ($skipped) {
			return;
		}

		$Model = ClassRegistry::init('Unicorn', 'Model');
		$Model->Behaviors->load('Media.Generator', $this->_behaviorSettings);

		$directory = $this->Folder->pwd() . 'filter' . DS . 's' . DS;
		mkdir($directory);

		$file = $this->Data->getFile(array(
			'image-jpg.jpg' => $this->Folder->pwd() . 'image.jpg'
		));
		$result = $Model->Behaviors->Generator->makeVersion($Model, $file, array(
			'version'      => 's',
			'directory'    => $directory,
			'instructions' => array(
				'setFormat' => 'png' // setFormat is an Imagick method.
			)
		));
		$this->assertTrue($result);

		$mimeType = Mime_Type::guessType($directory . 'image.jpg', array(
			'paranoid' => true
		));
		$this->assertEqual($mimeType, 'image/png');
	}

}
