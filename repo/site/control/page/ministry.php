<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Control_Page_Ministry extends Control_Page {
	/* Constants
	-------------------------------*/
	const RANGE = 10;

	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title    = 'JLDMP - Control';
	protected $_class    = 'ministry';
	protected $_template = '/ministry.phtml';
	protected $_msg      = array();
	
	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {
		$ministries = $this->_getMinistries();
		$ministries = $this->_getMinistryImage($ministries);

		// control()->output($ministries);
		// exit;

		$this->_renderMsg();

		$this->_body = array(
			'ministries' => $ministries,
			'msgs' 		 => $this->_msg);

		return $this->_page();
	}
	
	/* Protected Methods
	-------------------------------*/
	protected function _getMinistries() {
		$ministries = $this->_db->search()
			->setTable('ministry m')
			->setColumns('m.*', 'a.*')
			->addInnerJoinOn('admin a', 'a.admin_id = m.ministry_admin')
			->addSort('m.ministry_updated', 'DESC')
			->setRange(self::RANGE)
			->getRows();

		return $ministries;
	}

	protected function _getMinistryImage($ministries) {
		foreach ($ministries as $key => $ministry) {
			$image = $this->_db->search()
				->setTable('file')
				->setColumns('*')
				->addFilter('file_parent = '.$ministry['ministry_id'].' AND file_active = 1 AND file_type = "ministry"')
				->getRow();

			$ministries[$key]['ministry_image'] = $image;
		}

		return $ministries;
	}

	/* Private Methods
	-------------------------------*/
}
