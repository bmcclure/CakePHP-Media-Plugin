<?php
/**
 * Model Test Models
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
 * @package       Media.Test.Case.Model
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

class Movie extends CakeTestModel {
	public $name = 'Movie';
	public $useTable = 'movies';
	public $hasMany = array('Actor');
}

class Actor extends CakeTestModel {
	public $name = 'Actor';
	public $useTable = 'actors';
	public $belongsTo = array('Movie');
}

class Unicorn extends CakeTestModel {
	public $name = 'Unicorn';
	public $useTable = false;
	public $makeVersionArgs = array();
	public $returnMakeVersion = true;

	public function makeVersion() {
		$this->makeVersionArgs[] = func_get_args();
		return $this->returnMakeVersion;
	}
}

class Pirate extends CakeTestModel {
	public $name = 'Pirate';
	public $useTable = 'pirates';
}