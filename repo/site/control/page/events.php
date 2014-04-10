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
		$events = $this->_getEventImages($events);

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

		$q = $this->_db->search()
			->setTable('event')
			->setColumns('*');

		if ($filter != '') { $q = $q->addFilter($filter); }

		$events = $q->addSort('event_updated', 'DESC')
			->setStart($start)
			->setRange(self::RANGE)
			->getRows();

		return $events;
	}

	protected function _getEventImages($events) {
		foreach ($events as $key => $event) {
			$images = $this->_db->search()
				->setTable('file')
				->setColumns('*')
				->addFilter('file_parent = '.$event['event_id'].' AND file_active = 1 AND file_primary = 1 AND file_type = "event"')
				->getRow();

			$events[$key]['event_image'] = $images;
		}

		return $events;
	}

	protected function _setFilter() {
		$filter = '';

		switch ($this->_var) {
			case 'active':		$filter = 'event_active = 1'; break;
			case 'not-active':	$filter = 'event_active = 0'; break;
		}

		if ($filter != '' && isset($_GET['q'])) { $filter .= ' AND '; }
		if (isset($_GET['q'])) { $filter .= 'event_title like "%'.$_GET['q'].'%"'; }
		
		return $filter;
	}

	protected function _countEvents() {
		$filter = $this->_setFilter();

		$q = $this->_db->search()
			->setTable('event')
			->setColumns('count(*) as c');

		if ($filter != '') { $this->_setFilter(); }

		$count = $q->getRow();

		return $count['c']; 
	}

	protected function _removeEvent($id) {
		$this->_eventPath = dirname(__FILE__).'/../../../../uploads/event/';

		// remove images
		$images = $this->_db->search()
			->setTable('file')
			->setColumns('*')
			->addFilter('file_parent = '.$id.' AND file_active = 1 AND file_type = "event"')
			->getRows();

		if (!empty($images)) {
			foreach ($images as $image) {
				unlink($this->_eventPath.$image['file_name']);
			}

			$imagesFilter[] = array('file_parent=%s', $id);
			$imagesFilter[] = array('file_active=%s', 1);
			$imagesFilter[] = array('file_type=%s', 'event');

			$this->_db->deleteRows('file', $imagesFilter);
		}

		// delete event
		$filter[] = array('event_id=%s', $id);
		$this->_db->deleteRows('event', $filter);

		header('Location: /events');
		exit;
	}

	/* Private Methods
	-------------------------------*/
}
