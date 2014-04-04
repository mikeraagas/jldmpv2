<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Control_Page_Ministry_Member_Add extends Control_Page {
	/* Constants
	-------------------------------*/
	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title    = 'JLDMP - Control';
	protected $_class    = 'ministry';
	protected $_template = '/ministry/member/add.phtml';

	protected $_ministry 	= null;
	protected $_memberPath 	= null;
	protected $_post 	 	= array();
	protected $_msg 	 	= array();

	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {
		$this->_ministry   = isset($this->_request['variables'][0]) ? $this->_request['variables'][0] : null;
		$this->_memberPath = dirname(__FILE__).'/../../../../../../uploads/member/';

		if (isset($_FILES['file'])) {
			if ($this->_validateUpload($_FILES['file'])) {
				$this->_uploadMemberImage();
			}
		}

		if (isset($_GET['action']) && $_GET['action'] == 'remove_upload') { $this->_removeUploadedImage($_GET['id']); }

		if (isset($_POST['add_member'])) {
			if ($this->_validate($_POST)) {
				$this->_addMember($_POST);
			}

			$this->_post = $_POST;
		}

		$this->_renderMsg();

		$this->_body = array(
			'class'			=> 'member',
			'errors'   		=> $this->_errors,
			'msgs' 			=> $this->_msg,
			'ministry_id' 	=> $this->_ministry,
			'post' 	   		=> $this->_post);
		
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

		if (!filter_var($post['email'], FILTER_VALIDATE_EMAIL)) { $this->_errors[] = 'Email is invalid!'; return false; }
		if (!is_numeric($post['phone'])) { $this->_errors[] = 'Phone must be numeric!'; return false; }
		if (!is_numeric($post['age'])) { $this->_errors[] = 'Age must be numeric!'; return false; }

		return true;
	}

	protected function _addMember($post) {
		$memberSettings = array(
			'member_fullname' => $post['fullname'],
			'member_email'    => $post['email'],
			'member_phone'    => $post['phone'],
			'member_address'  => $post['address'],
			'member_age'      => $post['age'],
			'member_type'     => $post['type'],
			'member_gender'   => $post['gender'],
			'member_active'   => $post['active'],
			'member_created'  => time(),
			'member_updated'  => time());

		$this->_db->insertRow('member', $memberSettings);
		$memberId = $this->_db->getLastInsertedId();

		// add to ministry
		$settings = array(
			'group_ministry' => $this->_ministry,
			'group_member'   => $memberId);

		$this->_db->insertRow('`group`', $settings);

		// set member images
		if (isset($_SESSION['member_tmpimages']) && !empty($_SESSION['member_tmpimages'])) {
			$images = $_SESSION['member_tmpimages'];

			foreach ($images as $key => $image) {
				$file = $this->_db->search()
					->setTable('file')
					->setColumns('*')
					->filterByFileId($image['file_id'])
					->getRow();

				if (empty($file)) continue;

				$settings = array(
					'file_parent' => $memberId,
					'file_active' => 1);

				$this->_updateDbImage($file['file_id'], $settings);
			}

			unset($_SESSION['member_tmpimages']);
		}

		$_SESSION['msg'][] = array(
			'type' => 'success',
			'msg'  => 'Successfully added Member.');


		header('Location: /ministry/'.$this->_ministry.'/members');
		exit;
	}

	protected function _updateDbImage($id, $settings) {
		$filter[] = array('file_id=%s', $id);

		$this->_db->updateRows('file', $settings, $filter);
		return;
	}

	protected function _uploadMemberImage() {
		foreach ($this->_fileTmpPaths as $key => $fileTmpPath) {
			// move uploaded file
			if (move_uploaded_file($fileTmpPath, $this->_memberPath.$this->_fileNames[$key])) {
				// base64_encode image
				$extension = explode('.', $this->_fileNames[$key]);
				$extension = end($extension);

				$rand 		= str_shuffle(basename($this->_fileNames[$key])).rand(11111, 99999);
				$filename  	= md5($rand);
				$filename  	= $filename.'.'.$extension;

				rename($this->_memberPath.$this->_fileNames[$key], $this->_memberPath.$filename);

				$settings = array(
					'file_name' 	 => $filename,
					'file_extension' => $this->_fileTypes[$key],
					'file_type'		 => 'member',
					'file_active'	 => 0,
					'file_primary'	 => 1,
					'file_created'	 => time(),
					'file_updated'	 => time());

				$this->_db->insertRow('file', $settings);
				$fileId = $this->_db->getLastInsertedId();

				$file = $this->_db->search()
					->setTable('file')
					->setColumns('*')
					->filterByFileId($fileId)
					->getRow();

				if (!isset($_SESSION['member_tmpimages'])) { $_SESSION['member_tmpimages'] = array(); }

				$_SESSION['member_tmpimages'][] = $file;
				continue;
			}

			$_SESSION['msg'][] = array(
				'type' 	=> 'danger',
				'msg'	=> 'Image upload failed, possibly due to incorrect permissions on the upload folder.');

			unset($_SESSION['member_tmpimages']);

			header('Location: /ministry/'.$this->_ministry.'/member/add');
			exit;
		}

		unset($fileTmpPath);

		$_SESSION['msg'][] = array(
			'type' 	=> 'success',
			'msg'	=> 'Successfully uploaded member image.');

		header('Location: /ministry/'.$this->_ministry.'/member/add');
		exit;
	}

	protected function _removeUploadedImage($id) {
		$uploaded = $_SESSION['member_tmpimages'];

		foreach ($uploaded as $key => $image) {
			if ($image['file_id'] == $id) {
				unlink($this->_memberPath.$_SESSION['member_tmpimages'][$key]['file_name']);
				unset($_SESSION['member_tmpimages'][$key]);

				$filter[] = array('file_id=%s', $id);
				$this->_db->deleteRows('file', $filter);

				$_SESSION['msg'][] = array(
					'type' 	=> 'success',
					'msg'	=> 'Successfully removed uploaded image.');

				header('Location: /ministry/'.$this->_ministry.'/member/add');
				exit;
			}
		}

		if (empty($_SESSION['member_tmpimages'])) { unset($_SESSION['member_tmpimages']); }

		$_SESSION['msg'][] = array(
			'type' 	=> 'danger',
			'msg'	=> 'Image does not exists in uploaded files!');

		header('Location: /ministry/'.$this->_ministry.'/member/add');
		exit;
	}

	/* Private Methods
	-------------------------------*/
}
