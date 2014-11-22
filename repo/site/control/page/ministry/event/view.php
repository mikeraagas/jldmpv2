<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Control_Page_Ministry_Event_View extends Control_Page {
	/* Constants
	-------------------------------*/
	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title    = 'JLDMP - Control';
	protected $_class    = 'ministry';
	protected $_template = '/ministry/event/view.phtml';

	protected $_ministry = null;
	protected $_id 		 = null;
	protected $_msg 	 = array();

	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {
		$this->_ministry = isset($this->_request['variables'][0]) ? $this->_request['variables'][0] : null;
		$this->_id 		 = isset($this->_request['variables'][1]) ? $this->_request['variables'][1] : null;

		if ($this->_id == null) { header('Location: /ministry/'.$this->_ministry.'/events'); exit; }

		$event = $this->_getEvent();

		$this->_body = array(
			'class' 		=> 'event',
			'msgs' 			=> $this->_msg,
			'event'			=> $event,
			'ministry_id' 	=> $this->_ministry);

		return $this->_page();
	}
	
	/* Protected Methods
	-------------------------------*/
	protected function _getEvent() {
		// get event
		$eventFilter = array(
			'_id'            => new MongoId($this->_id),
			'event_ministry' => $this->_ministry);

		$event = $this->_collection['event']->findOne($eventFilter);
		$eventId = $event['_id']->{'$id'};

		$imageFilter = array(
			'file_parent'  => $eventId,
			'file_type'    => 'event',
			'file_active'  => 1,
			'file_primary' => 1);

		// get event images
		$image = $this->_collection['file']->findOne($imageFilter);
		$event['event_image'] = $image;

		return $event;
	}

	/* Private Methods
	-------------------------------*/
}
