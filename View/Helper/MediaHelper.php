<?php
/**
 * Media Helper File
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
 * @package       Media.View.Helper
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

App::uses('Folder', 'Utility');
App::uses('Set', 'Utility');
App::uses('AppHelper', 'View/Helper');

require_once 'Mime/Type.php';

/**
 * Media Helper Class
 *
 * To load the helper just include it in the helpers property
 * of a controller:
 * {{{
 *     public $helpers = array('Form', 'Html', 'Media.Media');
 * }}}
 *
 * If needed you can also pass additional path to URL mappings when
 * loading the helper:
 * {{{
 *     public $helpers = array('Media.Media' => array(MEDIA_FOO => 'foo/'));
 * }}}
 *
 * Nearly all helper methods take so called partial paths. Partial paths are
 * dynamically expanded path fragments for let you specify paths to files in a
 * very short way.
 *
 * @see           MediaHelper::file()
 * @see           MediaHelper::__construct()
 * @link          http://book.cakephp.org/2.0/en/views/helpers.html
 * @package       Media.View.Helper
 *
 * @property HtmlHelper $Html
 */
class MediaHelper extends AppHelper {

/**
 * Helpers
 *
 * @var array
 */
	public $helpers = array('Html');

/**
 * Default settings
 *
 * configFile
 *  The name of a file containing an array of HtmlHelper configuration options you wish to redefine/merge,
 *  see HtmlHelper::loadConfig().
 *
 * paths
 *  An array of base directory paths mapped to URLs. Used for determining the absolute path to a file
 *  in `file()` and for determining the URL corresponding to an absolute path. Paths are expected to
 *  end with a trailing slash.
 *
 * @var array
 */
	protected $_defaultSettings = array(
		'configFile' => null,
		'paths'      => array(
			MEDIA_STATIC   => MEDIA_STATIC_URL,
			MEDIA_TRANSFER => MEDIA_TRANSFER_URL,
			MEDIA_FILTER   => MEDIA_FILTER_URL
		)
	);

/**
 * Directory paths mapped to URLs. Can be modified by passing custom paths as
 * settings to the constructor.
 *
 * @var array
 */
	protected $_paths = array();

/**
 * Constructor
 *
 * Merges user supplied map settings with default map
 *
 * @param View $View The View this helper is being attached to.
 * @param array $settings See defaultSettings for configuration options
 */
	public function __construct(View $View, $settings = array()) {
		parent::__construct($View, $settings);

		$path = dirname(dirname(dirname(__FILE__))) . DS . 'Config' . DS;
		$this->Html->loadConfig('media_helper.php', $path);

		if (isset($settings['configFile'])) {
			$this->Html->loadConfig($settings['configFile']);
			unset($settings['configFile']);
		}

		$paths = (array)$settings;
		if (isset($paths['paths'])) {
			$paths = $paths['paths'];
		}
		$this->_paths = array_merge($this->_defaultSettings['paths'], $paths);
	}

/**
 * Turns a file path into an URL (without passing it through `Router::url()`)
 *
 * Reimplemented method from Helper
 *
 * @param string $path Absolute or partial path to a file
 * @param boolean $full Forces the URL to be fully qualified
 * @return string|void An URL to the file
 *
 * NOTE FULL_BASE_URL is deprecated as of 2.4, maybe add logic to utilize App.fullBaseUrl config value,
 * NOTE that would make testing the use of _encodeUrl() possible
 */
	public function url($path = null, $full = false) {
		if (!$path = $this->webroot($path)) {
			return null;
		}
		if ($full && strpos($path, '://') === false) {
			$path = $this->_encodeUrl(FULL_BASE_URL) . $path;
		}
		return $path;
	}

/**
 * Webroot
 *
 * Reimplemented method from Helper
 *
 * @param string $path Absolute or partial path to a file
 * @return string|void An URL to the file
 */
	public function webroot($path) {
		if (!$file = $this->file($path)) {
			return null;
		}

		foreach ($this->_paths as $directory => $url) {
			if (strpos($file, $directory) !== false) {
				if ($url === false) {
					return null;
				}
				$path = str_replace($directory, $url, $file);
				break;
			}
		}
		$path = str_replace('\\', '/', $path);

		if (strpos($path, '://') === false) {
			$path = $this->request->webroot . $path;
		}

		return $this->_encodeUrl($path);
	}

/**
 * Generates HTML5 markup for one ore more media files
 *
 * Determines correct dimensions for all images automatically. Dimensions for all
 * other media should be passed explicitly within the options array in order to prevent
 * the browser refloating the layout.
 *
 * @param string|array $paths Absolute or partial path to a file (or an array thereof)
 * @param array $options The following options control the output of this method:
 *                       - autoplay: Start playback automatically on page load, defaults to `false`.
 *                       - preload: Start buffering when page is loaded, defaults to `false`.
 *                       - controls: Show controls, defaults to `true`.
 *                       - loop: Loop playback, defaults to `false`.
 *                       - fallback: A string containing HTML to use when element is not supported.
 *                       - poster: The path to a placeholder image for a video.
 *                       - url: If given wraps the result with a link.
 *                       - full: Will generate absolute URLs when `true`, defaults to `false`.
 *
 *                       - width, height: For images the method will try to automatically determine
 *                                        the correct dimensions if no value is given for either
 *                                        one of these.
 *
 *                       Additional options will be mapped to HTML attributes.
 * @return string|boolean
 */
	public function embed($paths, $options = array()) {
		$default = array(
			'autoplay' => false,
			'preload'  => false,
			'controls' => true,
			'loop'     => false,
			'fallback' => null,
			'poster'   => null,
			'full'     => false
		);

		if (isset($options['url'])) {
			$link = $options['url'];
			unset($options['url']);

			return $this->Html->link($this->embed($paths, $options), $link, array(
				'escape' => false
			));
		}
		$options = array_merge($default, $options);
		extract($options, EXTR_SKIP);
		/* @var $autoplay boolean */
		/* @var $preload boolean */
		/* @var $controls boolean */
		/* @var $loop boolean */
		/* @var $fallback boolean */
		/* @var $poster boolean */
		/* @var $full boolean */

		if (!$sources = $this->_sources((array)$paths, $full)) {
			return false;
		}
		$attributes = array_diff_key($options, $default);

		switch($sources[0]['name']) {
			case 'audio':
				$body = null;

				foreach ($sources as $source) {
					$body .= $this->Html->useTag(
						'source',
						array(
							'src'  => $source['url'],
							'type' => $source['mimeType']
						)
					);
				}
				$attributes += compact('autoplay', 'controls', 'preload', 'loop');
				return $this->Html->useTag(
					'audio',
					$attributes,
					$body,
					$fallback
				);
			case 'document':
				break;
			case 'image':
				$attributes = $this->_addDimensions($sources[0]['file'], $attributes);

				return $this->Html->useTag('image',
					h($sources[0]['url']),
					$attributes
				);
			case 'video':
				$body = null;

				foreach ($sources as $source) {
					$body .= $this->Html->useTag(
						'source',
						array(
							'src'  => $source['url'],
							'type' => $source['mimeType']
						)
					);
				}
				if ($poster) {
					$attributes = $this->_addDimensions($this->file($poster), $attributes);
					$poster = $this->url($poster, $full);
				}

				$attributes += compact('autoplay', 'controls', 'preload', 'loop', 'poster');
				return $this->Html->useTag(
					'video',
					$attributes,
					$body,
					$fallback
				);
			default:
				break;
		}

		return false;
	}

/**
 * Generates markup for a single media file using the `object` tag similar to `embed()`.
 *
 * @param string|array $paths Absolute or partial path to a file. An array can be passed to be make
 *                            this method compatible with `embed()`, in which case just the first file
 *                            in that array is actually used.
 * @param array $options The following options control the output of this method. Please note that
 *                       support for these options differs from type to type.
 *                       - autoplay: Start playback automatically on page load, defaults to `false`.
 *                       - controls: Show controls, defaults to `true`.
 *                       - loop: Loop playback, defaults to `false`.
 *                       - fallback: A string containing HTML to use when element is not supported.
 *                       - url: If given wraps the result with a link.
 *                       - full: Will generate absolute URLs when `true`, defaults to `false`.
 *
 *                       Additional options will be mapped to HTML attributes.
 * @return string|boolean
 */
	public function embedAsObject($paths, $options = array()) {
		$default = array(
			'autoplay' => false,
			'controls' => true,
			'loop'     => false,
			'fallback' => null,
			'full'     => false
		);

		if (isset($options['url'])) {
			$link = $options['url'];
			unset($options['url']);

			return $this->Html->link($this->embed($paths, $options), $link, array(
				'escape' => false
			));
		}
		$options = array_merge($default, $options);
		extract($options + $default);
		/* @var $autoplay boolean */
		/* @var $controls boolean */
		/* @var $loop boolean */
		/* @var $fallback boolean */
		/* @var $full boolean */

		if (!$sources = $this->_sources((array)$paths, $full)) {
			return false;
		}
		$attributes  = array('type' => $sources[0]['mimeType'], 'data' => $sources[0]['url']);
		$attributes += array_diff_key($options, $default);

		switch ($sources[0]['mimeType']) {
			/* Windows Media */
			case 'video/x-ms-wmv': /* official */
			case 'video/x-ms-asx':
			case 'video/x-msvideo':
				$attributes += array(
					'classid' => 'clsid:6BF52A52-394A-11d3-B153-00C04F79FAA6'
				);
				$parameters = array(
					'src'         => $sources[0]['url'],
					'autostart'   => $autoplay,
					'controller'  => $controls,
					'pluginspage' => 'http://www.microsoft.com/Windows/MediaPlayer/'
				);
				break;
			/* RealVideo */
			case 'application/vnd.rn-realmedia':
			case 'video/vnd.rn-realvideo':
			case 'audio/vnd.rn-realaudio':
				$attributes += array(
					'classid' => 'clsid:CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA',
				);
				$parameters = array(
					'src'         => $sources[0]['url'],
					'autostart'   => $autoplay,
					'controls'    => isset($controls) ? 'ControlPanel' : null,
					'console'     => 'video' . uniqid(),
					'loop'        => $loop,
					'nologo'      => true,
					'nojava'      => true,
					'center'      => true,
					'pluginspage' => 'http://www.real.com/player/'
				);
				break;
			/* QuickTime */
			case 'video/quicktime':
				$attributes += array(
					'classid'  => 'clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B',
					'codebase' => 'http://www.apple.com/qtactivex/qtplugin.cab'
				);
				$parameters = array(
					'src' => $sources[0]['url'],
					'autoplay'    => $autoplay,
					'controller'  => $controls,
					'showlogo'    => false,
					'pluginspage' => 'http://www.apple.com/quicktime/download/'
				);
				break;
			/* Mpeg */
			case 'video/mpeg':
				$parameters = array(
					'src'       => $sources[0]['url'],
					'autostart' => $autoplay,
				);
				break;
			/* Flashy Flash */
			case 'application/x-shockwave-flash':
				$attributes += array(
					'classid'  => 'clsid:D27CDB6E-AE6D-11cf-96B8-444553540000',
					'codebase' => 'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab'
				);
				$parameters = array(
					'movie'       => $sources[0]['url'],
					'wmode'       => 'transparent',
					'FlashVars'   => 'playerMode=embedded',
					'quality'     => 'best',
					'scale'       => 'noScale',
					'salign'      => 'TL',
					'pluginspage' => 'http://www.adobe.com/go/getflashplayer'
				);
				break;
			case 'application/pdf':
				$parameters = array(
					'src'       => $sources[0]['url'],
					'toolbar'   => $controls, /* 1 or 0 */
					'scrollbar' => $controls, /* 1 or 0 */
					'navpanes'  => $controls
				);
				break;
			case 'audio/x-wav':
			case 'audio/mpeg':
			case 'audio/ogg':
			case 'audio/x-midi':
				$parameters = array(
					'src'      => $sources[0]['url'],
					'autoplay' => $autoplay
				);
				break;
			default:
				$parameters = array(
					'src' => $sources[0]['url']
				);
				break;
		}
		return $this->Html->useTag(
			'object',
			$attributes,
			$this->_parseParameters($parameters),
			$fallback
		);
	}

/**
 * Get the name of a media for a path
 *
 * @param string $path Absolute or partial path to a file
 * @return string|null i.e. `image` or `video`
 */
	public function name($path) {
		if ($file = $this->file($path)) {
			return Mime_Type::guessName($file);
		}
		return null;
	}

/**
 * Get MIME type for a path
 *
 * @param string $path Absolute or partial path to a file
 * @return string|null
 */
	public function mimeType($path) {
		if ($file = $this->file($path)) {
			return Mime_Type::guessType($file);
		}
		return null;
	}

/**
 * Get size of file
 *
 * @param string $path Absolute or partial path to a file
 * @return integer|null
 */
	public function size($path) {
		if ($file = $this->file($path)) {
			return filesize($file);
		}
		return null;
	}

/**
 * Resolves partial path to an absolute path by trying to find an existing file matching the
 * pattern `{<base path 1>, <base path 2>, [...]}/<provided partial path without ext>.*`.
 * The base paths are coming from the `_paths` property.
 *
 * Examples:
 * img/cern                 >>> MEDIA_STATIC/img/cern.png
 * img/mit.jpg              >>> MEDIA_TRANSFER/img/mit.jpg
 * s/<...>/img/hbk.jpg      >>> MEDIA_FILTER/s/<...>/img/hbk.png
 *
 * @param string $path A relative or absolute path to a file.
 * @return string|boolean False on error or if path couldn't be resolved otherwise
 *                        an absolute path to the file.
 */
	public function file($path) {
		if (strpos($path, '://') !== false) {
			return false;
		}

		$path = str_replace('/', DS, trim($path));

		// Most recent paths are probably searched more often
		$bases = array_reverse(array_keys($this->_paths));

		if (Folder::isAbsolute($path)) {
			return file_exists($path) ? $path : false;
		}

		$extension = null;
		extract(pathinfo($path), EXTR_OVERWRITE);
		/* @var $dirname string */
		/* @var $basename string */
		/* @var $extension string */
		/* @var $filename string */

		foreach ($bases as $base) {
			if (file_exists($base . $path)) {
				return $base . $path;
			}
			$files = glob($base . $dirname . DS . $filename . '.*', GLOB_NOSORT | GLOB_NOESCAPE);

			if (count($files) > 1) {
				$message  = "MediaHelper::file - ";
				$message .= "A relative path (`{$path}`) was given which triggered search for ";
				$message .= "files with the same name but not the same extension.";
				$message .= "This resulted in multiple files being found. ";
				$message .= "However the first file being found has been picked.";
				trigger_error($message, E_USER_NOTICE);
			}
			if ($files) {
				return array_shift($files);
			}
		}

		return false;
	}

/**
 * Takes an array of paths and generates and array of source items.
 *
 * @param array $paths An array of  relative or absolute paths to files.
 * @param boolean $full When `true` will generate absolute URLs.
 * @return array|boolean An array of sources each one with the keys `name`, `mimeType`, `url` and `file`.
 */
	protected function _sources($paths, $full = false) {
		$sources = array();

		foreach ($paths as $path) {
			if (!$url = $this->url($path, $full)) {
				return false;
			}
			$file = $this->file($path);
			$mimeType = Mime_Type::guessType($file);
			$name = Mime_Type::guessName($mimeType);

			$sources[] = compact('name', 'mimeType', 'url', 'file');
		}
		return $sources;
	}

/**
 * Adds dimensions to an attributes array if possible.
 *
 * @param string $file An absolute path to a file.
 * @param array $attributes
 * @return array The modified attributes array.
 */
	protected function _addDimensions($file, $attributes) {
		if (isset($attributes['width']) || isset($attributes['height'])) {
			return $attributes;
		}
		if (function_exists('getimagesize')) {
			list($attributes['width'], $attributes['height']) = getimagesize($file);
		}
		return $attributes;
	}

/**
 * Generates `param` tags
 *
 * @param array $options
 * @return string
 */
	protected function _parseParameters($options) {
		$parameters = array();
		$options = Set::filter($options);

		foreach ($options as $key => $value) {
			if ($value === true) {
				$value = 'true';
			} elseif ($value === false) {
				$value = 'false';
			}
			$parameters[] = $this->Html->useTag(
				'param',
				array(
					'name' => $key,
					'value' => $value
				)
			);
		}
		return implode("\n", $parameters);
	}

/**
 * Encodes an URL for use in HTML attributes.
 *
 * @param string $url The url to encode.
 * @return string The url encoded for URL contexts.
 */
	protected function _encodeUrl($url) {
		$path = parse_url($url, PHP_URL_PATH);
		$parts = array_map('rawurldecode', explode('/', $path));
		$parts = array_map('rawurlencode', $parts);
		$encoded = implode('/', $parts);
		return str_replace($path, $encoded, $url);
	}

}
