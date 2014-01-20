<?php
/**
 * Transfer Validation Test Case File
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
 * @package       Media.Test.Case.Lib
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

App::uses('TransferValidation', 'Media.Lib');

require_once dirname(dirname(__FILE__)) . DS . 'constants.php';

/**
 * Transfer Validation Test Case Class
 *
 * @package       Media.Test.Case.Lib
 */
class TransferValidationTest extends CakeTestCase {

	public function testResource() {
		$upload = array(
			'name'     => 'file.name',
			'type'     => 'mime/type',
			'tmp_name' => 'efhf42983q7ghdsui',
			'error'    => '',
			'size'     => 1234
		);
		$result = TransferValidation::resource($upload);
		$this->assertTrue($result);

		// TODO implement
		//$uploadedFile = null;
		//$result = TransferValidation::resource($uploadedFile);
		//$this->assertTrue($result);

		$file = __FILE__;
		$result = TransferValidation::resource($file);
		$this->assertTrue($result);

		$url = 'http://example.com';
		$result = TransferValidation::resource($url);
		$this->assertTrue($result);
	}

	public function testBlank() {
		$upload = array();
		$result = TransferValidation::blank($upload);
		$this->assertTrue($result);

		$upload = array(
			'name'     => '',
			'type'     => '',
			'tmp_name' => '',
			'error'    => UPLOAD_ERR_NO_FILE,
			'size'     => 0
		);
		$result = TransferValidation::blank($upload);
		$this->assertTrue($result);

		$upload = '';
		$result = TransferValidation::blank($upload);
		$this->assertTrue($result);

		$upload = array(
			'name'     => 'file.name',
			'type'     => 'mime/type',
			'tmp_name' => 'efhf42983q7ghdsui',
			'error'    => '',
			'size'     => 1234
		);
		$result = TransferValidation::blank($upload);
		$this->assertFalse($result);

		$upload = 'Lorem ipsum';
		$result = TransferValidation::blank($upload);
		$this->assertFalse($result);
	}

	public function testFileUpload() {
		$upload = array(
			'name'     => 'file.name',
			'type'     => 'mime/type',
			'tmp_name' => 'efhf42983q7ghdsui',
			'error'    => '',
			'size'     => 1234
		);
		$result = TransferValidation::fileUpload($upload);
		$this->assertTrue($result);

		$upload = array(
			'name' => 'file.name'
		);
		$result = TransferValidation::fileUpload($upload);
		$this->assertFalse($result);
	}

	public function testUploadedFile() {
		// TODO implement
	}

	public function testUrl() {
		$url = 'https://example.com';
		$result = TransferValidation::url($url, array('scheme' => 'http'));
		$this->assertFalse($result);

		$url = 'http://example.com';
		$result = TransferValidation::url($url, array('scheme' => 'http'));
		$this->assertTrue($result);

		$url = null;
		$result = TransferValidation::url($url);
		$this->assertFalse($result);
	}

}
