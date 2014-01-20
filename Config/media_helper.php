<?php
/**
 * Media helper configuration file
 *
 * PHP 5
 * CakePHP 2
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author        Oliver Nowak <info@nowak-media.de>
 * @package       Media.Config
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

$config = array(
	'tags' => array(
		'audio'  => '<audio%s>%s%s</audio>',
		'video'  => '<video%s>%s%s</video>',
		'source' => '<source%s/>',
		'object' => '<object%s>%s%s</object>',
		'param'  => '<param%s/>'
	),
	'minimizedAttributes' => array(
		'autobuffer',
		'autoplay',
		'checked',
		'compact',
		'controls',
		'declare',
		'defer',
		'disabled',
		'formnovalidate',
		'ismap',
		'loop',
		'multiple',
		'muted',
		'nohref',
		'noresize',
		'noshade',
		'novalidate',
		'nowrap',
		'readonly',
		'required',
		'selected'
	)
);
