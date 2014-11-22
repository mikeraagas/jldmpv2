<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Front_Page_Ministry_Events extends Front_Page {
	/* Constants
	-------------------------------*/
	const RANGE = 10;

	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title    = 'JLDMP - Ministries';
	protected $_class    = 'ministries';
	protected $_template = '/ministry/events.phtml';
	protected $_id       = null;
	
	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {
		$this->_id   = isset($this->_request['variables'][0]) ? $this->_request['variables'][0] : null;

		$ministry = $this->_getMinistry();
		$events   = $this->_getEvents();
		$events   = $this->_getEventImages($events);

		// front()->output($events);
		// exit;

		$this->_body = array(
			'class'    => 'events',
			'ministry' => $ministry,
			'events'   => $events);

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

		$ministry['ministry_admin'] = $admin; 
		return $ministry;
	}

	protected function _getEvents() {
		$pg 	= isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
		$start 	= ($pg-1) * self::RANGE;
		$filter = array(
			'event_active' => 1,
			'event_ministry' => $this->_id);

		if (isset($_GET['q'])) { $filter['event_title'] = $_GET['q']; }	

		$query = $this->_collection['event']->find($filter)
			->skip($start)
			->limit(self::RANGE)
			->sort(array('ministry_updated' => -1));

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

	/* Private Methods
	-------------------------------*/
}
