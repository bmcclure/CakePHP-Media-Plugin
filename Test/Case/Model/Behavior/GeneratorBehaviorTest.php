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

require_once dirname(dirname(dirname(__FILE__))) . DS . 'constants.php';
require_once dirname(__FILE__) . DS . 'BehaviorTestBase.php';

/**
 * Generator Behavior Test Case Class
 *
 * @package       Media.Test.Case.Model.Behavior
 */
class GeneratorBehaviorTest extends BaseBehaviorTest {

	public function setUp() {
		parent::setUp();

		$this->behaviorSettings = array(
			'baseDirectory'   => $this->Data->settings['base'],
			'filterDirectory' => $this->Data->settings['filter']
		);
	}

	public function testSetup() {
		$Model = ClassRegistry::init('TheVoid');
		$Model->Behaviors->load('Media.Generator');

		$Model = ClassRegistry::init('Song');
		$Model->Behaviors->load('Media.Generator');
	}

	public function testGlobalFilterViaConfigure() {
		Configure::write('Media.filter', array('image' => array(
			's' => array('convert' => 'image/png', 'fit' => array(5, 5)),
			'm' => array('convert' => 'image/png', 'fit' => array(10, 10))
		)));

		$Model = ClassRegistry::init('TheVoid');
		$Model->Behaviors->load('Media.Generator', $this->behaviorSettings);

		$file = $this->Data->getFile('image-jpg.jpg');
		$filter = $Model->filter($file);

		$expected = array(
			's' => array('convert' => 'image/png', 'fit' => array(5, 5)),
			'm' => array('convert' => 'image/png', 'fit' => array(10, 10))
		);

		$this->assertEqual($filter, $expected);
	}

	public function testPerModelFilterViaConfigure() {
		Configure::write('Media.filter.TheVoid', array('image' => array(
			's' => array('convert' => 'image/png', 'fit' => array(5, 5)),
			'm' => array('convert' => 'image/png', 'fit' => array(10, 10))
		)));

		$Model = ClassRegistry::init('TheVoid');
		$Model->Behaviors->load('Media.Generator', $this->behaviorSettings);

		$file = $this->Data->getFile('image-jpg.jpg');
		$filter = $Model->filter($file);

		$expected = array(
			's' => array('convert' => 'image/png', 'fit' => array(5, 5)),
			'm' => array('convert' => 'image/png', 'fit' => array(10, 10))
		);

		$this->assertEqual($filter, $expected);
	}

	public function testPerModelFilterViaModel() {
		Configure::write('Media.filter', array());

		$Model = ClassRegistry::init('TheVoid');
		$Model->Behaviors->load('Media.Generator', array(
			'filter' => array('image' => array(
				's' => array('convert' => 'image/png', 'fit' => array(5, 5)),
				'm' => array('convert' => 'image/png', 'fit' => array(10, 10))
			))
		) + $this->behaviorSettings);

		$file = $this->Data->getFile('image-jpg.jpg');
		$filter = $Model->filter($file);

		$expected = array(
			's' => array('convert' => 'image/png', 'fit' => array(5, 5)),
			'm' => array('convert' => 'image/png', 'fit' => array(10, 10))
		);

		$this->assertEqual($filter, $expected);
	}

	public function testDynamicallySetFilterForPerModelFilterViaConfigure() {
		Configure::write('Media.filter.FooBar', array('image' => array(
			's' => array('convert' => 'image/png', 'fit' => array(5, 5)),
			'm' => array('convert' => 'image/png', 'fit' => array(10, 10))
		)));

		$Model = ClassRegistry::init('TheVoid');
		$Model->Behaviors->load('Media.Generator', $this->behaviorSettings);
		$Model->setFilter('FooBar');

		$file = $this->Data->getFile('image-jpg.jpg');
		$filter = $Model->filter($file);

		$expected = array(
			's' => array('convert' => 'image/png', 'fit' => array(5, 5)),
			'm' => array('convert' => 'image/png', 'fit' => array(10, 10))
		);

		$this->assertEqual($filter, $expected);
	}

	public function testDynamicallySetFilterForPerModelFilterViaModel() {
		Configure::write('Media.filter', array());

		$Model = ClassRegistry::init('TheVoid');
		$Model->Behaviors->load('Media.Generator', $this->behaviorSettings);
		$Model->setFilter(array('image' => array(
				's' => array('convert' => 'image/png', 'fit' => array(5, 5)),
				'm' => array('convert' => 'image/png', 'fit' => array(10, 10))
			))
		);

		$file = $this->Data->getFile('image-jpg.jpg');
		$filter = $Model->filter($file);

		$expected = array(
			's' => array('convert' => 'image/png', 'fit' => array(5, 5)),
			'm' => array('convert' => 'image/png', 'fit' => array(10, 10))
		);

		$this->assertEqual($filter, $expected);
	}

	public function testDynamicallyUnsetFilterForPerModelFilterViaModel() {
		Configure::write('Media.filter', array('image' => array(
			's' => array('convert' => 'image/png', 'fit' => array(5, 5)),
			'm' => array('convert' => 'image/png', 'fit' => array(10, 10))
		)));

		$Model = ClassRegistry::init('TheVoid');
		$Model->Behaviors->load('Media.Generator', array(
			'filter' => array('image' => array(
				'l' => array('convert' => 'image/png', 'fit' => array(20, 20))
			))
		) + $this->behaviorSettings);
		$Model->setFilter(null);

		$file = $this->Data->getFile('image-jpg.jpg');
		$filter = $Model->filter($file);

		$expected = array(
			's' => array('convert' => 'image/png', 'fit' => array(5, 5)),
			'm' => array('convert' => 'image/png', 'fit' => array(10, 10))
		);

		$this->assertEqual($filter, $expected);
	}

	public function testMergeFilter() {
		Configure::write('Media.filter', array('image' => array(
			's' => array('convert' => 'image/png', 'fit' => array(5, 5)),
			'm' => array('convert' => 'image/png', 'fit' => array(10, 10))
		)));

		$Model = ClassRegistry::init('TheVoid');
		$Model->Behaviors->load('Media.Generator', array(
			'mergeFilter' => true,
			'filter' => array('audio' => array(
				'original' => array('clone' => 'copy')
			))
		) + $this->behaviorSettings);

		$file = $this->Data->getFile('image-jpg.jpg');
		$filter = $Model->filter($file);

		$expected = array(
			's' => array('convert' => 'image/png', 'fit' => array(5, 5)),
			'm' => array('convert' => 'image/png', 'fit' => array(10, 10))
		);

		$this->assertEqual($filter, $expected);
	}

	public function testNoModelTypeNameConflict() {
		Configure::write('Media.filter', array(
			'image' => array(
				'l' => array('convert' => 'image/png', 'fit' => array(20, 20))
			),
			'Image' => array(
				'image' => array(
					's' => array('convert' => 'image/png', 'fit' => array(5, 5)),
					'm' => array('convert' => 'image/png', 'fit' => array(10, 10))
				))
		));

		$Model = ClassRegistry::init('Image');
		$Model->Behaviors->load('Media.Generator', $this->behaviorSettings);

		$file = $this->Data->getFile(array('image-jpg.jpg' => 'ta.jpg'));
		$filter = $Model->filter($file);

		$expected = array(
			's' => array('convert' => 'image/png', 'fit' => array(5, 5)),
			'm' => array('convert' => 'image/png', 'fit' => array(10, 10))
		);

		$this->assertEqual($filter, $expected);
	}

	public function testNonExistentFilter() {
		Configure::write('Media.filter', array());

		$Model = ClassRegistry::init('TheVoid');
		$Model->Behaviors->load('Media.Generator', $this->behaviorSettings);

		$file = $this->Data->getFile('image-jpg.jpg');
		$filter = $Model->filter($file);

		$this->assertEqual($filter, array());
	}

	public function testMakeWithNoFiltersConfigured() {
		Configure::write('Media.filter', array());

		$Model = ClassRegistry::init('Song');
		$Model->Behaviors->load('Media.Generator', $this->behaviorSettings);

		$file = $this->Data->getFile(array('image-jpg.jpg' => 'ta.jpg'));

		$this->assertTrue($Model->make($file));
	}

	public function testMakeThroughModel() {
		Configure::write('Media.filter.image', array(
			's' => array('convert' => 'image/png', 'fit' => array(5, 5)),
			'm' => array('convert' => 'image/png', 'fit' => array(10, 10))
		));

		$Model = ClassRegistry::init('Unicorn', 'Model'); // has makeVersion mocked
		$Model->Behaviors->load('Media.Generator', array(
			'createDirectory' => true
		) + $this->behaviorSettings);

		$file = $this->Data->getFile(array(
			'image-jpg.jpg' => 'image-jpg.jpg'
		));

		$expected[] = array(
			$file,
			array(
				'directory'    => $this->Data->settings['filter'] . 's' . DS,
				'version'      => 's',
				'instructions' => array('convert' => 'image/png', 'fit' => array(5, 5))
		));
		$expected[] = array(
			$file,
			array(
				'directory'    => $this->Data->settings['filter'] . 'm' . DS,
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
		) + $this->behaviorSettings);

		$file = $this->Data->getFile(array(
			'image-jpg.jpg' => 'image-jpg.jpg'
		));

		$expected = null;
		try {
			$Model->make($file);
		} catch (Exception $exception) {
			$expected = $exception;
		}
		if ($expected === null) {
			$this->fail('Expected Model::make to raise an error.');
		}

		$this->assertFalse(is_dir($this->Data->settings['filter'] . 's'));
		$this->assertFalse(is_dir($this->Data->settings['filter'] . 'm'));

		$Model->Behaviors->load('Media.Generator', array(
			'createDirectory' => true
		) + $this->behaviorSettings);

		$Model->make($file);

		$this->assertTrue(is_dir($this->Data->settings['filter'] . 's'));
		$this->assertTrue(is_dir($this->Data->settings['filter'] . 'm'));
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
		) + $this->behaviorSettings);

		$file = $this->Data->getFile(array(
			'image-jpg.jpg' => 'image-jpg.jpg'
		));

		$Model->make($file);
		$this->assertEqual(decoct(fileperms($this->Data->settings['filter'] . 's')), 40755);

		$Model->Behaviors->load('Media.Generator', array(
			'createDirectoryMode' => 0777
		) + $this->behaviorSettings);

		$Model->make($file);
		$this->assertEqual(decoct(fileperms($this->Data->settings['filter'] . 's')), 40755);

		rmdir($this->Data->settings['filter'] . 's');

		$Model->make($file);
		$this->assertEqual(decoct(fileperms($this->Data->settings['filter'] . 's')), 40777);
	}

	public function testMakeVersion() {
		$config = Media_Process::config();

		$message = 'Need media processing adapter for image.';
		$skipped = $this->skipIf(!isset($config['image']), $message);

		if ($skipped) {
			return;
		}

		$Model = ClassRegistry::init('Unicorn', 'Model');
		$Model->Behaviors->load('Media.Generator', $this->behaviorSettings);

		$file = $this->Data->getFile(array(
			'image-jpg.jpg' => 'image-jpg.jpg'
		));
		$directory = $this->Data->settings['filter'] . 's' . DS;
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

	public function testMakeVersionAcrossMedia() {
		$config = Media_Process::config();

		$message = 'Need media processing adapters configured for both image and document.';
		$skipped = $this->skipIf(!isset($config['image'], $config['document']), $message);

		if ($skipped) {
			return;
		}

		$Model = ClassRegistry::init('Unicorn', 'Model');
		$Model->Behaviors->load('Media.Generator', $this->behaviorSettings);

		$file = $this->Data->getFile(array(
			'application-pdf.pdf' => 'application-pdf.pdf'
		));
		$directory = $this->Data->settings['filter'] . 's' . DS;
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
		$Model->Behaviors->load('Media.Generator', $this->behaviorSettings);

		$directory = $this->Data->settings['filter'] . 's' . DS;
		mkdir($directory);

		$file = $this->Data->getFile(array(
			'image-jpg.jpg' => 'copied.jpg'
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

		if ($this->skipIf($this->_isWindows(), 'For some reason on Windows symlink does not work, and hard links cannot be unlinked immediately.')) {
			return;
		}

		$file = $this->Data->getFile(array(
			'image-jpg.jpg' => 'symlinked.jpg'
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
			'image-jpg.jpg' => 'hardlinked.jpg'
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

	public function testMakeVersionUnknownMethodsArePassedThrough() {
		$config = Media_Process::config();

		$message = 'Need imagick media processing adapters configured for both image.';
		$skipped = $this->skipIf(!isset($config['image']) || $config['image'] != 'Imagick', $message);

		if ($skipped) {
			return;
		}

		$Model = ClassRegistry::init('Unicorn', 'Model');
		$Model->Behaviors->load('Media.Generator', $this->behaviorSettings);

		$directory = $this->Data->settings['filter'] . 's' . DS;
		mkdir($directory);

		$file = $this->Data->getFile(array(
			'image-jpg.jpg' => 'image.jpg'
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
