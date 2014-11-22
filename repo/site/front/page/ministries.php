<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Front_Page_Index extends Front_Page {
	/* Constants
	-------------------------------*/
	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title = 'JLDMP - Ministries';
	protected $_class = 'ministries';
	protected $_template = '/ministries.phtml';
	
	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {
		// get ministries
		$ministries = $this->_getMinistries();
		$ministries = $this->_getMinistryImage($ministries);
		// front()->output($ministries);
		// exit;

		$this->_body = array(
			'ministries' => $ministries);

		return $this->_page();
	}
	
	/* Protected Methods
	-------------------------------*/
	protected function _getMinistries() {
		$q = $this->_collection['ministry']->find();
		$q = $q->sort(array('ministry_updated' => -1));
		$ministries = iterator_to_array($q);

		// get ministry admin info
		foreach ($ministries as $key => $value) {
			$filter = array('_id' => new MongoId($value['ministry_admin']));
			$admin  = $this->_collection['admin']->findOne($filter);

			$ministries[$key]['admin'] = $admin;
		}
		
		return $ministries;
	}

	protected function _getMinistryImage($ministries) {
		foreach ($ministries as $key => $ministry) {
			$filter = array(
				'file_parent' => $ministry['_id']->{'$id'},
				'file_active' => 1,
				'file_type'   => 'ministry');

			$image = $this->_collection['file']->findOne($filter);
			$ministries[$key]['ministry_image'] = $image;
		}

		return $ministries;
	}

	/* Private Methods
	-------------------------------*/
}
