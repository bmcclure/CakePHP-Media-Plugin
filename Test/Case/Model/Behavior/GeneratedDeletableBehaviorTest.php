<?php
/**
 * Generated deletable behavior test case file
 *
 * PHP 5
 * CakePHP 2
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author        Oliver Nowak <info@nowak-media.de>
 * @package       Media.Test.Case.Model.Behaviour
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

require_once dirname(__FILE__) . DS . 'BehaviorTestBase.php';

/**
 * Generated deletable behavior test case
 *
 * @package       Media.Test.Case.Model.Behaviour
 */
class GeneratedDeletableBehaviorTest extends BaseBehaviorTest {

	public function testDeleteVersions() {
		Configure::write('Media.filter.image', array(
			's' => array('convert' => 'image/png'),
			'm' => array('convert' => 'image/png')
		));

		$Model = ClassRegistry::init('Song');
		$Model->Behaviors->load('Media.Transfer', array(
			'transferDirectory' => $this->Data->settings['transfer']
		));
		$Model->Behaviors->load('Media.Generator', array(
			'baseDirectory'   => $this->Data->settings['transfer'],
			'filterDirectory' => $this->Data->settings['filter']
		));
		$Model->Behaviors->load('Media.GeneratedDeletable');
		$Model->Behaviors->load('Media.Coupler', array(
			'baseDirectory' => $this->Data->settings['transfer']
		));

		$file = $this->Data->getFile(array('image-jpg.jpg' => 'ta.jpg'));
		$this->assertTrue(file_exists($file));

		$Model->create();
		$this->assertTrue(!!$Model->save(array('file' => $file)));
		$this->assertEqual($Model->transferred(), $this->Data->settings['transfer'] . 'img' . DS . 'ta.jpg');
		$this->assertTrue(file_exists($this->Data->settings['filter'] . 's' . DS . 'img' . DS . 'ta.png'));
		$this->assertTrue(file_exists($this->Data->settings['filter'] . 'm' . DS . 'img' . DS . 'ta.png'));

		$this->assertTrue($Model->delete($Model->getInsertID()));
		$this->assertFalse(file_exists($this->Data->settings['transfer'] . 'img' . DS . 'ta.jpg'));
		$this->assertFalse(file_exists($this->Data->settings['filter'] . 's' . DS . 'img' . DS . 'ta.png'));
		$this->assertFalse(file_exists($this->Data->settings['filter'] . 'm' . DS . 'img' . DS . 'ta.png'));
	}

}
