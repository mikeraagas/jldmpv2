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

	protected $_ministry = null;
	protected $_var		 = null;
	protected $_msg 	 = array();	
	
	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {
		$this->_ministry = isset($this->_request['variables'][0]) ? $this->_request['variables'][0] : null;
		$this->_var 	 = isset($this->_request['variables'][1]) ? $this->_request['variables'][1] : null;

		if (isset($_GET['action']) && $_GET['action'] == 'deleteMember') { $this->_deleteMember($_GET['id']); }

		// set pagination
		$pg    = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
		$count = $this->_countMembers();
		$pages = ceil($count/self::RANGE);

		$members = $this->_getMembers();
		$members = $this->_getMemberImage($members);

		// control()->output($members);
		// exit;

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

		$q = $this->_db->search()
			->setTable('member m')
			->setColumns('m.*')
			->addInnerJoinOn('`group` g', 'g.group_member = m.member_id')
			->addFilter('g.group_ministry = '.$this->_ministry);

		if ($filter != '') { $q = $q->addFilter($filter); }

		$members = $q->addSort('m.member_updated', 'DESC')
			->setStart($start)
			->setRange(self::RANGE)
			->getRows();

		return $members;
	}

	protected function _getMemberImage($members) {
		foreach ($members as $key => $member) {
			$images = $this->_db->search()
				->setTable('file')
				->setColumns('*')
				->addFilter('file_parent = '.$member['member_id'].' AND file_active = 1 AND file_primary = 1 AND file_type = "member"')
				->getRow();

			$members[$key]['member_image'] = $images;
		}

		return $members;
	}

	protected function _countMembers() {
		$q = $this->_db->search()
			->setTable('member m')
			->setColumns('count(*) as c')
			->addInnerJoinOn('`group` g', 'g.group_member = m.member_id')
			->addFilter('g.group_ministry = '.$this->_ministry);

		if (isset($_GET['q'])) { $q = $q->addFilter('m.member_fullname like "%'.$_GET['q'].'%"'); }

		$count = $q->getRow();

		return $count['c'];
	}

	protected function _setFilter() {
		$filter = '';

		switch ($this->_var) {
			case 'active':		$filter = 'm.member_active = 1'; break;
			case 'not-active':	$filter = 'm.member_active = 0'; break;
			case 'request':		$filter = 'm.member_active = 2'; break;
		}

		if ($filter != '' && isset($_GET['q'])) { $filter .= ' AND '; }
		if (isset($_GET['q'])) { $filter .= 'm.member_fullname like "%'.$_GET['q'].'%"'; }
		
		return $filter;
	}

	protected function _deleteMember($id) {
		$filter[] = array('member_id=%s', $id);
		$this->_db->deleteRows('member', $filter);

		$_SESSION['msg'][] = array(
			'type' => 'success',
			'msg'  => 'Member successfully deleted.');

		header('Location: /ministry/'.$this->_ministry.'/members');
		exit;
	}

	/* Private Methods
	-------------------------------*/
}
