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
			'ministry_id' 	=> $detail['ministry']['ministry_id'],
			'ministry' 		=> $detail['ministry'],
			'admin'    		=> $detail['admin']);

		return $this->_page();
	}
	
	/* Protected Methods
	-------------------------------*/
	protected function _getMinistry() {
		$ministry = $this->_db->search()
			->setTable('ministry')
			->setColumns('*')
			->filterByMinistryId($this->_id)
			->getRow();

		$admin = $this->_db->search()
			->setTable('admin')
			->setColumns('admin_name, admin_email')
			->filterByAdminId($ministry['ministry_admin'])
			->getRow();

		$detail = array(
			'ministry' => $ministry,
			'admin'    => $admin);

		return $detail;
	}

	/* Private Methods
	-------------------------------*/
}
