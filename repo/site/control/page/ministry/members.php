<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Control_Page_Ministry_Members extends Control_Page {
	/* Constants
	-------------------------------*/
	const RANGE = 10;

	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title    = 'JLDMP - Control';
	protected $_class    = 'ministry';
	protected $_template = '/ministry/members.phtml';

	protected $_ministry   = null;
	protected $_memberPath = null;
	protected $_var		   = null;
	protected $_msg 	   = array();	
	
	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {
		$this->_ministry = isset($this->_request['variables'][0]) ? $this->_request['variables'][0] : null;
		$this->_var 	 = isset($this->_request['variables'][1]) ? $this->_request['variables'][1] : null;

		// set file upload member path
		$this->_memberPath = dirname(__FILE__).'/../../../../../uploads/member/';

		if (isset($_GET['action']) && $_GET['action'] == 'deleteMember') { $this->_deleteMember($_GET['id']); }

		$members = $this->_getMembers();
		$members = $this->_getMemberImages($members);

		// set pagination
		$pg    = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
		$count = $this->_countMembers();
		$pages = ceil($count/self::RANGE);

		$this->_renderMsg();

		$this->_body = array(
			'class' 		=> 'member',
			'var'			=> $this->_var,
			'msg'			=> $this->_msg,
			'pg'			=> $pg,
			'pages'			=> $pages,
			'ministry_id'	=> $this->_ministry,
			'members'		=> $members);

		return $this->_page();
	}
	
	/* Protected Methods
	-------------------------------*/
	protected function _getMembers() {
		$pg 	= isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
		$start 	= ($pg-1) * self::RANGE;
		$filter = $this->_setFilter();

		$query = $this->_collection['member']->find($filter)
			->skip($start)
			->limit(self::RANGE)
			->sort(array('ministry_updated' => -1));

		$members = iterator_to_array($query);		
		return $members;
	}

	protected function _getMemberImages($members) {
		foreach ($members as $key => $member) {
			$filter = array(
				'file_parent' => $member['_id']->{'$id'},
				'file_active' => 1,
				'file_type'   => 'member');

			$image = $this->_collection['file']->findOne($filter);

			$members[$key]['member_image'] = $image;
		}

		return $members;
	}

	protected function _countMembers() {
		$filter = $this->_setFilter();
		$query  = $this->_collection['member']->find($filter);
		$count  = $query->count();

		return $count;
	}

	protected function _setFilter() {
		$filter = array(
			'member_ministry' => $this->_ministry,
			'member_active'   => 1);

		switch ($this->_var) {
			case 'active':		$filter['member_active'] = 1; break;
			case 'not-active':	$filter['member_active'] = 0; break;
			case 'request':		$filter['member_active'] = 2; break;
		}

		if (isset($_GET['q'])) { $filter['member_name'] = $_GET['q']; }
		
		return $filter;
	}

	protected function _deleteMember($id) {
		$filter = array('_id' => new MongoId($id));
		$this->_collection['member']->remove($filter, array('justOne' => true));

		// remove member image
		$fileFilter = array(
			'file_parent' => $id,
			'file_type'   => 'member');

		// get file id
		$file = $this->_collection['file']->findOne($fileFilter);

		$removeFileFilter = array('_id' => new MongoId($file['_id']));
		$this->_collection['file']->remove($removeFileFilter, array('justOne' => true));
		
		unlink($this->_memberPath.$file['file_name']);

		$_SESSION['msg'][] = array(
			'type' => 'success',
			'msg'  => 'Member successfully deleted.');

		header('Location: /ministry/'.$this->_ministry.'/members');
		exit;
	}

	/* Private Methods
	-------------------------------*/
}
