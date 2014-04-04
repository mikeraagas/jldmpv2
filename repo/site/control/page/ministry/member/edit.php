<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Control_Page_Ministry_Member_Edit extends Control_Page {
	/* Constants
	-------------------------------*/
	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title    = 'JLDMP - Control';
	protected $_class    = 'ministry';
	protected $_template = '/ministry/member/edit.phtml';

	protected $_ministry 	= null;
	protected $_id 		 	= null;
	protected $_post 	 	= array();
	protected $_msg 	 	= array();
	protected $_memberPath 	= null;

	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {
		$this->_ministry   = isset($this->_request['variables'][0]) ? $this->_request['variables'][0] : null;
		$this->_id 		   = isset($this->_request['variables'][1]) ? $this->_request['variables'][1] : null;
		$this->_memberPath = dirname(__FILE__).'/../../../../../../uploads/member/';

		if ($this->_id == null) { header('Location: /ministry/'.$this->_ministry.'/members'); exit; }
		if (isset($_GET['action']) && $_GET['action'] == 'remove_image') { $this->_removeImage($_GET['id']); }
		
		if (isset($_FILES['file'])) {
			if ($this->_validateUpload($_FILES['file'])) { $this->_uploadMemberImage(); }
		}

		$this->_post = $this->_getMember();
		$this->_post = $this->_getMemberImage($this->_post);

		// control()->output($this->_post);
		// exit;

		if (isset($_POST['edit_member'])) {
			if ($this->_validate($_POST)) {
				$this->_editMember($_POST);
			}

			$this->_post = $_POST;
		}

		$this->_renderMsg();
		
		$this->_body = array(
			'class'			=> 'member',
			'ministry_id' 	=> $this->_ministry,
			'msgs' 			=> $this->_msg,
			'errors' 		=> $this->_errors,
			'post' 			=> $this->_post);

		return $this->_page();
	}
	
	/* Protected Methods
	-------------------------------*/
	protected function _validate($post) {
		// check if field is empty
		$required = true;
		foreach ($post as $key => $field) {
			if ($field == '') { $required = false; }
		}

		if (!$required) { $this->_errors[] = 'All fields are required!'; return false; }

		if (!filter_var($post['member_email'], FILTER_VALIDATE_EMAIL)) { $this->_errors[] = 'Email is invalid!'; return false; }
		if (!is_numeric($post['member_phone'])) { $this->_errors[] = 'Phone must be numeric!'; return false; }
		if (!is_numeric($post['member_age'])) { $this->_errors[] = 'Age must be numeric!'; return false; }

		return true;
	}

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
			->addFilter('file_parent = '.$member['member_id'].' AND file_active = 1 AND file_type = "member"')
			->getRow();

		$member['member_image'] = $image;
		return $member;
	}

	protected function _editMember($post) {
		$settings = array(
			'member_fullname' => $post['member_fullname'],
			'member_email'    => $post['member_email'],
			'member_phone'    => $post['member_phone'],
			'member_address'  => $post['member_address'],
			'member_age'      => $post['member_age'],
			'member_type'     => $post['member_type'],
			'member_gender'   => $post['member_gender'],
			'member_active'   => $post['member_active'],
			'member_updated'  => time());

		$filter[] = array('member_id=%s', $this->_id);
		$this->_db->updateRows('member', $settings, $filter);

		$_SESSION['msg'][] = array(
			'type' => 'success',
			'msg'  => 'Member Successfully updated.');

		header('Location: /ministry/'.$this->_ministry.'/members');
		exit;
	}

	protected function _uploadMemberImage() {
		foreach ($this->_fileTmpPaths as $key => $fileTmpPath) {
			// move uploaded file
			if (move_uploaded_file($fileTmpPath, $this->_memberPath.$this->_fileNames[$key])) {
				// base64_encode image
				$extension = explode('.', $this->_fileNames[$key]);
				$extension = end($extension);

				$rand = str_shuffle(basename($this->_fileNames[$key])).rand(11111, 99999);
				$filename  = md5($rand);
				$filename  = $filename.'.'.$extension;
				
				rename($this->_memberPath.$this->_fileNames[$key], $this->_memberPath.$filename);

				// check if profile image already exists
				$exists = $this->_db->search()
					->setTable('file')
					->setColumns('*')
					->addFilter('file_parent = '.$this->_id.' AND file_type = "member" AND file_active = 1 AND file_primary = 1')
					->getRow();

				if (!empty($exists)) {
					$filter[] = array('file_id=%s', $exists['file_id']);
					$filter[] = array('file_parent=%s', $this->_id);
					$this->_db->deleteRows('file', $filter);

					unlink($this->_memberPath.$exists['file_name']);
				}

				$settings = array(
					'file_parent' 	 => $this->_id,
					'file_name' 	 => $filename,
					'file_extension' => $this->_fileTypes[$key],
					'file_type'		 => 'member',
					'file_active'	 => 1,
					'file_primary'	 => 1,
					'file_created'	 => time(),
					'file_updated'	 => time());

				$this->_db->insertRow('file', $settings);

				// edit event update time
				$settings 		= array('member_updated' => time());
				$memberFilter[] = array('member_id=%s', $this->_id);

				$this->_db->updateRows('member', $settings, $memberFilter);
				continue;
			}

			$_SESSION['msg'][] = array(
				'type' 	=> 'danger',
				'msg'	=> 'Image upload failed, possibly due to incorrect permissions on the upload folder.');

			$this->_redirect();
		}

		unset($fileTmpPath);

		$_SESSION['msg'][] = array(
			'type' 	=> 'success',
			'msg'	=> 'Successfully uploaded member profile image.');

		$this->_redirect();
	}

	protected function _removeImage($id) {
		$image = $this->_db->search()
			->setTable('file')
			->setColumns('*')
			->addFilter('file_id = '.$id.' AND file_parent = '.$this->_id.' AND file_type = "member"')
			->getRow();

		$filter[] = array('file_id=%s', $id);
		$filter[] = array('file_parent=%s', $this->_id);
		$filter[] = array('file_type=%s', 'member');

		$this->_db->deleteRows('file', $filter);

		unlink($this->_memberPath.$image['file_name']);

		$_SESSION['msg'][] = array(
			'type'  => 'success',
			'msg'	=> 'Successfully removed image.');

		$this->_redirect();
	}

	protected function _redirect() {
		header('Location: /ministry/'.$this->_ministry.'/member/edit/'.$this->_id);
		exit;	
	}

	/* Private Methods
	-------------------------------*/
}
