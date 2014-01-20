<?php
/**
 * Attachment Fixture File
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
 * Attachment Fixture Class
 *
 * @package       Media.Test.Fixture
 */
class AttachmentFixture extends CakeTestFixture {

	public $name = 'Attachment';

	public $fields = array(
		'id'          => array('type' => 'integer', 'key' => 'primary'),
		'model'       => array('type' => 'string'),
		'foreign_key' => array('type' => 'integer'),
		'dirname'     => array('type' => 'string', 'null' => true),
		'basename'    => array('type' => 'string'),
		'checksum'    => array('type' => 'string'),
		'group'       => array('type' => 'string', 'null' => true),
		'alternative' => array('type' => 'string', 'null' => true, 'length' => 50),
		'indexes'     => array('PRIMARY' => array('column' => 'id', 'unique' => 1))
	);

	public $records = array();

}
