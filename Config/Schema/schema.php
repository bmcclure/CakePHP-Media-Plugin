<?php
/**
 * Media Schema File
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
 * @package       Media.Config.Schema
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Media Schema Class
 *
 * @package       Media.Config.Schema
 */
class MediaSchema extends CakeSchema {

/**
 * before
 *
 * @param array $event
 * @return boolean
 */
	public function before($event = array()) {
		return true;
	}

/**
 * after
 *
 * @param array $event
 */
	public function after($event = array()) {
	}

/**
 * attachments
 *
 * @var array
 */
	public $attachments = array(
		'id'          => array('type' => 'integer', 'key' => 'primary'),
		'model'       => array('type' => 'string'),
		'foreign_key' => array('type' => 'integer', 'length' => 10),
		'dirname'     => array('type' => 'string', 'null' => true),
		'basename'    => array('type' => 'string'),
		'checksum'    => array('type' => 'string', 'null' => true),
		'group'       => array('type' => 'string', 'null' => true),
		'alternative' => array('type' => 'string', 'null' => true),
		'created'     => array('type' => 'datetime', 'null' => true),
		'modified'    => array('type' => 'datetime', 'null' => true),
		'indexes'     => array('PRIMARY' => array('column' => 'id', 'unique' => 1))
	);

}
