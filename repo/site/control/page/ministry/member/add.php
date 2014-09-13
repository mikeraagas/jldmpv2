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
			'member_ministry' => array($this->_ministry),
			'member_fullname' => $post['fullname'],
			'member_email'    => $post['email'],
			'member_phone'    => $post['phone'],
			'member_address'  => $post['address'],
			'member_age'      => $post['age'],
			'member_type'     => $post['type'],
			'member_gender'   => $post['gender'],
			'member_active'   => (int) $post['active'],
			'member_created'  => new MongoDate(),
			'member_updated'  => new MongoDate());

		$this->_collection['member']->insert($memberSettings);
		$memberId = $memberSettings['_id']->{'$id'};

		// set member images
		if (isset($_SESSION['member_tmpimages']) && !empty($_SESSION['member_tmpimages'])) {
			$images = $_SESSION['member_tmpimages'];

			foreach ($images as $key => $image) {
				$fileFilter = array('_id' => $image['_id']);
				$file = $this->_collection['file']->findOne($fileFilter);

				if (empty($file)) continue;

				$settings = array(
					'file_parent' => $memberId,
					'file_active' => 1);

				$this->_collection['file']->update($fileFilter, array('$set' => $settings));
			}

			unset($_SESSION['member_tmpimages']);
		}

		$_SESSION['msg'][] = array(
			'type' => 'success',
			'msg'  => 'Successfully added Member.');


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

				$rand 		= str_shuffle(basename($this->_fileNames[$key])).rand(11111, 99999);
				$filename  	= md5($rand);
				$filename  	= $filename.'.'.$extension;

				rename($this->_memberPath.$this->_fileNames[$key], $this->_memberPath.$filename);

				// check if image already exists
				$existsFilter = array(
					'file_name'   => $_SESSION['member_tmpimages'][0]['file_name'],
					'file_active' => 0,
					'file_type'   => 'member');

				$exists = $this->_collection['file']->findOne($existsFilter);

				// if image exists update file and database
				if (!empty($exists)) {
					unlink($this->_memberPath.$_SESSION['member_tmpimages'][0]['file_name']);

					// update datebase file name
					$settings = array('$set' => array('file_name' => $filename));
					$filter = array('_id' => $exists['_id']->{'$id'});

					$this->_collection['file']->update($filter, $settings);

					$_SESSION['member_tmpimages'][0]['file_name'] = $filename;
					unset($fileTmpPath);

					$_SESSION['msg'][] = array(
						'type' 	=> 'success',
						'msg'	=> 'Successfully updated member image.');

					header('Location: /ministry/'.$this->_ministry.'/member/add');
					exit;
				}

				$fileSettings = array(
					'file_name' 	 => $filename,
					'file_extension' => $this->_fileTypes[$key],
					'file_type'		 => 'member',
					'file_active'	 => 0,
					'file_primary'	 => 1,
					'file_created'	 => new MongoDate(),
					'file_updated'	 => new MongoDate());

				$this->_collection['file']->insert($fileSettings);
				$fileId = $fileSettings['_id']->{'$id'};

				if (!isset($_SESSION['member_tmpimages'])) { $_SESSION['member_tmpimages'] = array(); }

				$_SESSION['member_tmpimages'][] = $fileSettings;
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
			if ($image['_id']->{'$id'} == $id) {

				unlink($this->_memberPath.$_SESSION['member_tmpimages'][$key]['file_name']);
				unset($_SESSION['member_tmpimages'][$key]);

				$filter = array('_id' => new MongoId($id));
				$this->_collection['file']->remove($filter, array('justOne' => true));

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
