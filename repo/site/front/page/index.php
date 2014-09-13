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
	protected $_title = 'JLDMP - Home';
	protected $_class = 'home';
	protected $_template = '/index.phtml';
	
	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {
		$sort = array('ministry_updated' => -1);

		$q = $this->_collection['ministry']->find()
			->sort($sort)
			->limit(3);

		$ministries = iterator_to_array($q);
		$ministries = $this->_getMinistryImage($ministries);

		// front()->output($ministries);
		// exit;

		$this->_body = array(
			'ministries' => $ministries);

		return $this->_page();
	}
	
	/* Protected Methods
	-------------------------------*/
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
