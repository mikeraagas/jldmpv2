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
		$filter = array(
			'_id'             => new MongoId($this->_id),
			'member_ministry' => $this->_ministry);

		$member = $this->_collection['member']->findOne($filter);
		return $member;
	}

	protected function _getMemberImage($member) {
		$filter = array(
			'file_parent' => $member['_id']->{'$id'},
			'file_active' => 1,
			'file_type'   => 'member');

		$image = $this->_collection['file']->findOne($filter);

		$member['member_image'] = $image;
		return $member;
	}

	protected function _getMinistries() {
		$filter = array(
			'_id'             => new MongoId($this->_id),
			'member_ministry' => $this->_ministry);

		$member = $this->_collection['member']->findOne($filter);
		$memberMinistries = $member['member_ministry'];

		$ministries = array();
		foreach ($memberMinistries as $ministryId) {
			$filter   = array('_id' => new MongoId($ministryId));
			$ministry = $this->_collection['ministry']->findOne($filter);

			$ministries[] = $ministry;
		}

		return $ministries;
	}

	/* Private Methods
	-------------------------------*/
}
