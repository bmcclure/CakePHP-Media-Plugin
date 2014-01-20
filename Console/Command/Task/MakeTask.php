<?php
/**
 * Make Task File
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
 * @package       Media.Console.Command.Task
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

App::uses('MediaShell', 'Media.Console/Command');

/**
 * Make Task Class
 *
 * @package       Media.Console.Command.Task
 */
class MakeTask extends MediaShell {

	public $source;

	public $model;

	protected $_Model;

/**
 * Main execution method
 *
 * @return void
 */
	public function execute() {
		if (isset($this->params['model'])) {
			$this->model = $this->params['model'];
		} else {
			$this->model = $this->in(__d('media_console', 'Model'), null, 'Media.Attachment');
		}
		$this->_Model = ClassRegistry::init($this->model);

		if (!isset($this->_Model->Behaviors->Generator)) {
			$this->error(__d('media_console', 'Model `%s` has the `Generator` behavior not attached to it.', $this->model));
		}
		$settings = $this->_Model->Behaviors->Generator->settings[$this->_Model->alias];

		if (!$this->source = array_shift($this->args)) {
			$this->source = $this->in(__d('media_console', 'Source directory'), null, $settings['baseDirectory']);
		}
		$message = __d('media_console', 'Regex (matches against the basenames of the files) for source inclusion:');
		$pattern = $this->in($message, null, '.*');

		$this->out();
		$this->out(sprintf('%-25s: %s', __d('media_console', 'Base'), $this->shortPath($settings['baseDirectory'])));
		$this->out(sprintf('%-25s: %s (%s)', __d('media_console', 'Source'), $this->shortPath($this->source), $pattern));
		$this->out(sprintf('%-25s: %s', __d('media_console', 'Destination'), $this->shortPath($settings['filterDirectory'])));
		$this->out(sprintf('%-25s: %s', __d('media_console', 'Overwrite existing'), $settings['overwrite'] ? 'yes' : 'no'));
		$this->out(sprintf('%-25s: %s', __d('media_console', 'Create directories'), $settings['createDirectory'] ? 'yes' : 'no'));

		if ($this->in(__d('media_console', 'Looks OK?'), 'y,n', 'y') == 'n') {
			return;
		}
		$this->out();
		$this->out('Making');
		$this->hr();

		$Folder = new Folder($this->source);
		$files = $Folder->findRecursive($pattern);

		$this->progress(count($files));

		foreach ($files as $key => $file) {
			$this->progress($key, $this->shortPath($file));
			$this->_Model->make($file);
		}
		$this->out();
	}

}
