<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Control_Page_Ministry_Add extends Control_Page {
	/* Constants
	-------------------------------*/
	const ALL_FIELDS_REQUIRED = 'All fields are required!';
	const PASSWORD_NOT_MATCH  = 'Password did not match!';
	const USERNAME_ERROR  	  = 'Username must be 6 or more characters!';
	const PASSWORD_ERROR      = 'Password must be 8 or more characters!';
	const MINISTRY_EXISTS 	  = "Ministry already exists!";

	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title    = 'JLDMP - Control';
	protected $_class    = 'ministry';
	protected $_template = '/ministry/add.phtml';
	
	protected $_msg      	= array();
	protected $_errors    	= array();
	protected $_minstryPath = null;
	
	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {
		$this->_minstryPath = dirname(__FILE__).'/../../../../../uploads/ministry/';

		if (isset($_POST['add_ministry'])) {
			if ($this->_setErrors($_POST)) {
				$this->_addMinistry($_POST);
			}

			$post = $_POST;
		}

		if (isset($_GET['action']) && $_GET['action'] == 'remove_upload') { $this->_removeUploadedImage($_GET['id']); }

		if (isset($_FILES['file'])) {
			if ($this->_validateUpload($_FILES['file'])) {
				$this->_uploadMinistryImage();
			}
		}

		$this->_renderMsg();
		
		$this->_body = array(
			'post'   => isset($post) ? $post : array(),
			'msgs'   => $this->_msg,
			'errors' => $this->_errors);

		return $this->_page();
	}
	
	/* Protected Methods
	-------------------------------*/
	protected function _setErrors($post) {
		foreach ($post as $field => $value) {
			if ($value == '') {
				$this->_errors[] = self::ALL_FIELDS_REQUIRED;
				return false;
			}
		}
		
		if (strlen($post['username']) < 5) { $this->_errors[] = self::USERNAME_ERROR; return false; }
		if (strlen($post['password']) < 8 && strlen($post['confirm']) < 8) { $this->_errors[] = self::PASSWORD_ERROR; return false; }
		if ($post['password'] != $post['confirm']) { $this->_errors[] = self::PASSWORD_NOT_MATCH; return false; }
		if ($this->_checkExists($post['title'])) { $this->_errors[] = self::MINISTRY_EXISTS; return false; }

		return true;
	}

	protected function _checkExists($title) {
		// check if exists
		$exists = $this->_db->search()
			->setTable('ministry')
			->setColumns('*')
			->filterByMinistryTitle($title)
			->getRow();

		if (!empty($exists)) {
			return true;
		}

		return false;
	}

	protected function _addMinistry($post) {
		// add admin
		$settings = array(
			'admin_name' 	 => $post['name'],
			'admin_email'    => $post['email'],
			'admin_username' => $post['username'],
			'admin_password' => md5($post['password']),
			'admin_active'   => 1,
			'admin_type'     => 2,
			'admin_created'  => time(),
			'admin_updated'  => time());

		$this->_db->insertRow('admin', $settings);
		$ministryId = $this->_db->getLastInsertedId();

		// add ministry
		$settings = array(
			'ministry_admin' 		=> $ministryId,
			'ministry_title' 		=> $post['title'],
			'ministry_description' 	=> $post['description'],
			'ministry_active' 		=> $post['active'],
			'ministry_created' 		=> time(),
			'ministry_updated' 		=> time());

		$this->_db->insertRow('ministry', $settings);

		// set member images
		if (isset($_SESSION['ministry_tmpimages']) && !empty($_SESSION['ministry_tmpimages'])) {
			$images = $_SESSION['ministry_tmpimages'];

			foreach ($images as $key => $image) {
				$file = $this->_db->search()
					->setTable('file')
					->setColumns('*')
					->filterByFileId($image['file_id'])
					->getRow();

				if (empty($file)) continue;

				$settings = array(
					'file_parent' => $ministryId,
					'file_active' => 1);

				$this->_updateDbImage($file['file_id'], $settings);
			}

			unset($_SESSION['ministry_tmpimages']);
		}

		$_SESSION['msg'][] = array(
			'type' => 'success',
			'msg'  => 'Ministry successfully added.');

		header('Location: /ministry');
		exit;
	}

	protected function _updateDbImage($id, $settings) {
		$filter[] = array('file_id=%s', $id);

		$this->_db->updateRows('file', $settings, $filter);
		return;
	}

	protected function _uploadMinistryImage() {
		foreach ($this->_fileTmpPaths as $key => $fileTmpPath) {
			// move uploaded file
			if (move_uploaded_file($fileTmpPath, $this->_minstryPath.$this->_fileNames[$key])) {
				// base64_encode image
				$extension = explode('.', $this->_fileNames[$key]);
				$extension = end($extension);

				$rand 		= str_shuffle(basename($this->_fileNames[$key])).rand(11111, 99999);
				$filename  	= md5($rand);
				$filename  	= $filename.'.'.$extension;

				rename($this->_minstryPath.$this->_fileNames[$key], $this->_minstryPath.$filename);

				// check if image already exists
				$exists = $this->_db->search()
					->setTable('file')
					->setColumns('*')
					->addFilter('file_name = "'.$_SESSION['ministry_tmpimages'][0]['file_name'].'" AND file_active = 0 AND file_type = "ministry"')
					->getRow();

				// if image exists update file and database
				if (!empty($exists)) {
					unlink($this->_minstryPath.$_SESSION['ministry_tmpimages'][0]['file_name']);

					// update datebase file name
					$settings = array('file_name' => $filename);
					$filter[] = array('file_id=%s', $exists['file_id']);

					$this->_db->updateRows('file', $settings, $filter);

					$_SESSION['ministry_tmpimages'][0]['file_name'] = $filename;
					unset($fileTmpPath);

					$_SESSION['msg'][] = array(
						'type' 	=> 'success',
						'msg'	=> 'Successfully updated ministry image.');

					header('Location: /ministry/add');
					exit;
				}

				$settings = array(
					'file_name' 	 => $filename,
					'file_extension' => $this->_fileTypes[$key],
					'file_type'		 => 'ministry',
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

				if (!isset($_SESSION['ministry_tmpimages'])) { $_SESSION['ministry_tmpimages'] = array(); }

				$_SESSION['ministry_tmpimages'][] = $file;
				continue;
			}

			$_SESSION['msg'][] = array(
				'type' 	=> 'danger',
				'msg'	=> 'Image upload failed, possibly due to incorrect permissions on the upload folder.');

			unset($_SESSION['ministry_tmpimages']);

			header('Location: /ministry/add');
			exit;
		}

		unset($fileTmpPath);

		$_SESSION['msg'][] = array(
			'type' 	=> 'success',
			'msg'	=> 'Successfully uploaded ministry image.');

		header('Location: /ministry/add');
		exit;
	}

	protected function _removeUploadedImage($id) {
		$uploaded = $_SESSION['ministry_tmpimages'];

		foreach ($uploaded as $key => $image) {
			if ($image['file_id'] == $id) {
				unlink($this->_minstryPath.$_SESSION['ministry_tmpimages'][$key]['file_name']);
				unset($_SESSION['ministry_tmpimages'][$key]);

				$filter[] = array('file_id=%s', $id);
				$this->_db->deleteRows('file', $filter);

				$_SESSION['msg'][] = array(
					'type' 	=> 'success',
					'msg'	=> 'Successfully removed uploaded image.');

				header('Location: /ministry/add');
				exit;
			}
		}

		if (empty($_SESSION['ministry_tmpimages'])) { unset($_SESSION['ministry_tmpimages']); }

		$_SESSION['msg'][] = array(
			'type' 	=> 'danger',
			'msg'	=> 'Image does not exists in uploaded files!');

		header('Location: /ministry/add');
		exit;
	}

	/* Private Methods
	-------------------------------*/
}