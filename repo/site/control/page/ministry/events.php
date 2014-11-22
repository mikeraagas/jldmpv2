<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Control_Page_Ministry_Events extends Control_Page {
	/* Constants
	-------------------------------*/
	const RANGE = 10;

	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title    = 'JLDMP - Control';
	protected $_class    = 'ministry';
	protected $_template = '/ministry/events.phtml';

	protected $_ministry 	= null;
	protected $_var 	 	= null;
	protected $_eventPath   = null;
	protected $_msg			= array();
	
	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {
		$this->_ministry = isset($this->_request['variables'][0]) ? $this->_request['variables'][0] : null;
		$this->_var 	 = isset($this->_request['variables'][1]) ? $this->_request['variables'][1] : null;

		if (isset($_GET['action']) && $_GET['action'] == 'remove_event') $this->_removeEvent($_GET['id']);

		// set pagination
		$pg    = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
		$count = $this->_countEvents();
		$pages = ceil($count/self::RANGE);

		$events = $this->_getEvents();
		$events = $this->_getEventImages($events);

		$this->_renderMsg();

		$this->_body = array(
			'class'			=> 'event',
			'var'			=> $this->_var,
			'msg'			=> $this->_msg,
			'pg'			=> $pg,
			'pages'			=> $pages,
			'events'		=> $events,
			'ministry_id' 	=> $this->_ministry);

		return $this->_page();
	}
	
	/* Protected Methods
	-------------------------------*/
	protected function _getEvents() {
		$pg 	= isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
		$start 	= ($pg-1) * self::RANGE;
		$filter = $this->_setFilter();

		$query = $this->_collection['event']->find($filter)
			->skip($start)
			->limit(self::RANGE)
			->sort(array('event_updated' => -1));

		$events = iterator_to_array($query);
		return $events;
	}

	protected function _getEventImages($events) {
		foreach ($events as $key => $event) {
			$filter = array(
				'file_parent'  => $event['_id']->{'$id'},
				'file_active'  => 1,
				'file_primary' => 1,
				'file_type'    => 'event');

			$image = $this->_collection['file']->findOne($filter);
			$events[$key]['event_image'] = $image;
		}

		return $events;
	}

	protected function _setFilter() {
		$filter = array(
			'event_active'   => 1,
			'event_ministry' => $this->_ministry);

		switch ($this->_var) {
			case 'active':		$filter['event_active'] = 1; break;
			case 'not-active':	$filter['event_active'] = 0; break;
		}

		if (isset($_GET['q'])) { $filter['event_title'] = $_GET['q']; }		
		return $filter;
	}

	protected function _countEvents() {
		$filter = $this->_setFilter();
		$query  = $this->_collection['event']->find($filter);
		$count  = $query->count();

		return $count; 
	}

	protected function _removeEvent($id) {
		$this->_eventPath = dirname(__FILE__).'/../../../../../uploads/event/';

		// remove images
		$filter = array(
			'file_parent' => $id,
			'file_active' => 1,
			'file_type'   => 'event');

		$images = $this->_collection['file']->find($filter);

		if (!empty($images)) {
			foreach ($images as $image) {
				unlink($this->_eventPath.$image['file_name']);
			}

			$imageFilter = array(
				'file_parent' => $id,
				'file_active' => 1,
				'file_type'   => 'event');

			$this->_collection['file']->remove($imageFilter, array('justOne' => false));
		}

		$eventFilter = array('_id' => new MongoId($id));
		$this->_collection['event']->remove($eventFilter, array('justOne' => true));

		header('Location: /ministry/'.$this->_ministry.'/events');
		exit;
	}

	/* Private Methods
	-------------------------------*/
}
