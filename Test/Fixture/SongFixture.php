<?php
/**
 * Song Fixture File
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
 * @package       Media.Test.Fixture
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Song Fixture Class
 *
 * @package       Media.Test.Fixture
 */
class SongFixture extends CakeTestFixture {

	public $name = 'Song';

	public $fields = array(
		'id'       => array('type' => 'integer', 'key' => 'primary'),
		'dirname'  => array('type' => 'string', 'null' => true),
		'basename' => array('type' => 'string'),
		'checksum' => array('type' => 'string', 'null' => true),
		'indexes'  => array('PRIMARY' => array('column' => 'id', 'unique' => 1))
	);

	public $records = array(
		array(
			'id'       => 1,
			'dirname'  => 'static/img',
			'basename' => 'image-png.png',
			'checksum' => '7f9af648b511f2c83b1744f42254983f'
		),
		array(
			'id'       => 2,
			'dirname'  => 'static/img',
			'basename' => 'image-jpg.jpg',
			'checksum' => '1920c29e7fbe4d1ad2f9173ef4591133'
		),
		array(
			'id'       => 3,
			'dirname'  => 'static/txt',
			'basename' => 'text-plain.txt',
			'checksum' => '3f3f58abd4209b4c87be51018fe5a0c6'
		),
		array(
			'id'       => 4,
			'dirname'  => 'static/txt',
			'basename' => 'non-existent.txt',
			'checksum' => null
		)
	);

}
