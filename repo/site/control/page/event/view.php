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
		$eventFilter = array('_id' => new MongoId($this->_id));
		$event = $this->_collection['event']->findOne($eventFilter);

		$filter = array(
			'file_parent' => $event['_id']->{'$id'},
			'file_active' => 1,
			'file_type'   => 'event');

		$query  = $this->_collection['file']->find($filter);
		$images = iterator_to_array($query);

		$event['event_images'] = $images;

		return $event;
	}

	/* Private Methods
	-------------------------------*/
}
