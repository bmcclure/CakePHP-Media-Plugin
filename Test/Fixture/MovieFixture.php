<?php
/**
 * Movie Fixture File
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
 * Movie Fixture Class
 *
 * @package       Media.Test.Fixture
 */
class MovieFixture extends CakeTestFixture {

	public $name = 'Movie';

	public $fields = array(
		'id'       => array('type' => 'integer', 'key' => 'primary'),
		'title'    => array('type' => 'string', 'null' => true),
		'director' => array('type' => 'string'),
		'indexes'  => array('PRIMARY' => array('column' => 'id', 'unique' => 1))
	);

	public $records = array(
		array(
			'id'       => 1,
			'title'    => 'Frost/Nixon',
			'director' => 'Ron Howard'
		),
		array(
			'id'       => 2,
			'title'    => 'Entre les murs',
			'director' => 'Laurent Cantet'
		),
		array(
			'id'       => 3,
			'title'    => 'Revanche',
			'director' => 'Goetz Spielmann'
		),
	);

}
