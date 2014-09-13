<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Control_Page_Events extends Control_Page {
	/* Constants
	-------------------------------*/
	const RANGE = 10;

	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title    = 'JLDMP - Control';
	protected $_class    = 'event';
	protected $_template = '/events.phtml';

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
		$this->_var = isset($this->_request['variables'][0]) ? $this->_request['variables'][0] : null;

		if (isset($_GET['action']) && $_GET['action'] == 'remove_event') $this->_removeEvent($_GET['id']);

		// set pagination
		$pg    = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
		$count = $this->_countEvents();
		$pages = ceil($count/self::RANGE);

		$events = $this->_getEvents();
		$events = $this->_getEventImage($events);

		$this->_renderMsg();

		$this->_body = array(
			'class'			=> 'event',
			'var'			=> $this->_var,
			'msg'			=> $this->_msg,
			'pg'			=> $pg,
			'pages'			=> $pages,
			'events'		=> $events);

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

	protected function _getEventImage($events) {
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
		$filter = array();

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
		$this->_eventPath = dirname(__FILE__).'/../../../../uploads/event/';

		// remove images
		$fileFilter = array(
			'file_parent' => $id,
			'file_type'   => 'event');

		$query  = $this->_collection['file']->find($fileFilter);
		$images = iterator_to_array($query);
		
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

		$filter = array('_id' => new MongoId($id));
		$this->_collection['event']->remove($filter, array('justOne' => true));

		header('Location: /events');
		exit;
	}

	/* Private Methods
	-------------------------------*/
}
