<?php
/**
 * Transfer Behavior File
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

App::uses('HttpSocket', 'Network/Http');
App::uses('Folder', 'Utility');
App::uses('MediaValidation', 'Media.Lib');
App::uses('TransferValidation', 'Media.Lib');
App::uses('ModelBehavior', 'Model');

require_once 'Mime/Type.php';

/**
 * Transfer Behavior Class
 *
 * Takes care of transferring local and remote (via HTTP)
 * files or handling uploads received through a HTML form.
 *
 * The behavior can be operated in two different modes: automatically or manually.
 *
 * In automatic mode a transfer takes place when a record (of the model the
 * behavior is attached to) is saved and the record contains a field named
 * file. The value of field must be one of the transferable item listed below.
 * It does not have to be present in your model schema (it can be virtual). The
 * best thing is that you don’t even have to touch any controller code.
 *
 * {{{
 *     $this->Movie->save(array('Movie' => array('file' => $file)));
 * }}}
 *
 * This is what happens upon save: The file fields contents become the source of the
 * transfer. The source is then picked up and copied/moved to the resolved path
 * of the destinationFile. After the behavior’s beforeSave method has been run
 * the file field contains an absolute path to the transferred file.
 *
 * In manual mode (most often used with a tableless model) you’ve got full
 * control when the transfer actually takes place. You just call the transform
 * method passing the file as a parameter.
 *
 * {{{
 *     $this->Movie->perform($file);
 * }}}
 *
 * @see           TransferBehavior::beforeSave()
 * @see           TransferBehavior::transfer()
 * @package       Media.Model.Behavior
 */
class TransferBehavior extends ModelBehavior {

/**
 * Holds data between function calls keyed by model alias
 *
 * @var array
 */
	public $runtime = array();

/**
 * Default settings
 *
 * trustClient
 *   false -
 *   true  - Trust the MIME type submitted together with an upload
 *
 * transferDirectory
 *   string - An absolute path (with trailing slash) to a directory
 *
 *   The directory will be used as a basis for the relative path returned by
 *   transferTo(). This options defaults to MEDIA_TRANSFER which is defined in
 *   the plugin’s core.php.
 *
 * createDirectory
 *   false - Fail on missing directories
 *   true  - Recursively create missing directories
 *
 * alternativeFile
 *   integer - Specifies the maximum number of tries for finding an alternative destination file name
 *             (i.e. test_1.png, test_2.png, ...).
 *
 * overwrite
 *   false - Existing destination files with the same are not overridden, an alternative name is used
 *   true - Overwrites existing destination files with the same name
 *
 * @var array
 */
	protected $_defaultSettings = array(
		'trustClient'       => false,
		'transferDirectory' => MEDIA_TRANSFER,
		'createDirectory'   => true,
		'alternativeFile'   => 100,
		'overwrite'         => false
	);

/**
 * Default runtime
 *
 * @var array
 */
	protected $_defaultRuntime = array(
		'source'       => null,
		'temporary'    => null,
		'destination'  => null,
		'hasPerformed' => false
	);

/**
 * Setup
 *
 * Merges default settings with provided config and sets default validation options
 *
 * @see $_defaultSettings
 * @param Model $Model Model using this behavior
 * @param array $settings Configuration settings for $Model
 * @return void
 */
	public function setup(Model $Model, $settings = array()) {
		/* If present validation rules get some sane default values */
		if (isset($Model->validate['file'])) {
			$validate = &$Model->validate;

			if (!is_array($validate['file'])) {
				$validate['file'] = array(
					'rule' => $validate['file']
				);
			}

			if (is_array($validate['file']) && isset($validate['file']['rule'])) {
				$validate['file'] = array(
					$validate['file']['rule'] => $validate['file']
				);
			}

			$default = array('allowEmpty' => true, 'required' => false, 'last' => true);
			foreach ($validate['file'] as &$rule) {
				$rule = array_merge($default, $rule);
			}
		}

		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = $this->_defaultSettings;
		}

		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array)$settings);
		$this->runtime[$Model->alias] = $this->_defaultRuntime;
	}

/**
 * Run before any or if validation occurs
 *
 * Triggers `prepare()` setting source, temporary and destination to
 * enable validation rules to check the transfer. If that fails
 * invalidates the model.
 *
 * @param Model $Model Model using this behavior
 * @param array $options Options passed from Model::save().
 * @return mixed False or null will abort the operation. Any other result will continue.
 */
	public function beforeValidate(Model $Model, $options = array()) {
		if (!isset($Model->data[$Model->alias]['file'])) {
			return true;
		}
		$file = $Model->data[$Model->alias]['file'];

		if (TransferValidation::blank($file)) {
			$Model->data[$Model->alias]['file'] = null;
			return true;
		}

		if (!$this->_prepare($Model, $file)) {
			$Model->invalidate('file', 'error');
			return false;
		}
		return true;
	}

/**
 * Triggers `prepare()` and performs transfer
 *
 * If transfer is unsuccessful save operation will abort.
 *
 * @param Model $Model Model using this behavior
 * @param array $options Options passed from Model::save().
 * @return mixed False if the operation should abort. Any other result will continue.
 */
	public function beforeSave(Model $Model, $options = array()) {
		if (!isset($Model->data[$Model->alias]['file'])) {
			return true;
		}
		$file = $Model->data[$Model->alias]['file'];

		if (TransferValidation::blank($file)) {
			unset($Model->data[$Model->alias]['file']);
			return true;
		}

		if (!$file = $this->transfer($Model, $file)) {
			return false;
		}
		$Model->data[$Model->alias]['file'] = $file;
		return $Model->data[$Model->alias];
	}

/**
 * Retrieves metadata of any transferable resource
 *
 * @param Model $Model
 * @param array|string $resource Transfer resource
 * @return array|void
 */
	public function transferMeta(Model $Model, $resource) {
		extract($this->settings[$Model->alias]);
		/* @var $trustClient boolean */
		/* @var $transferDirectory string */
		/* @var $createDirectory boolean */
		/* @var $alternativeFile integer */
		/* @var $overwrite boolean */

		$defaultResource = array(
			'scheme'      => null,
			'host'        => null,
			'port'        => null,
			'file'        => null,
			'mimeType'    => null,
			'size'        => null,
			'pixels'      => null,
			'permission'  => null,
			'dirname'     => null,
			'basename'    => null,
			'filename'    => null,
			'extension'   => null,
			'type'        => null
		);

		/* Currently HTTP is supported only */
		if (TransferValidation::url($resource, array('scheme' => 'http'))) {
			$resource = array_merge(
				$defaultResource,
				pathinfo(parse_url($resource, PHP_URL_PATH)),
				array(
					'scheme' => parse_url($resource, PHP_URL_SCHEME),
					'host'   => parse_url($resource, PHP_URL_HOST),
					'port'   => parse_url($resource, PHP_URL_PORT),
					'file'   => $resource,
					'type'   => 'http-url-remote'
			));

			$Socket = new HttpSocket(array('timeout' => 5));
			$Socket->get($resource['file']);

			if (empty($Socket->error) && $Socket->response['status']['code'] == 200) {
				$resource = array_merge(
					$resource,
					array(
						'size'       => $Socket->response['header']['Content-Length'],
						'mimeType'   => $trustClient ? $Socket->response['header']['Content-Type'] : null,
						'permission' => '0004'
				));
			}
		} elseif (MediaValidation::file($resource, false)) {
			$resource = array_merge(
				$defaultResource,
				pathinfo($resource),
				array(
					'file' => $resource,
					'host' => 'localhost',
					'mimeType' => Mime_Type::guessType($resource, array('paranoid' => !$trustClient))
			));

			if (TransferValidation::uploadedFile($resource['file'])) {
				$resource['type'] = 'uploaded-file-local';
			} else {
				$resource['type'] = 'file-local';
			}

			if (is_readable($resource['file'])) {
				$resource = array_merge(
					$resource,
					array(
						'size'       => filesize($resource['file']),
						'permission' => substr(sprintf('%o', fileperms($resource['file'])), -4),
				));
				/*
				 * Because there is not better way to determine if resource is an image
				 * first, we suppress a warning that would be thrown here otherwise.
				 */
				if (function_exists('getimagesize')) {
					list($width, $height) = @getimagesize($resource['file']);
					$resource['pixels'] = $width * $height;
				}
			}
		} elseif (TransferValidation::fileUpload($resource)) {
			$resource = array_merge(
				$defaultResource,
				pathinfo($resource['name']),
				array(
					'file'       => $resource['name'],
					'host'       => env('REMOTE_ADDR'),
					'size'       => $resource['size'],
					'mimeType'   => $trustClient ? $resource['type'] : null,
					'permission' => '0004',
					'type'       => 'file-upload-remote'
			));
		} else {
			return null;
		}

		return $resource;
	}

/**
 * Returns a relative path to the destination file in order to determine
 * the final destination file name in combination with the transferDirectory
 * setting.
 *
 * Since there are many requirements to file name generation the behavior
 * implements these method which is given information about the temporary and
 * destination resource of the current transfer. You can reimplement this method
 * in your model which then will take precedence over the one provided by the
 * behavior. The method must return a relative path.
 *
 * The default implementation generates destination paths according to the pattern
 * <shortened media name>/<slugged filename>.<original extension>. However it is
 * also possible to do much more here, like correcting the extension using
 * Mime_Type::guessExtension() or multiple levels of subdirectories.
 *
 * Please note that the destination directory part of the returned path must be
 * existent (or creatable if createDirectory is enabled) and writable by the
 * server.
 *
 * @see _prepare()
 * @param Model $Model
 * @param array $via Information about the temporary resource (if used)
 * @param array $from Information about the source
 * @return string
 */
	public function transferTo(Model $Model, $via, $from) {
		extract($from);
		/* @var $scheme string */
		/* @var $host string */
		/* @var $port integer */
		/* @var $file string */
		/* @var $mimeType string */
		/* @var $size integer */
		/* @var $pixels integer */
		/* @var $permission string */
		/* @var $dirname string */
		/* @var $basename string */
		/* @var $filename string */
		/* @var $extension string */
		/* @var $error array */

		$irregular = array(
			'image' => 'img',
			'text' => 'txt'
		);
		$name = Mime_Type::guessName($mimeType ? $mimeType : $file);

		if (isset($irregular[$name])) {
			$short = $irregular[$name];
		} else {
			$short = substr($name, 0, 3);
		}

		$path  = $short . DS;
		$path .= strtolower(Inflector::slug($filename));
		$path .= !empty($extension) ? '.' . strtolower($extension) : null;

		return $path;
	}

/**
 * Prepares (if necessary) and performs a transfer
 *
 * Please note that if a file with the same name as the destination exists,
 * it will be silently overwritten.
 *
 * There are currently 3 different types of transferable items which are all
 * enabled – if you’re not using the location validation rule – by default.
 *
 * 1. Array generated by an upload through a HTML form via HTTP POST.
 *    {{{
 *        array(
 *            'name' => 'cern.jpg',
 *            'type' => 'image/jpeg',
 *            'tmp_name' => '/tmp/32ljsdf',
 *            'error' => 0,
 *            'size' => 49160
 *        )
 *    }}}
 *
 * 2. String containing an absolute path to a file in the filesystem.
 *  `'/var/www/tmp/cern.jpg'`
 *
 * 3. String containing an URL.
 *  `'http://cakephp.org/imgs/logo.png'`
 *
 * Transfer types can be enabled selectively by using the location validation
 * rule. To make HTTP transfers work you must explicitly allow all HTTP transfers
 * by specifying 'http://' in the location validation rule or – if you only want
 * to allow transfers from certain domains – use 'http://example.org' instead.
 * {{{
 *     'location' => array('rule' => array('checkLocation', array(MEDIA_TRANSFER, '/tmp/', 'http://')))
 * }}}
 *
 * If you experience problems with the model not validating, try commenting the
 * mimeType rule or provide less strict settings for the rules.
 *
 * For users on Windows it is important to know that checkExtension and
 * checkMimeType take both a blacklist and a whitelist and you make sure that you
 * additionally specify tmp as an extension in case you are using a whitelist:
 * {{{
 *     'extension' => array('rule' => array('checkExtension', array('bin', 'exe'), array('jpg', 'tmp')))
 * }}}
 *
 * @see checkLocation()
 * @param Model $Model
 * @param mixed $file File from which source, temporary and destination are derived
 * @return string|boolean Destination file on success, false on failure
 */
	public function transfer(Model $Model, $file) {
		if ($this->runtime[$Model->alias]['hasPerformed']) {
			$this->runtime[$Model->alias] = $this->_defaultRuntime;
			$this->runtime[$Model->alias]['hasPerformed'] = true;
		}
		if (!$this->_prepare($Model, $file)) {
			return false;
		}
		extract($this->runtime[$Model->alias]);
		/* @var $source array */
		/* @var $temporary string */
		/* @var $destination string */
		/* @var $hasPerformed boolean */

		$Socket = null;
		if ($source['type'] === 'http-url-remote') {
			$Socket = new HttpSocket(array('timeout' => 5));
			$Socket->request(array('method' => 'GET', 'uri' => $source['file']));

			if (!empty($Socket->error) || $Socket->response['status']['code'] != 200) {
				return false;
			}
		}

		$chain = implode('>>', array($source['type'], $temporary['type'], $destination['type']));
		$result = false;

		switch ($chain) {
			case 'file-upload-remote>>uploaded-file-local>>file-local':
				$result = move_uploaded_file($temporary['file'], $destination['file']);
				break;
			case 'file-local>>>>file-local':
				$result = copy($source['file'], $destination['file']);
				break;
			case 'file-local>>file-local>>file-local':
				if (copy($source['file'], $temporary['file'])) {
					$result = rename($temporary['file'], $destination['file']);
				}
				break;
			case 'http-url-remote>>>>file-local':
				$result = file_put_contents($destination['file'], $Socket->response['body']);
				break;
			case 'http-url-remote>>file-local>>file-local':
				if (file_put_contents($temporary['file'], $Socket->response['body'])) {
					$result = rename($temporary['file'], $destination['file']);
				}
				break;
		}
		return $result ? $destination['file'] : false;
	}

/**
 * Convenience method which (if available) returns absolute path to last transferred file
 *
 * @param Model $Model
 * @return string|boolean
 */
	public function transferred(Model $Model) {
		extract($this->runtime[$Model->alias], EXTR_SKIP);
		return isset($destination['file']) ? $destination['file'] : false;
	}

/**
 * Triggered by `beforeValidate` and `transfer()`
 *
 * @param Model $Model
 * @param array|string $resource Transfer resource
 * @return boolean true if transfer is ready to be performed, false on error
 */
	protected function _prepare(Model $Model, $resource) {
		extract($this->settings[$Model->alias], EXTR_SKIP);
		/* @var $trustClient boolean */
		/* @var $transferDirectory string */
		/* @var $createDirectory boolean */
		/* @var $alternativeFile integer */
		/* @var $overwrite boolean */
		extract($this->runtime[$Model->alias], EXTR_SKIP);
		/* @var $source array */
		/* @var $temporary string */
		/* @var $destination string */
		/* @var $hasPerformed boolean */

		if ($source = $this->_source($Model, $resource)) {
			$this->runtime[$Model->alias]['source'] = $source;
		} else {
			return false;
		}

		if ($source['type'] !== 'file-local') {
			$temporary = $this->runtime[$Model->alias]['temporary'] = $this->_temporary($Model, $resource);
		}

		if (!$file = $Model->transferTo($temporary, $source)) {
			$message = "TransferBehavior::_prepare - Could not obtain destination file path.";
			trigger_error($message, E_USER_NOTICE);
			return false;
		}
		$file = $transferDirectory . $file;

		if (!$overwrite) {
			if (!$file = $this->_alternativeFile($file, $alternativeFile)) {
				$message  = "TransferBehavior::_prepare - ";
				$message .= "Exceeded number of max. tries while finding alternative file name.";
				trigger_error($message, E_USER_NOTICE);
				return false;
			}
		}

		if ($destination = $this->_destination($Model, $file)) {
			$this->runtime[$Model->alias]['destination'] = $destination;
		} else {
			return false;
		}
		if ($destination == $source || $destination == $temporary) {
			return false;
		}

		$Folder = new Folder($destination['dirname'], $createDirectory);

		if (!$Folder->pwd()) {
			$message  = "TransferBehavior::_prepare - Directory `{$destination['dirname']}` could ";
			$message .= "not be created or is not writable. Please check the permissions.";
			trigger_error($message, E_USER_WARNING);
			return false;
		}
		return true;
	}

/**
 * Parse data to be used as source
 *
 * @param Model $Model
 * @param array|string $resource Transfer resource good for deriving the source data from it
 * @return array|boolean Parsed results on success, false on error
 * @todo evaluate errors in file uploads
 */
	protected function _source(Model $Model, $resource) {
		if (TransferValidation::fileUpload($resource)) {
			return array_merge(
				$this->transferMeta($Model, $resource),
				array('error' => $resource['error'])
			);
		} elseif (MediaValidation::file($resource)) {
			return $this->transferMeta($Model, $resource);
		} elseif (TransferValidation::url($resource, array('scheme' => 'http'))) {
			return $this->transferMeta($Model, $resource);
		}
		return false;
	}

/**
 * Parse data to be used as temporary
 *
 * @param Model $Model
 * @param array|string $resource Transfer resource good for deriving the temporary data from it
 * @return array|boolean Parsed results on success, false on error
 */
	protected function _temporary(Model $Model, $resource) {
		if (TransferValidation::fileUpload($resource)
		&& TransferValidation::uploadedFile($resource['tmp_name'])) {
			return array_merge(
				$this->transferMeta($Model, $resource['tmp_name']),
				array('error' => $resource['error'])
			);
		} elseif (MediaValidation::file($resource)) {
			return $this->transferMeta($Model, $resource);
		}
		return false;
	}

/**
 * Parse data to be used as destination
 *
 * @param Model $Model
 * @param array|string $resource Transfer resource good for deriving the destination data from it
 * @return array|boolean Parsed results on success, false on error
 */
	protected function _destination(Model $Model, $resource) {
		if (MediaValidation::file($resource, false)) {
			return $this->transferMeta($Model, $resource);
		}
		return false;
	}

/**
 * Checks if field contains a transferable resource
 *
 * To require a file being uploaded, consider the following validation rule.
 * {{{
 *     public $validate = array(
 *         'file' => array(
 *             'resource' => array(
 *                 'rule' => 'checkResource',
 *                 'allowEmpty' => false,
 *                 'required' => true
 *     ))));
 * }}i}
 *
 * @see TransferBehavior::_source()
 * @param Model $Model
 * @param array $field
 * @return boolean
 */
	public function checkResource(Model $Model, $field) {
		return TransferValidation::resource(current($field));
	}

/**
 * Checks if sufficient permissions are set to access the resource
 * Source must be readable, temporary read or writable, destination writable
 *
 * @param Model $Model
 * @param array $field
 * @return boolean
 */
	public function checkAccess(Model $Model, $field) {
		extract($this->runtime[$Model->alias]);
		/* @var $source array */
		/* @var $temporary string */
		/* @var $destination string */
		/* @var $hasPerformed boolean */

		if (MediaValidation::file($source['file'], true)) {
			if (!MediaValidation::access($source['file'], 'r')) {
				return false;
			}
		} else {
			if (!MediaValidation::access($source['permission'], 'r')) {
				return false;
			}
		}
		if (!empty($temporary)) {
			if (MediaValidation::file($temporary['file'], true)) {
				if (!MediaValidation::access($temporary['file'], 'r')) {
					return false;
				}
			} elseif (MediaValidation::folder($temporary['dirname'], true)) {
				if (!MediaValidation::access($temporary['dirname'], 'w')) {
					return false;
				}
			}
		}
		if (!MediaValidation::access($destination['dirname'], 'w')) {
			return false;
		}
		return true;
	}

/**
 * Checks if resource is located within given locations
 *
 * @param Model $Model
 * @param array $field
 * @param mixed $allow True or * allows any location, an array containing absolute paths to locations
 * @return boolean
 */
	public function checkLocation(Model $Model, $field, $allow = true) {
		extract($this->runtime[$Model->alias]);

		foreach ((array)$allow as $allowed) {
			if (strpos(':', $allowed) !== false) {
				$message  = "TransferBehavior::checkLocation - ";
				$message .= "Makers cannot be used in parameters for this method anymore. ";
				$message .= "Please use predefined constants instead.";
				trigger_error($message, E_USER_NOTICE);
			}
		}
		foreach (array('source', 'temporary', 'destination') as $type) {
			if ($type == 'temporary' && empty($$type)) {
				continue;
			}
			if ($type == 'source' && ${$type}['type'] == 'file-upload-remote') {
				continue;
			}
			if (!MediaValidation::location(${$type}['file'], $allow)) {
				return false;
			}
		}
		return true;
	}

/**
 * Checks if provided or potentially dangerous permissions are set
 *
 * @param Model $Model
 * @param array $field
 * @param mixed $match True to check for potentially dangerous permissions,
 * 	a string containing the 4-digit octal value of the permissions to check for an exact match,
 * 	false to allow any permissions
 * @return boolean
 */
	public function checkPermission(Model $Model, $field, $match = true) {
		extract($this->runtime[$Model->alias]);

		foreach (array('source', 'temporary') as $type) {
			if ($type == 'temporary' && empty($$type)) {
				continue;
			}
			if (!MediaValidation::permission(${$type}['permission'], $match)) {
				return false;
			}
		}
		return true;
	}

/**
 * Checks if resource doesn't exceed provided size
 *
 * Please note that the size will always be checked against
 * limitations set in `php.ini` for `post_max_size` and `upload_max_filesize`
 * even if $max is set to false.
 *
 * @param Model $Model
 * @param array $field
 * @param mixed $max String (e.g. 8M) containing maximum allowed size, false allows any size
 * @return boolean
 */
	public function checkSize(Model $Model, $field, $max = false) {
		extract($this->runtime[$Model->alias]);

		foreach (array('source', 'temporary') as $type) {
			if ($type == 'temporary' && empty($$type)) {
				continue;
			}
			if (!MediaValidation::size(${$type}['size'], $max)) {
				return false;
			}
		}
		return true;
	}

/**
 * Checks if resource (if it is an image) pixels doesn't exceed provided size
 *
 * Useful in situation where you wan't to prevent running out of memory when
 * the image gets resized later. You can calculate the amount of memory used
 * like this: width * height * 4 + overhead
 *
 * @param Model $Model
 * @param array $field
 * @param mixed $max String (e.g. 40000 or 200x100) containing maximum allowed amount of pixels
 * @return boolean
 */
	public function checkPixels(Model $Model, $field, $max = false) {
		extract($this->runtime[$Model->alias]);

		foreach (array('source', 'temporary') as $type) { /* pixels value is optional */
			if (($type == 'temporary' && empty($$type)) || !isset(${$type}['pixels'])) {
				continue;
			}
			if (!MediaValidation::pixels(${$type}['pixels'], $max)) {
				return false;
			}
		}
		return true;
	}

/**
 * Checks if resource has (not) one of given extensions
 *
 * @param Model $Model
 * @param array $field
 * @param mixed $deny True or * blocks any extension,
 * 	an array containing extensions (w/o leading dot) selectively blocks,
 * 	false blocks no extension
 * @param mixed $allow True or * allows any extension,
 * 	an array containing extensions (w/o leading dot) selectively allows,
 * 	false allows no extension
 * @return boolean
 */
	public function checkExtension(Model $Model, $field, $deny = false, $allow = true) {
		extract($this->runtime[$Model->alias]);

		foreach (array('source', 'temporary', 'destination') as $type) {
			if (($type == 'temporary' && empty($$type)) || !isset(${$type}['extension'])) {
				continue;
			}
			if (!MediaValidation::extension(${$type}['extension'], $deny, $allow)) {
				return false;
			}
		}
		return true;
	}

/**
 * Checks if resource has (not) one of given MIME types
 *
 * This check is less strict in that it isn't sensitive to MIME types with or
 * without properties or experimental indicators. This holds true for the type
 * which is subject of the check as well as types provided for $deny and
 * $allow. I.e. `audio/x-ogg` will be allowed if $allow contains `audio/ogg`
 * and `video/ogg` works also if $allow contains the stricter `video/x-ogg`.
 *
 * @param Model $Model
 * @param array $field
 * @param mixed $deny True or * blocks any MIME type,
 * 	an array containing MIME types selectively blocks,
 * 	false blocks no MIME type
 * @param mixed $allow True or * allows any extension,
 * 	an array containing extensions selectively allows,
 * 	false allows no MIME type
 * @return boolean
 */
	public function checkMimeType(Model $Model, $field, $deny = false, $allow = true) {
		extract($this->runtime[$Model->alias]);
		/* @var $source array */
		/* @var $temporary string */
		/* @var $destination string */
		/* @var $hasPerformed boolean */
		extract($this->settings[$Model->alias], EXTR_SKIP);
		/* @var $trustClient boolean */
		/* @var $transferDirectory string */
		/* @var $createDirectory boolean */
		/* @var $alternativeFile integer */
		/* @var $overwrite boolean */

		foreach (array('source', 'temporary') as $type) {
			/*
			 * MIME types and trustClient setting
			 *
			 * trust | source   | (temporary) | (destination)
			 * ------|----------|----------------------------
			 * true  | x/x      | x/x         | x/x,null
			 * ------|----------|----------------------------
			 * false | x/x,null | x/x,null    | null
			 */
			/* Temporary is optional */
			if ($type === 'temporary' && empty($$type)) {
				continue;
			}
			/* With `trustClient` set to `false` we don't necessarily have a MIME type */
			if (!isset(${$type}['mimeType']) && !$trustClient) {
				continue;
			}
			$result  = MediaValidation::mimeType(${$type}['mimeType'], $deny, $allow);
			$result |= MediaValidation::mimeType(Mime_Type::simplify(${$type}['mimeType']), $deny, $allow);

			return $result;
		}
		return true;
	}

/**
 * Finds an alternative filename for an already existing file
 *
 * @param string $file Absolute path to file in local FS
 * @param integer $tries Number of tries
 * @return mixed A string if an alt. name was found, false if number of tries were exceeded
 */
	protected function _alternativeFile($file, $tries = 100) {
		$extension = null;
		extract(pathinfo($file), EXTR_OVERWRITE);
		/* @var $dirname string */
		/* @var $basename string */
		/* @var $extension string */
		/* @var $filename string */

		$newFilename = $filename;

		$Folder = new Folder($dirname);
		$names = $Folder->find($filename . '.*');

		foreach ($names as &$name) {
			$name = pathinfo($name, PATHINFO_FILENAME);
		}

		for ($count = 2; in_array($newFilename, $names); $count++) {
			if ($count > $tries) {
				return false;
			}

			$newFilename = $filename . '_' . $count;
		}

		$new = $dirname . DS . $newFilename;

		if (isset($extension)) {
			$new .= '.' . $extension;
		}
		return $new;
	}

}
