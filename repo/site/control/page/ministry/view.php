<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Control_Page_Ministry_View extends Control_Page {
	/* Constants
	-------------------------------*/
	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title    = 'JLDMP - Control';
	protected $_class    = 'ministry';
	protected $_template = '/ministry/view.phtml';

	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {
		$this->_id = isset($this->_request['variables'][0]) ? $this->_request['variables'][0] : null;

		$detail = $this->_getMinistry();

		$this->_body = array(
			'class'			=> 'view',
			'ministry_id' 	=> $detail['ministry']['_id']->{'$id'},
			'ministry' 		=> $detail['ministry'],
			'admin'    		=> $detail['admin']);

		return $this->_page();
	}
	
	/* Protected Methods
	-------------------------------*/
	protected function _getMinistry() {
		$ministryFilter = array('_id' => new MongoId($this->_id));
		$ministry = $this->_collection['ministry']->findOne($ministryFilter);

		$filter = array(
			'file_parent' => $ministry['_id']->{'$id'},
			'file_active' => 1,
			'file_type'   => 'ministry');

		$image = $this->_collection['file']->findOne($filter);

		if (!empty($image)) {
			$ministry['ministry_image'] = $image;
		}

		$adminFilter = array('_id' => new MongoId($ministry['ministry_admin']));
		$admin = $this->_collection['admin']->findOne($adminFilter);

		$detail = array(
			'ministry' => $ministry,
			'admin'    => $admin);

		return $detail;
	}

	/* Private Methods
	-------------------------------*/
}
