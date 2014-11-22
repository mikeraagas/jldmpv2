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
	protected $_ministryPath = null;
	
	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {		
		$this->_ministryPath = $this->_uploads.'/ministry';

		if (isset($_POST['add_ministry'])) {
			if ($this->_setErrors($_POST)) {
				$this->_addMinistry($_POST);
			}

			$post = $_POST;
		}

		if (isset($_GET['action']) && $_GET['action'] == 'remove_upload') { $this->_removeUploadedImage($_GET['id']); }

		if (isset($_FILES['file']) && isset($_POST['avatar_src']) && isset($_POST['avatar_data'])) {
			$this->_cropImage();
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
		$filter = array('ministry_title' => $title);
		$exists = $this->_collection['admin']->findOne($filter);

		if (!empty($exists)) { return true; }
		return false;
	}

	protected function _addMinistry($post) {
		// add admin
		$adminSettings = array(
			'admin_name' 	 => $post['name'],
			'admin_email'    => $post['email'],
			'admin_username' => $post['username'],
			'admin_password' => md5($post['password']),
			'admin_active'   => 1,
			'admin_type'     => 2,
			'admin_created'  => new MongoDate(),
			'admin_updated'  => new MongoDate());

		$this->_collection['admin']->insert($adminSettings);
		$ministryId = $adminSettings['_id']->{'$id'};

		// add ministry
		$ministrySettings = array(
			'ministry_admin' 		=> $ministryId,
			'ministry_title' 		=> $post['title'],
			'ministry_description' 	=> $post['description'],
			'ministry_active' 		=> (int) $post['active'],
			'ministry_created' 		=> new MongoDate(),
			'ministry_updated' 		=> new MongoDate());

		$this->_collection['ministry']->insert($ministrySettings);

		// set member images
		if (isset($_SESSION['ministry_tmpimage']) && !empty($_SESSION['ministry_tmpimage'])) {
			$image = $_SESSION['ministry_tmpimage'];

			$filter = array('_id' => $image['_id']);
			$file   = $this->_collection['file']->findOne($filter);

			if (!empty($file)) {
				$settings = array(
					'file_parent' => $ministrySettings['_id']->{'$id'},
					'file_active' => 1);

				$this->_updateDbImage($file['_id']->{'$id'}, $settings);

				unset($_SESSION['ministry_tmpimage']);
			}
		}

		$_SESSION['msg'][] = array(
			'type' => 'success',
			'msg'  => 'Ministry successfully added.');

		header('Location: /ministries');
		exit;
	}

	protected function _updateDbImage($id, $settings) {
		$filter       = array('_id' => new MongoId($id));
		$fileSettings = array('$set' => $settings);

		$this->_collection['file']->update($filter, $fileSettings);
		return;
	}

	protected function _cropImage() {
		require($this->_lib.'/cropper/crop-avatar.php');

		$crop = new CropAvatar();
		$cropResult = $crop->cropImage($_POST['avatar_src'], $_POST['avatar_data'], $_FILES['file']);

		if ($cropResult['status']) {
			$filename = $this->_uploadNewsImage($cropResult['dst']);
			$uploadAbsolutePath = 'http://'.$_SERVER['SERVER_NAME'].'/uploads/ministry';
		}

		$response = array(
	        'state'   => 200,
	        'message' => $cropResult['msg'],
	        'result'  => isset($filename) ? $uploadAbsolutePath.'/'.$filename : $cropResult['result']
	    );

	    echo json_encode($response);
	    exit;
	}

	protected function _uploadNewsImage($fileTmpPath) {
		$file = basename($fileTmpPath);

		// move uploaded file
		if (rename($fileTmpPath, $this->_ministryPath.'/'.$file)) {
			// base64_encode image
			$extension = explode('.', $file);
			$extension = end($extension);

			$rand 		= str_shuffle(basename($file)).rand(11111, 99999);
			$filename  	= md5($rand);
			$filename  	= $filename.'.'.$extension;

			rename($this->_ministryPath.'/'.$file, $this->_ministryPath.'/'.$filename);


			// check if image already exists
			if (isset($_SESSION['ministry_tmpimage'])) {
				$existsFilter = array(
					'file_name'   => $_SESSION['ministry_tmpimage']['file_name'],
					'file_active' => 0,
					'file_type'   => 'ministry');

				$exists = $this->_collection['file']->findOne($existsFilter);

				// if image exists update file and database
				if (!empty($exists)) {
					unlink($this->_ministryPath.'/'.$_SESSION['ministry_tmpimage']['file_name']);

					// update datebase file name
					$settings = array('$set' => array('file_name' => $filename));
					$filter = array('_id' => $exists['_id']);

					$this->_collection['file']->update($filter, $settings);

					$_SESSION['ministry_tmpimage']['file_name'] = $filename;
					return $filename;
				}
			}

			$fileSettings = array(
				'file_name' 	 => $filename,
				'file_extension' => $extension,
				'file_type'		 => 'ministry',
				'file_active'	 => 0,
				'file_primary'	 => 1,
				'file_created'	 => new MongoDate(),
				'file_updated'	 => new MongoDate());

			$this->_collection['file']->insert($fileSettings);
			$fileId = $fileSettings['_id']->{'$id'};

			if (!isset($_SESSION['ministry_tmpimage'])) { $_SESSION['ministry_tmpimage'] = array(); }
			$_SESSION['ministry_tmpimage'] = $fileSettings;

			return $filename;
		}

		return false;
	}

	/* Private Methods
	-------------------------------*/
}