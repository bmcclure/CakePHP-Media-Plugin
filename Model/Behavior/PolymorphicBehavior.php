<?php
/**
 * Polymorphic Behavior.
 *
 * Allow the model to be associated with any other model object
 *
 * PHP 5
 * CakePHP 2
 *
 * Copyright (c) 2008, Andy Dawson
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) 2008, Andy Dawson
 * @link          www.ad7six.com
 * @package       Base.Model.Behavior
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

App::uses('ModelBehavior', 'Model');

/**
 * PolymorphicBehavior class
 *
 * @package       Base.Model.Behavior
 */
class PolymorphicBehavior extends ModelBehavior {

/**
 * defaultSettings property
 *
 * @var array
 * @access protected
 */
	protected $_defaultSettings = array(
		'modelField' => 'model',
		'foreignKey' => 'foreign_key'
	);

/**
 * setup method
 *
 * @param Model $Model Model using this behavior
 * @param array $settings Configuration settings for $Model
 * @return void
 */
	public function setup(Model $Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = $this->_defaultSettings;
		}

		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array)$settings);
	}

/**
 * afterFind method
 *
 * @param Model $Model Model using this behavior
 * @param mixed $results The results of the find operation
 * @param boolean $primary Whether this model is being queried directly (vs. being queried as an association)
 * @return mixed An array value will replace the value of $results - any other value will be ignored.
 */
	public function afterFind(Model $Model, $results, $primary = false) {
		extract($this->settings[$Model->alias]);
		/* @var $modelField string */
		/* @var $foreignKey string */

		if (App::import('Vendor', 'MiCache')) {
			$models = MiCache::mi('models');
		} else {
			$models = App::objects('Model');
		}
		if ($primary && isset($results[0][$Model->alias][$modelField]) && isset($results[0][$Model->alias][$foreignKey]) && $Model->recursive > 0) {
			foreach ($results as $key => $result) {
				$model = Inflector::classify($result[$Model->alias][$modelField]);
				$foreignId = $result[$Model->alias][$foreignKey];
				if ($model && $foreignId && in_array($model, $models)) {
					$result = $result[$Model->alias];
					if (!isset($Model->$model)) {
						$Model->bindModel(array('belongsTo' => array(
							$model => array(
								'conditions' => array($Model->alias . '.' . $modelField => $model),
								'foreignKey' => $foreignKey
							)
						)));
					}
					$conditions = array($model . '.' . $Model->$model->primaryKey => $result[$foreignKey]);
					$recursive = -1;
					$associated = $Model->$model->find('first', compact('conditions', 'recursive'));
					$name = $Model->$model->display($result[$foreignKey]);
					$associated[$model]['display_field'] = $name?$name:'*missing*';
					$results[$key][$model] = $associated[$model];
				}
			}
		} elseif (isset($results[$Model->alias][$modelField])) {
			$model = Inflector::classify($results[$Model->alias][$modelField]);
			$foreignId = $results[$Model->alias][$foreignKey];
			if ($model && $foreignId) {
				$result = $results[$Model->alias];
				if (!isset($Model->$model)) {
					$Model->bindModel(array('belongsTo' => array(
						$model => array(
							'conditions' => array($Model->alias . '.' . $modelField => $model),
							'foreignKey' => $foreignKey
						)
					)));
				}
				$conditions = array($model . '.' . $Model->$model->primaryKey => $result[$foreignKey]);
				$recursive = -1;
				$associated = $Model->$model->find('first', compact('conditions', 'recursive'));
				$name = $Model->$model->display($result[$foreignKey]);
				$associated[$model]['display_field'] = $name?$name:'*missing*';
				$results[$model] = $associated[$model];
			}
		}
		return $results;
	}

/**
 * display method
 *
 * Fall back. Assumes that find list is setup such that it returns users real names
 *
 * @param Model $Model
 * @param mixed $id
 * @return mixed
 */
	public function display(Model $Model, $id = null) {
		if (!$id) {
			if (!$Model->id) {
				return false;
			}
			$id = $Model->id;
		}
		return current($Model->find('list', array('conditions' => array($Model->alias . '.' . $Model->primaryKey => $id))));
	}

}
