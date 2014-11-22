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
		$this->_memberPath = $this->_uploads.'/member';

		if (isset($_FILES['file']) && isset($_POST['avatar_src']) && isset($_POST['avatar_data'])) {
			$this->_cropImage();
		}

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
		if (isset($_SESSION['member_tmpimage']) && !empty($_SESSION['member_tmpimage'])) {
			$image = $_SESSION['member_tmpimage'];

			$fileFilter = array('_id' => $image['_id']);
			$file = $this->_collection['file']->findOne($fileFilter);

			if (!empty($file)) {
				$settings = array(
					'file_parent' => $memberId,
					'file_active' => 1);

				$this->_collection['file']->update($fileFilter, array('$set' => $settings));

				unset($_SESSION['member_tmpimage']);
			}
		}

		$_SESSION['msg'][] = array(
			'type' => 'success',
			'msg'  => 'Successfully added Member.');


		header('Location: /ministry/'.$this->_ministry.'/members');
		exit;
	}

	protected function _cropImage() {
		require($this->_lib.'/cropper/crop-avatar.php');

		$crop = new CropAvatar();
		$cropResult = $crop->cropImage($_POST['avatar_src'], $_POST['avatar_data'], $_FILES['file']);

		if ($cropResult['status']) {
			$filename = $this->_uploadMemberImage($cropResult['dst']);
			$uploadAbsolutePath = 'http://'.$_SERVER['SERVER_NAME'].'/uploads/member';
		}

		$response = array(
	        'state'   => 200,
	        'message' => $cropResult['msg'],
	        'result'  => isset($filename) ? $uploadAbsolutePath.'/'.$filename : $cropResult['result']
	    );

	    echo json_encode($response);
	    exit;
	}

	protected function _uploadMemberImage($fileTmpPath) {
		$file = basename($fileTmpPath);

		// move uploaded file
		if (rename($fileTmpPath, $this->_memberPath.'/'.$file)) {
			// base64_encode image
			$extension = explode('.', $file);
			$extension = end($extension);

			$rand 		= str_shuffle(basename($file)).rand(11111, 99999);
			$filename  	= md5($rand);
			$filename  	= $filename.'.'.$extension;

			rename($this->_memberPath.'/'.$file, $this->_memberPath.'/'.$filename);


			// check if image already exists
			if (isset($_SESSION['member_tmpimage'])) {
				$existsFilter = array(
					'file_name'   => $_SESSION['member_tmpimage']['file_name'],
					'file_active' => 0,
					'file_type'   => 'member');

				$exists = $this->_collection['file']->findOne($existsFilter);

				// if image exists update file and database
				if (!empty($exists)) {
					unlink($this->_memberPath.'/'.$_SESSION['member_tmpimage']['file_name']);

					// update datebase file name
					$settings = array('$set' => array('file_name' => $filename));
					$filter = array('_id' => $exists['_id']);

					$this->_collection['file']->update($filter, $settings);

					$_SESSION['member_tmpimage']['file_name'] = $filename;
					return $filename;
				}
			}

			$fileSettings = array(
				'file_name' 	 => $filename,
				'file_extension' => $extension,
				'file_type'		 => 'member',
				'file_active'	 => 0,
				'file_primary'	 => 1,
				'file_created'	 => new MongoDate(),
				'file_updated'	 => new MongoDate());

			$this->_collection['file']->insert($fileSettings);
			$fileId = $fileSettings['_id']->{'$id'};

			if (!isset($_SESSION['member_tmpimage'])) { $_SESSION['member_tmpimage'] = array(); }
			$_SESSION['member_tmpimage'] = $fileSettings;

			return $filename;
		}

		return false;
	}

	/* Private Methods
	-------------------------------*/
}
