<?php
/**
 * Media Helper Test Case File
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
 * @package       Media.Test.Case.View.Helper
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

App::uses('ClassRegistry', 'Utility');
App::uses('View', 'View');
App::uses('MediaHelper', 'Media.View/Helper');

require_once dirname(dirname(dirname(__FILE__))) . DS . 'constants.php';
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DS . 'Config' . DS . 'bootstrap.php';
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DS . 'Fixture' . DS . 'TestData.php';

/**
 * Mock Media Helper
 *
 * @package       Media.Test.Case.View.Helper
 */
class MockMediaHelper extends MediaHelper {

	public function paths() {
		return $this->_paths;
	}

}

/**
 * Media Helper Test Case Class
 *
 * @package       Media.Test.Case.View.Helper
 */
class MediaHelperTest extends CakeTestCase {

/**
 * @var TestData
 */
	public $Data;

	public $file0;
	public $file1;
	public $file2;
	public $file3;
	public $file4;
	public $file5;

/**
 * Media helper instance
 *
 * @var MediaHelper
 */
	public $Media;

	public $View;

	public function setUp() {
		parent::setUp();

		$this->Data = new TestData();
		$this->Data->settings['special'] = $this->Data->settings['base'] . 'special' . DS;
		$this->Data->Folder->create($this->Data->settings['special'] . 'img');
		$this->Data->Folder->create($this->Data->settings['filter'] . 's' . DS . 'static' . DS . 'img');
		$this->Data->Folder->create($this->Data->settings['filter'] . 's' . DS . 'transfer' . DS . 'img');
		$this->Data->Folder->create($this->Data->settings['base'] . 'theme' . DS . 'blanko' . DS . 'img' . DS);

		$this->Data->getFile(array(
			'image-png.png' => $this->Data->settings['special'] . 'img/special-image-&-png.png'
		));
		$this->file0 = $this->Data->getFile(array(
			'image-png.png' => $this->Data->settings['static'] . 'img/image-png.png'
		));
		$this->file1 = $this->Data->getFile(array(
			'image-png.png' => $this->Data->settings['filter'] . 's/static/img/image-png.png'
		));
		$this->file2 = $this->Data->getFile(array(
			'image-png.png' => $this->Data->settings['filter'] . 's/static/img/dot.ted.name.png'
		));
		$this->file3 = $this->Data->getFile(array(
			'image-png.png' => $this->Data->settings['transfer'] . 'img/image-png-x.png'
		));
		$this->file4 = $this->Data->getFile(array(
			'image-png.png' => $this->Data->settings['filter'] . 's/transfer/img/image-png-x.png'
		));
		$this->file5 = $this->Data->getFile(array(
			'image-png.png' => $this->Data->settings['base'] . 'theme/blanko/img/image-blanko.png'
		));

		$settings = array(
			$this->Data->settings['special'] => 'http://fo&o:bar@example.com/media/special[folder]/',
			$this->Data->settings['static'] => 'media/static/',
			$this->Data->settings['filter'] => 'media/filter/',
			$this->Data->settings['transfer'] => false,
			$this->Data->settings['base'] . 'theme' . DS  => 'media/theme/'
		);
		$this->View = new View(null);
		$this->Media = new MediaHelper($this->View, $settings);
		$this->Media->request = new CakeRequest(null, false);
		$this->Media->request->base = '';
		$this->Media->request->here = $this->Media->request->webroot = '/';
	}

	public function tearDown() {
		parent::tearDown();

		$this->Data->cleanUp();
		ClassRegistry::flush();
	}

	public function testConstructWithCustomPaths() {
		$settings = array(
			$this->Data->settings['static']               => 'media/static/',
			$this->Data->settings['base'] . 'theme' . DS  => 'media/theme/'
		);
		$MediaHelper = new MockMediaHelper($this->View, $settings);

		$result = $MediaHelper->paths();
		$expected = array(
			$this->Data->settings['static']               => MEDIA_STATIC_URL,
			MEDIA_TRANSFER                                => MEDIA_TRANSFER_URL,
			MEDIA_FILTER                                  => MEDIA_FILTER_URL,
			$this->Data->settings['base'] . 'theme' . DS  => 'media/theme/'
		);
		$this->assertEqual($result, $expected);

		$settings = array(
			'paths' => array(
				$this->Data->settings['static']               => 'media/static/',
				$this->Data->settings['base'] . 'theme' . DS  => 'media/theme/'
			)
		);
		$MediaHelper = new MockMediaHelper($this->View, $settings);

		$result = $MediaHelper->paths();
		$expected = array(
			$this->Data->settings['static']               => MEDIA_STATIC_URL,
			MEDIA_TRANSFER                                => MEDIA_TRANSFER_URL,
			MEDIA_FILTER                                  => MEDIA_FILTER_URL,
			$this->Data->settings['base'] . 'theme' . DS  => 'media/theme/'
		);
		$this->assertEqual($result, $expected);
	}

	public function testConstructWithCustomPathsAndConfigFile() {
		$settings = array(
			'configFile' => 'media_helper.php',
			'paths'      => array(
				$this->Data->settings['static']               => 'media/static/',
				$this->Data->settings['base'] . 'theme' . DS  => 'media/theme/'
			)
		);
		$MediaHelper = new MockMediaHelper(new View(null), $settings);

		$result = $MediaHelper->paths();
		$expected = array(
			$this->Data->settings['static']               => MEDIA_STATIC_URL,
			MEDIA_TRANSFER                                => MEDIA_TRANSFER_URL,
			MEDIA_FILTER                                  => MEDIA_FILTER_URL,
			$this->Data->settings['base'] . 'theme' . DS  => 'media/theme/'
		);
		$this->assertEqual($result, $expected);

		$result = $MediaHelper->Html->useTag('foo', array('bar' => 'baz', 'qux' => true));
		$expected = '<foo bar="baz" qux="qux"/>';
		$this->assertEqual($result, $expected);

		$result = $MediaHelper->Html->useTag('image', 'foo', array('bar' => 'baz', 'ismap' => true));
		$expected = '<img src="foo"  bar="baz" ismap="ismap"/>';
		$this->assertEqual($result, $expected);

		$result = $MediaHelper->Html->useTag('audio', array('foo' => 'bar'), '<source/>', '<fallback/>');
		$expected = '<audio foo="bar"><source/><fallback/></audio>';
		$this->assertEqual($result, $expected);

		$result = $MediaHelper->Html->useTag('video', array('foo' => 'bar'), '<source/>', '<fallback/>');
		$expected = '<video foo="bar"><source/><fallback/></video>';
		$this->assertEqual($result, $expected);

		$result = $MediaHelper->Html->useTag('source', array('foo' => 'bar'));
		$expected = '<source foo="bar"/>';
		$this->assertEqual($result, $expected);

		$result = $MediaHelper->Html->useTag('object', array('foo' => 'bar'), '<param/>', '<fallback/>');
		$expected = '<object foo="bar"><param/><fallback/></object>';
		$this->assertEqual($result, $expected);

		$result = $MediaHelper->Html->useTag('param', array('foo' => 'bar'));
		$expected = '<param foo="bar"/>';
		$this->assertEqual($result, $expected);
	}

	public function testUrl() {
		$result = $this->Media->url('http://example.com/img/image-png');
		$this->assertNull($result);

		$result = $this->Media->url('img/special-image-&-png');
		$this->assertEqual($result, 'http://fo&o:bar@example.com/media/special%5Bfolder%5D/img/special-image-%26-png.png');

		$result = $this->Media->url('img/image-png');
		$this->assertEqual($result, '/media/static/img/image-png.png');

		$result = $this->Media->url('s/static/img/image-png');
		$this->assertEqual($result, '/media/filter/s/static/img/image-png.png');

		$result = $this->Media->url('img/image-png-x');
		$this->assertNull($result);

		$result = $this->Media->url('img/image-png-xyz');
		$this->assertNull($result);

		$result = $this->Media->url('s/transfer/img/image-png-x');
		$this->assertEqual($result, '/media/filter/s/transfer/img/image-png-x.png');

		$result = $this->Media->url($this->Data->settings['filter'] . 's/transfer/img/image-png-x.png');
		$this->assertEqual($result, '/media/filter/s/transfer/img/image-png-x.png');
	}

	public function testWebroot() {
		$result = $this->Media->webroot('http://example.com/img/image-png');
		$this->assertNull($result);

		$result = $this->Media->webroot('img/special-image-&-png');
		$this->assertEqual($result, 'http://fo&o:bar@example.com/media/special%5Bfolder%5D/img/special-image-%26-png.png');

		$result = $this->Media->webroot('img/image-png');
		$this->assertEqual($result, '/media/static/img/image-png.png');

		$result = $this->Media->webroot('s/static/img/image-png');
		$this->assertEqual($result, '/media/filter/s/static/img/image-png.png');

		$result = $this->Media->webroot('img/image-png-x');
		$this->assertNull($result);

		$result = $this->Media->webroot('img/image-png-xyz');
		$this->assertNull($result);

		$result = $this->Media->webroot('s/transfer/img/image-png-x');
		$this->assertEqual($result, '/media/filter/s/transfer/img/image-png-x.png');

		$result = $this->Media->webroot($this->Data->settings['filter'] . 's/transfer/img/image-png-x.png');
		$this->assertEqual($result, '/media/filter/s/transfer/img/image-png-x.png');
	}

	public function testEmbed() {
		$result = $this->Media->embed('http://example.com/img/image-png');
		$this->assertFalse($result);

		$result = $this->Media->embed('img/image-png', array(
			'url' => 'http://example.com'
		));
		$expected = '<a href="http://example.com"><img src="/media/static/img/image-png.png"  height="54" width="70"/></a>';
		$this->assertEqual($result, $expected);

		$result = $this->Media->embed('img/image-png', array(
			'checked'  => true,
			'disabled' => true,
			'noresize' => true,
			'required' => true
		));
		$expected = '<img src="/media/static/img/image-png.png"  checked="checked" disabled="disabled" noresize="noresize" required="required" height="54" width="70"/>';
		$this->assertEqual($result, $expected);

		$this->Data->getFile(array(
			'audio-mp3.mp3' => $this->Data->settings['special'] . 'img/special-audio-&-mp3.mp3'
		));
		$result = $this->Media->embed('img/special-audio-&-mp3');
		$expected = '<audio controls="controls"><source src="http://fo&amp;o:bar@example.com/media/special%5Bfolder%5D/img/special-audio-%26-mp3.mp3" type="audio/mpeg"/></audio>';
		$this->assertEqual($result, $expected);

		$result = $this->Media->embed('img/special-image-&-png');
		$expected = '<img src="http://fo&amp;o:bar@example.com/media/special%5Bfolder%5D/img/special-image-%26-png.png"  height="54" width="70"/>';
		$this->assertEqual($result, $expected);

		$this->Data->getFile(array(
			'video-wmv.wmv' => $this->Data->settings['special'] . 'img/special-video-&-wmv.wmv'
		));
		$result = $this->Media->embed('img/special-video-&-wmv');
		$expected = '<video controls="controls"><source src="http://fo&amp;o:bar@example.com/media/special%5Bfolder%5D/img/special-video-%26-wmv.wmv" type="video/x-ms-wmv"/></video>';
		$this->assertEqual($result, $expected);

		$result = $this->Media->embed('img/image-png', array(
			'id' => 'my-image',
			'class' => 'image',
			'data-custom' => 42
		));
		$expected = '<img src="/media/static/img/image-png.png"  id="my-image" class="image" data-custom="42" height="54" width="70"/>';
		$this->assertEqual($result, $expected);

		$this->Data->getFile(array(
			'audio-mp3.mp3' => $this->Data->settings['static'] . 'aud/audio-mp3.mp3'
		));
		$result = $this->Media->embed('aud/audio-mp3');
		$expected = '<audio controls="controls"><source src="/media/static/aud/audio-mp3.mp3" type="audio/mpeg"/></audio>';
		$this->assertEqual($result, $expected);

		$result = $this->Media->embed('img/image-png');
		$expected = '<img src="/media/static/img/image-png.png"  height="54" width="70"/>';
		$this->assertEqual($result, $expected);

		$this->Data->getFile(array(
			'video-wmv.wmv' => $this->Data->settings['static'] . 'vid/video-wmv.wmv'
		));
		$result = $this->Media->embed('vid/video-wmv');
		$expected = '<video controls="controls"><source src="/media/static/vid/video-wmv.wmv" type="video/x-ms-wmv"/></video>';
		$this->assertEqual($result, $expected);

		$result = $this->Media->embed('vid/video-wmv', array('poster' => $this->file0));
		$expected = '<video height="54" width="70" controls="controls" poster="/media/static/img/image-png.png"><source src="/media/static/vid/video-wmv.wmv" type="video/x-ms-wmv"/></video>';
		$this->assertEqual($result, $expected);

		$result = $this->Media->embed('non-existent');
		$expected = '';
		$this->assertEqual($result, $expected);
	}

	public function testEmbedAsObject() {
		$result = $this->Media->embedAsObject('http://example.com/img/image-png');
		$this->assertFalse($result);

		$result = $this->Media->embedAsObject('img/image-png', array(
			'url' => 'http://example.com'
		));
		$expected = '<a href="http://example.com"><img src="/media/static/img/image-png.png"  height="54" width="70"/></a>';
		$this->assertEqual($result, $expected);

		$result = $this->Media->embedAsObject('img/image-png', array(
			'checked'  => true,
			'disabled' => true,
			'noresize' => true,
			'required' => true
		));
		$expected = '<object type="image/png" data="/media/static/img/image-png.png" checked="checked" disabled="disabled" noresize="noresize" required="required"><param name="src" value="/media/static/img/image-png.png"/></object>';
		$this->assertEqual($result, $expected);

		$result = $this->Media->embedAsObject('img/special-image-&-png');
		$expected = '<object type="image/png" data="http://fo&amp;o:bar@example.com/media/special%5Bfolder%5D/img/special-image-%26-png.png"><param name="src" value="http://fo&amp;o:bar@example.com/media/special%5Bfolder%5D/img/special-image-%26-png.png"/></object>';
		$this->assertEqual($result, $expected);

		$result = $this->Media->embedAsObject('img/image-png', array(
			'id' => 'my-image',
			'class' => 'image',
			'data-custom' => 42
		));
		$expected = '<object type="image/png" data="/media/static/img/image-png.png" id="my-image" class="image" data-custom="42"><param name="src" value="/media/static/img/image-png.png"/></object>';
		$this->assertEqual($result, $expected);

		$this->Data->getFile(array(
			'video-wmv.wmv' => $this->Data->settings['static'] . 'vid/video-wmv.wmv'
		));
		$result = $this->Media->embedAsObject('vid/video-wmv');
		$expected = '<object type="video/x-ms-wmv" data="/media/static/vid/video-wmv.wmv" classid="clsid:6BF52A52-394A-11d3-B153-00C04F79FAA6"><param name="src" value="/media/static/vid/video-wmv.wmv"/>
<param name="controller" value="true"/>
<param name="pluginspage" value="http://www.microsoft.com/Windows/MediaPlayer/"/></object>';
		$this->assertEqual($result, $expected);

		$this->Data->getFile(array(
			'video-rm.rm' => $this->Data->settings['static'] . 'vid/video-rm.rm'
		));
		$result = $this->Media->embedAsObject('vid/video-rm');
		$expected = '<object type="application/vnd.rn-realmedia" data="/media/static/vid/video-rm.rm" classid="clsid:CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA"><param name="src" value="/media/static/vid/video-rm.rm"/>
<param name="controls" value="ControlPanel"/>
<param name="console" value="video5268463f37ca4"/>
<param name="nologo" value="true"/>
<param name="nojava" value="true"/>
<param name="center" value="true"/>
<param name="pluginspage" value="http://www.real.com/player/"/></object>';
		$pattern = str_replace('video5268463f37ca4', 'video.{13}', preg_quote($expected, '/'));
		$this->assertRegExp('/^' . $pattern . '$/', $result);

		$this->Data->getFile(array(
			'video-mov.mov' => $this->Data->settings['static'] . 'vid/video-mov.mov'
		));
		$result = $this->Media->embedAsObject('vid/video-mov');
		$expected = '<object type="video/quicktime" data="/media/static/vid/video-mov.mov" classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" codebase="http://www.apple.com/qtactivex/qtplugin.cab"><param name="src" value="/media/static/vid/video-mov.mov"/>
<param name="controller" value="true"/>
<param name="pluginspage" value="http://www.apple.com/quicktime/download/"/></object>';
		$this->assertEqual($result, $expected);

		$this->Data->getFile(array(
			'video-mpg.mpg' => $this->Data->settings['static'] . 'vid/video-mpg.mpg'
		));
		$result = $this->Media->embedAsObject('vid/video-mpg');
		$expected = '<object type="video/mpeg" data="/media/static/vid/video-mpg.mpg"><param name="src" value="/media/static/vid/video-mpg.mpg"/></object>';
		$this->assertEqual($result, $expected);

		$this->Data->getFile(array(
			'video-swf.swf' => $this->Data->settings['static'] . 'vid/video-swf.swf'
		));
		$result = $this->Media->embedAsObject('vid/video-swf');
		$expected = '<object type="application/x-shockwave-flash" data="/media/static/vid/video-swf.swf" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab"><param name="movie" value="/media/static/vid/video-swf.swf"/>
<param name="wmode" value="transparent"/>
<param name="FlashVars" value="playerMode=embedded"/>
<param name="quality" value="best"/>
<param name="scale" value="noScale"/>
<param name="salign" value="TL"/>
<param name="pluginspage" value="http://www.adobe.com/go/getflashplayer"/></object>';
		$this->assertEqual($result, $expected);

		$this->Data->getFile(array(
			'application-pdf.pdf' => $this->Data->settings['static'] . 'doc/application-pdf.pdf'
		));
		$result = $this->Media->embedAsObject('doc/application-pdf');
		$expected = '<object type="application/pdf" data="/media/static/doc/application-pdf.pdf"><param name="src" value="/media/static/doc/application-pdf.pdf"/>
<param name="toolbar" value="true"/>
<param name="scrollbar" value="true"/>
<param name="navpanes" value="true"/></object>';
		$this->assertEqual($result, $expected);

		$this->Data->getFile(array(
			'audio-mp3.mp3' => $this->Data->settings['static'] . 'aud/audio-mp3.mp3'
		));
		$result = $this->Media->embedAsObject('aud/audio-mp3');
		$expected = '<object type="audio/mpeg" data="/media/static/aud/audio-mp3.mp3"><param name="src" value="/media/static/aud/audio-mp3.mp3"/></object>';
		$this->assertEqual($result, $expected);

		$result = $this->Media->embedAsObject('img/image-png');
		$expected = '<object type="image/png" data="/media/static/img/image-png.png"><param name="src" value="/media/static/img/image-png.png"/></object>';
		$this->assertEqual($result, $expected);

		$result = $this->Media->embedAsObject('non-existent');
		$expected = '';
		$this->assertEqual($result, $expected);
	}

	public function testFile() {
		$result = $this->Media->file('http://example.com/img/image-png');
		$this->assertFalse($result);

		$result = $this->Media->file('static/img/non-existent.jpg');
		$this->assertFalse($result);

		$result = $this->Media->file('img/image-png');
		$this->assertEqual($result, $this->file0);

		$result = $this->Media->file('s/static/img/image-png');
		$this->assertEqual($result, $this->file1);

		$result = $this->Media->file('s/static/img/dot.ted.name');
		$this->assertEqual($result, $this->file2);

		$result = $this->Media->file('img/image-png-x');
		$this->assertEqual($result, $this->file3);

		$result = $this->Media->file('s/transfer/img/image-png-x');
		$this->assertEqual($result, $this->file4);

		$result = $this->Media->file($this->Data->settings['filter'] . 's/transfer/img/image-png-x.png');
		$this->assertEqual($result, $this->file4);

		$result = $this->Media->file('blanko/img/image-blanko');
		$this->assertEqual($result, $this->file5);
	}

	public function testName() {
		$this->assertEqual($this->Media->name('img/image-png.png'), 'image');
		$this->assertNull($this->Media->name('static/img/non-existent.jpg'));
	}

	public function testMimeType() {
		$this->assertEqual($this->Media->mimeType('img/image-png.png'), 'image/png');
		$this->assertNull($this->Media->mimeType('static/img/non-existent.jpg'));
	}

	public function testSize() {
		$this->assertEqual($this->Media->size('img/image-png.png'), 2032);
		$this->assertNull($this->Media->size('static/img/non-existent.jpg'));
	}

}
