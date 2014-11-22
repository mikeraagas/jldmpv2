<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Control_Page_Ministries extends Control_Page {
	/* Constants
	-------------------------------*/
	const RANGE = 10;

	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title    = 'JLDMP - Control';
	protected $_class    = 'ministry';
	protected $_template = '/ministries.phtml';
	protected $_msg      = array();
	
	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {
		$this->_var = isset($this->_request['variables'][0]) ? $this->_request['variables'][0] : null;

		if (isset($_GET['action']) && $_GET['action'] == 'remove_ministry') {
			if (!isset($_GET['id'])) { header('Location: /ministries'); exit; }
			$this->_removeMinistry($_GET['id']);
		}

		$ministries = $this->_getMinistries();
		$ministries = $this->_getMinistryImage($ministries);

		// set pagination
		$pg    = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
		$count = $this->_countMinistries();
		$pages = ceil($count/self::RANGE);

		// control()->output($ministries);
		// exit;

		$this->_renderMsg();

		$this->_body = array(
			'var'        => $this->_var,
			'ministries' => $ministries,
			'msgs' 		 => $this->_msg,
			'pg'         => $pg,
			'pages'      => $pages);

		return $this->_page();
	}
	
	/* Protected Methods
	-------------------------------*/
	protected function _getMinistries() {
		$pg 	= isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
		$start 	= ($pg-1) * self::RANGE;
		$filter = $this->_setFilter();

		$query = $this->_collection['ministry']->find($filter)
			->skip($start)
			->limit(self::RANGE)
			->sort(array('ministry_updated' => -1));

		$ministries = iterator_to_array($query);

		foreach ($ministries as $key => $value) {
			$filter = array('_id' => new MongoId($value['ministry_admin']));
			$admin  = $this->_collection['admin']->findOne($filter);

			$ministries[$key]['admin'] = $admin;
		}
		
		return $ministries;
	}

	protected function _getMinistryImage($ministries) {
		foreach ($ministries as $key => $ministry) {
			$filter = array(
				'file_parent' => $ministry['_id']->{'$id'},
				'file_active' => 1,
				'file_type'   => 'ministry');

			$image = $this->_dbFile->findOne($filter);

			$ministries[$key]['ministry_image'] = $image;
		}

		return $ministries;
	}

	protected function _countMinistries() {
		$filter = $this->_setFilter();
		$query  = $this->_collection['ministry']->find($filter);
		$count  = $query->count();

		return $count;
	}

	protected function _setFilter() {
		$filter = array();

		switch ($this->_var) {
			case 'active':		$filter['ministry_active'] = 1; break;
			case 'not-active':	$filter['ministry_active'] = 0; break;
		}

		if (isset($_GET['q'])) { $filter['member_title'] = array('$regex' => $_GET['q']); }
		
		return $filter;
	}

	protected function _removeMinistry($id) {
		$ministryPath = dirname(__FILE__).'/../../../../uploads/ministry/';

		// get ministry info
		$ministryFilter = array('_id' => new MongoId($id));
		$ministry = $this->_dbMinistry->findOne($ministryFilter);

		// get ministry admin info
		$adminFilter = array('_id' => new MongoId($ministry['ministry_admin']));
		$admin = $this->_dbAdmin->findOne($adminFilter);

		// get ministry image info
		$fileFilter = array(
			'file_parent' => $id,
			'file_active' => 1,
			'file_type'   => 'ministry');

		$file = $this->_dbFile->findOne($fileFilter);

		// remove ministry
		$removeMinistry = array('_id' => new MongoId($id));
		$this->_dbMinistry->remove($removeMinistry, array('justOne' => true));

		// remove ministry admin
		$removeAdmin = array('_id' => new MongoId($admin['_id']->{'$id'}));
		$this->_dbAdmin->remove($removeAdmin, array('justOne' => true));

		// remove ministry image
		$removeImage = array('_id' => new MongoId($file['_id']->{'$id'}));
		$this->_dbFile->remove($removeImage, array('justOne' => true));

		// delete image file from server
		unlink($ministryPath.$file['file_name']);

		$_SESSION['msg'][] = array(
			'type' => 'success',
			'msg'  => 'Successfully removed ministry!');

		header('Location: /ministries');
		exit;
	}

	/* Private Methods
	-------------------------------*/
}
