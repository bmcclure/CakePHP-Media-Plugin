<?php
/**
 * Media constant checks.
 *
 * Forces all MEDIA* filesystem path constants to point to somewhere in the temp folder.
 * Be sure to include this file in all tests!
 *
 * PHP 5
 * CakePHP 2
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author        Oliver Nowak <info@nowak-media.de>
 * @package       Media.Test.Case
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

$map = array(
	'MEDIA'          => TMP . 'tests' . DS,
	'MEDIA_STATIC'   => TMP . 'tests' . DS . 'static' . DS,
	'MEDIA_FILTER'   => TMP . 'tests' . DS . 'filter' . DS,
	'MEDIA_TRANSFER' => TMP . 'tests' . DS . 'transfer' . DS
);
foreach ($map as $name => $path) {
	if (!defined($name)) {
		define($name, $path);
	} elseif (constant($name) !== $path) {
		trigger_error($name . ' constant already defined and not pointing to "' . $path . '".', E_USER_ERROR);
	}
}
