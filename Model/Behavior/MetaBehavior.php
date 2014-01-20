<?php
/**
 * Meta Behavior File
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
 * @package       Media.Model.Behavior
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

App::uses('Cache', 'Cache');
App::uses('Inflector', 'Utility');
App::uses('ModelBehavior', 'Model');

require_once 'Mime/Type.php';
require_once 'Media/Info.php';

/**
 * Meta Behavior Class
 *
 * If you set metadataLevel to a value greater then zero, you’ll get additional
 * metadata on each consecutive find operation.
 *
 * To manually get metadata or generate versions of a certain file just call the
 * metadata or make method on the model which the behavior is attached to. This
 * even works if you don’t use the above table schema.
 *
 * {{{
 *      $result = $this->Document->metadata('/tmp/cern.jpg', 2);
 * }}}
 *
 * @package       Media.Model.Behavior
 */
class MetaBehavior extends ModelBehavior {

/**
 * Cache configuration.
 *
 * config
 *   The name of the cache configuration to use
 *
 * keyPrefix
 *   The prefix to use for the cache data identifier
 *
 * @var array
 */
	public static $cacheConfig = array(
		'config'    => 'default',
		'keyPrefix' => 'media_metadata_'
	);

/**
 * Default settings
 *
 * metadataLevel
 *  0 - (disabled) No retrieval of additional metadata
 *  1 - (basic) Adds `mime_type` and `size` fields
 *  2 - (detailed) Queries an `Media_Info` object for all available fields
 *
 * @var array
 */
	protected $_defaultSettings = array(
		'level' => 1
	);

/**
 * Holds cached metadata keyed by model alias
 *
 * @var array
 * @access private
 */
	protected $__cached = array();

/**
 * Setup behavior settings and cached metadata for the current model
 *
 * @param Model $Model
 * @param array $settings See defaultSettings for configuration options
 * @return void
 */
	public function setup(Model $Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = $this->_defaultSettings;
		}

		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array)$settings);

		extract(MetaBehavior::$cacheConfig);
		/* @var $config string */
		/* @var $keyPrefix string */
		$this->__cached[$Model->alias] = Cache::read($keyPrefix . $Model->alias, $config);
	}

/**
 * Write cached data on a per model basis
 *
 * @return void
 */
	public function __destruct() {
		extract(MetaBehavior::$cacheConfig);
		/* @var $config string */
		/* @var $keyPrefix string */

		foreach ($this->__cached as $alias => $data) {
			if ($data) {
				Cache::write($keyPrefix . $alias, $data, $config);
			}
		}
	}

/**
 * Callback
 *
 * Adds metadata to be stored in table if a record is about to be created.
 *
 * @param Model $Model Model using this behavior
 * @param array $options Options passed from Model::save().
 * @return mixed False if the operation should abort. Any other result will continue.
 */
	public function beforeSave(Model $Model, $options = array()) {
		if (!isset($Model->data[$Model->alias]['file'])) {
			return true;
		}
		extract($this->settings[$Model->alias]);
		/* @var $level integer */

		$Model->data[$Model->alias] += $this->metadata(
			$Model, $Model->data[$Model->alias]['file'], $level
		);
		return true;
	}

/**
 * Callback
 *
 * Adds metadata of corresponding file to each result.
 *
 * @param Model $Model Model using this behavior
 * @param mixed $results The results of the find operation
 * @param boolean $primary Whether this model is being queried directly (vs. being queried as an association)
 * @return mixed An array value will replace the value of $results - any other value will be ignored.
 */
	public function afterFind(Model $Model, $results, $primary = false) {
		if (empty($results)) {
			return $results;
		}
		extract($this->settings[$Model->alias]);
		/* @var $level integer */

		foreach ($results as &$result) {
			if (!isset($result[$Model->alias]['file'])) {
				continue;
			}
			$metadata = $this->metadata($Model, $result[$Model->alias]['file'], $level);

			if ($metadata) {
				$result[$Model->alias] = array_merge($result[$Model->alias], $metadata);
			}
		}
		return $results;
	}

/**
 * Retrieve (cached) metadata of a file
 *
 * @param Model $Model
 * @param string $file An absolute path to a file
 * @param integer $level level of amount of info to add, `0` disable, `1` for basic, `2` for detailed info
 * @return mixed Array with results or false if file is not readable
 */
	public function metadata(Model $Model, $file, $level = 1) {
		if ($level < 1) {
			return array();
		}
		$File = new File($file);

		if (!$File->readable()) {
			return false;
		}
		$checksum = $File->md5(true);

		$data = array();

		if (isset($this->__cached[$Model->alias][$checksum])) {
			$data = $this->__cached[$Model->alias][$checksum];
		}

		if ($level > 0 && !isset($data[1])) {
			$data[1] = array(
				'size'      => $File->size(),
				'mime_type' => Mime_Type::guessType($File->pwd()),
				'checksum'  => $checksum,
			);
		}
		if ($level > 1 && !isset($data[2])) {
			$data[2] = array();

			try {
				$Info = Media_Info::factory(array('source' => $File->pwd()));

				foreach ($Info->all() as $key => $value) {
					$data[2][Inflector::underscore($key)] = $value;
				}
			} catch (Exception $E) {
			}
		}

		for ($i = $level, $result = array(); $i > 0; $i--) {
			$result = array_merge($result, $data[$i]);
		}
		$this->__cached[$Model->alias][$checksum] = $data;
		return $result;
	}

}
