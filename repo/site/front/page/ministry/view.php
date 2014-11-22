<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Front_Page_Ministry_View extends Front_Page {
	/* Constants
	-------------------------------*/
	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title    = 'JLDMP - Ministries';
	protected $_class    = 'ministries';
	protected $_template = '/ministry/view.phtml';
	protected $_id       = null;
	
	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {
		$this->_id   = isset($this->_request['variables'][0]) ? $this->_request['variables'][0] : null;
		$this->_type = isset($this->_request['variables'][1]) ? $this->_request['variables'][1] : null;

		if (isset($_GET['action']) && $_GET['action'] == 'getMember') {
			if (isset($_GET['id'])) {
				$this->_getMember($_GET['id']);
			}
		}

		$ministry = $this->_getMinistry();
		$members  = $this->_getMinistryMembers();

		$this->_body = array(
			'class'    => 'view',
			'ministry' => $ministry,
			'members'  => $members);

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

	protected function _getMinistryMembers() {
		$filter = array(
			'member_ministry' => $this->_id,
			'member_active'   => 1);

		$query = $this->_collection['member']->find($filter)
			->sort(array('ministry_updated' => -1));

		$members = iterator_to_array($query);
		$members = $this->_getMemberImages($members);

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

	protected function _getMember($id) {
		$filter = array(
			'_id' => new MongoId($id),
			'member_ministry' => $this->_id);

		$member = $this->_collection['member']->findOne($filter);

		// get member image
		$imageFilter = array(
			'file_parent' => $id,
			'file_active' => 1,
			'file_type'   => 'member');

		$image = $this->_collection['file']->findOne($imageFilter);
		$member['member_image'] = $image;

		echo json_encode($member);
		exit;
	}

	/* Private Methods
	-------------------------------*/
}
