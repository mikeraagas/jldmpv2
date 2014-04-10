<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Control_Page_Event_View extends Control_Page {
	/* Constants
	-------------------------------*/
	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title    = 'JLDMP - Control';
	protected $_class    = 'event';
	protected $_template = '/event/view.phtml';

	protected $_id 		 = null;
	protected $_msg 	 = array();

	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {
		$this->_id = isset($this->_request['variables'][0]) ? $this->_request['variables'][0] : null;

		if ($this->_id == null) { header('Location: /events'); exit; }

		$event 	= $this->_getEvent();

		$this->_body = array(
			'class'	=> 'event',
			'msgs'	=> $this->_msg,
			'event'	=> $event);

		return $this->_page();
	}
	
	/* Protected Methods
	-------------------------------*/
	protected function _getEvent() {
		$event = $this->_db->search()
			->setTable('event')
			->setColumns('*')
			->addFilter('event_id = "'.$this->_id.'"')
			->getRow();

		$images = $this->_db->search()
			->setTable('file')
			->setColumns('*')
			->addFilter('file_parent = '.$event['event_id'].' AND file_type = "event" AND file_active = 1')
			->getRows();

		$event['event_images'] = $images;

		return $event;
	}

	/* Private Methods
	-------------------------------*/
}
