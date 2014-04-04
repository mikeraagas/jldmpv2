<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Control_Page_Ministry_Member_View extends Control_Page {
	/* Constants
	-------------------------------*/
	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title    = 'JLDMP - Control';
	protected $_class    = 'ministry';
	protected $_template = '/ministry/member/view.phtml';

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

		if ($this->_id == null) {
			header('Location: /ministry/'.$this->_ministry.'/members');
			exit;
		}

		$member 	= $this->_getMember();
		$member 	= $this->_getMemberImage($member);
		$ministries = $this->_getMinistries();

		$this->_body = array(
			'class' 		=> 'member',
			'msgs' 			=> $this->_msg,
			'member' 		=> $member,
			'ministry_id' 	=> $this->_ministry,
			'ministries'	=> $ministries);

		return $this->_page();
	}
	
	/* Protected Methods
	-------------------------------*/
	protected function _getMember() {
		$member = $this->_db->search()
			->setTable('member m')
			->setColumns('m.*')
			->addInnerJoinOn('`group` g', 'g.group_member = m.member_id')
			->addFilter('g.group_ministry = '.$this->_ministry.' AND m.member_id = '.$this->_id)
			->getRow();

		return $member;
	}

	protected function _getMemberImage($member) {
		$image = $this->_db->search()
			->setTable('file')
			->setColumns('*')
			->addFilter('file_parent = '.$this->_id.' AND file_active = 1 AND file_type = "member"')
			->getRow();

		$member['member_image'] = $image;
		return $member;
	}

	protected function _getMinistries() {
		// get affiliated ministries
		$groups = $this->_db->search()
			->setTable('`group`')
			->setColumns('*')
			->filterByGroupMember($this->_id)
			->getRows();

		$ministries = array();	
		foreach ($groups as $group) {
			$ministry = $this->_db->search()
				->setTable('ministry')
				->setColumns('*')
				->filterByMinistryId($group['group_ministry'])
				->getRow();

			$ministries[] = $ministry;
		}

		return $ministries;
	}

	/* Private Methods
	-------------------------------*/
}
