<?php
/**
 * Media Shell File
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
 * @package       Media.Console.Command
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

App::uses('ConnectionManager', 'Model');
App::uses('Shell', 'Console');
App::uses('Folder', 'Utility');

Configure::write('Cache.disable', true);

/**
 * Media Shell Class
 *
 * @package       Media.Console.Command
 *
 * @property MakeTask $Make
 * @property SyncTask $Sync
 */
class MediaShell extends Shell {

/**
 * Tasks
 *
 * @var array
 */
	public $tasks = array('Media.Sync', 'Media.Make');

/**
 * Verbose mode
 *
 * @var boolean
 */
	public $verbose = false;

/**
 * Quiet mode
 *
 * @var boolean
 */
	public $quiet = false;

/**
 * Startup
 *
 * @return void
 */
	public function startup() {
		$this->verbose = isset($this->params['verbose']);
		$this->quiet = isset($this->params['quiet']);
		parent::startup();
	}

/**
 * Welcome
 *
 * @return void
 */
	protected function _welcome() {
		$this->hr();
		$this->out(__d('media_console', 'Media Shell'));
		$this->hr();
	}

/**
 * Main
 *
 * @return void
 */
	public function main() {
		$this->out(__d('media_console', '[I]nitialize Media Directory'));
		$this->out(__d('media_console', '[P]rotect Transfer Directory'));
		$this->out(__d('media_console', '[S]ynchronize'));
		$this->out(__d('media_console', '[M]ake'));
		$this->out(__d('media_console', '[H]elp'));
		$this->out(__d('media_console', '[Q]uit'));

		$action = $this->in(
			__d('media_console', 'What would you like to do?'),
			array('I', 'P', 'S', 'M', 'H', 'Q'),
			'q'
		);

		$this->out();

		switch (strtoupper($action)) {
			case 'I':
				$this->init();
				break;
			case 'P':
				$this->protect();
				break;
			case 'S':
				$this->Sync->execute();
				break;
			case 'M':
				$this->Make->execute();
				break;
			case 'C':
				$this->Collect->execute();
				break;
			case 'H':
				$this->help();
				break;
			case 'Q':
				$this->_stop();
		}
		$this->main();
	}

/**
 * Initializes directory structure
 *
 * @return void
 */
	public function init() {
		$message = __d('media_console', 'Do you want to create missing media directories now?');
		if ($this->in($message, 'y,n', 'n') == 'n') {
			return;
		}

		$short = array('aud', 'doc', 'gen', 'img', 'vid');

		$dirs = array(
			MEDIA => array(),
			MEDIA_STATIC => $short,
			MEDIA_TRANSFER => $short,
			MEDIA_FILTER => array(),
		);

		foreach ($dirs as $dir => $subDirs) {
			if (is_dir($dir)) {
				$result = 'SKIP';
			} else {
				new Folder($dir, true);

				if (is_dir($dir)) {
					$result = 'OK';
				} else {
					$result = 'FAIL';
				}
			}
			$this->out(sprintf('%-50s [%-4s]', $this->shortPath($dir), $result));

			foreach ($subDirs as $subDir) {
				if (is_dir($dir . $subDir)) {
					$result = 'SKIP';
				} else {
					new Folder($dir . $subDir, true);

					if (is_dir($dir . $subDir)) {
						$result = 'OK';
					} else {
						$result = 'FAIL';
					}
				}
				$this->out(sprintf('%-50s [%-4s]', $this->shortPath($dir . $subDir), $result));
			}
		}

		$this->out();
		$this->protect();
		$this->out(__d('media_console', 'Remember to set the correct permissions on transfer and filter directory.'));
	}

/**
 * Protects the transfer directory
 *
 * @return boolean
 */
	public function protect() {
		if (MEDIA_TRANSFER_URL === false) {
			$this->out(__d('media_console', 'The content of the transfer directory is not served.'));
			return true;
		}

		$file = MEDIA_TRANSFER . '.htaccess';

		if (is_file($file)) {
			$this->err(__d('media_console', '%s is already present.', $this->shortPath($file)));
			return true;
		}
		if (strpos(MEDIA_TRANSFER, WWW_ROOT) === false) {
			$this->err(__d('media_console', '%s is not in your webroot.', $this->shortPath($file)));
			return true;
		}
		$this->out(__d('media_console', 'Your transfer directory is missing a htaccess file to block requests.'));

		if ($this->in(__d('media_console', 'Do you want to create it now?'), 'y,n', 'n') == 'n') {
			return false;
		}

		$File = new File($file);
		$File->append("Order deny,allow\n");
		$File->append("Deny from all\n");
		$File->close();

		$this->out(__d('media_console', '%s created.', $this->shortPath($File->pwd())));
		$this->out('');
		return true;
	}

/**
 * Displays help contents
 *
 * @return void
 */
	public function help() {
		// 63 chars ===============================================================
		$this->out("NAME");
		$this->out("\tmedia -- the 23rd shell");
		$this->out('');
		$this->out("SYNOPSIS");
		$this->out("\tcake media <params> <command> <args>");
		$this->out('');
		$this->out("COMMANDS");
		$this->out("\tinit");
		$this->out("\t\tInitializes the media directory structure.");
		$this->out('');
		$this->out("\tprotect");
		$this->out("\t\tCreates a htaccess file to protect the transfer dir.");
		$this->out('');
		$this->out("\tsync [-auto] [model] [searchdir]");
		$this->out("\t\tChecks if records are in sync with files and vice versa.");
		$this->out('');
		$this->out("\t\t-auto Automatically repair without asking for confirmation.");
		$this->out('');
		$this->out("\tmake [-force] [-version name] [sourcefile/sourcedir] [destinationdir]");
		$this->out("\t\tProcess a file or directory according to filters.");
		$this->out('');
		$this->out("\t\t-version name Restrict command to a specific filter version (e.g. xxl).");
		$this->out("\t\t-force Overwrite files if they exist.");
		$this->out('');
		$this->out("\thelp");
		$this->out("\t\tShows this help message.");
		$this->out('');
		$this->out("OPTIONS");
		$this->out("\t-verbose");
		$this->out("\t-quiet");
		$this->out('');
	}

/**
 * progress
 *
 * Start with progress(target value)
 * Update with progress(current value, text)
 *
 * @param mixed $value
 * @param mixed $text
 * @return void
 */
	public function progress($value, $text = null) {
		static $target = 0;

		if ($this->quiet) {
			return null;
		}

		if ($text === null) {
			$target = $value;
		} else {
			$out = sprintf('%\' 6.2f%% %s', ($value * 100) / $target, $text);
			$this->out($out);
		}
	}

}
