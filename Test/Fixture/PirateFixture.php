<?php
/**
 * Pirate Fixture File
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
 * Pirate Fixture Class
 *
 * @package       Media.Test.Fixture
 */
class PirateFixture extends CakeTestFixture {

	public $name = 'Pirate';

	public $fields = array(
		'id'    => array('type' => 'integer', 'key' => 'primary'),
		'name'  => array('type' => 'string', 'null' => true),
		'group' => array('type' => 'string'),
		'model' => array('type' => 'string'),
	);

	public $records = array(
		array(
			'id'    => 1,
			'name'  => 'George Lowther',
			'group' => 'atlantic',
			'model' => 'unknown'
		)
	);

}
