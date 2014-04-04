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

		// control()->output($events);
		// exit;

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

		$q = $this->_db->search()
			->setTable('event')
			->setColumns('*')
			->addFilter('event_ministry = '.$this->_ministry);

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
			->setColumns('count(*) as c')
			->addFilter('event_ministry = '.$this->_ministry);

		if ($filter != '') { $this->_setFilter(); }

		$count = $q->getRow();

		return $count['c']; 
	}

	protected function _removeEvent($id) {
		$filter[] = array('event_id=%s', $id);
		$this->_db->deleteRows('event', $filter);

		header('Location: /ministry/'.$this->_ministry.'/events');
		exit;
	}

	/* Private Methods
	-------------------------------*/
}
