<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Control_Page_Ministry_Edit extends Control_Page {
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
	protected $_template = '/ministry/edit.phtml';

	protected $_id 		 	 = null;
	protected $_msg      	 = array();
	protected $_errors   	 = array();
	protected $_ministryPath = null;

	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {
		$this->_id = isset($this->_request['variables'][0]) ? $this->_request['variables'][0] : null;
		$this->_ministryPath = $this->_uploads.'/ministry';

		if (isset($_GET['action']) && $_GET['action'] == 'remove_image') { $this->_removeImage($_GET['id']); }
		
		if (isset($_FILES['file']) && isset($_POST['avatar_src']) && isset($_POST['avatar_data'])) {
			$this->_cropImage();
		}
		
		$post = $this->_getMinistry();

		if (isset($_POST['edit_ministry'])) {
			if ($this->_setErrors($_POST)) {
				$this->_editMinistry($_POST);
			}

			$post = $_POST;
		}

		$this->_renderMsg();
		
		$this->_body = array(
			'post'   => $post,
			'msgs'   => $this->_msg,
			'errors' => $this->_errors);

		return $this->_page();
	}
	
	/* Protected Methods
	-------------------------------*/
	protected function _setErrors($post) {
		foreach ($post as $field => $value) {
			if ($value == '' && $field != 'admin_password' && $field != 'admin_confirm') {
				$this->_errors[] = self::ALL_FIELDS_REQUIRED;
				return false;
			}
		}
		
		if (strlen($post['admin_username']) < 5) { $this->_errors[] = self::USERNAME_ERROR; return false; }
		return true;
	}

	protected function _getMinistry() {
		$ministryFilter = array('_id' => new MongoId($this->_id));
		$ministry = $this->_collection['ministry']->findOne($ministryFilter);

		if (empty($ministry)) {
			header('Location: /ministries');
			exit;
		}

		// get ministry admin
		$adminFilter = array('_id' => new MongoId($ministry['ministry_admin']));
		$admin = $this->_collection['admin']->findOne($adminFilter);

		$ministry['admin'] = $admin;

		// get ministry image
		$filter = array(
			'file_parent' => $ministry['_id']->{'$id'},
			'file_active' => 1,
			'file_type'   => 'ministry');

		$image = $this->_collection['file']->findOne($filter);

		if (!empty($image)) {
			$ministry['ministry_image'] = $image;
		}

		return $ministry;
	}

	protected function _editMinistry($post) {
		// update ministry admin
		$settings = array(
			'$set' => array(
				'admin_name'     => $post['admin_name'],
				'admin_email'    => $post['admin_email'],
				'admin_username' => $post['admin_username'],
				'admin_updated'  => $post['admin_name']
			)
		);

		$filter = array('_id' => new MongoId($post['admin_id']));
		$this->_collection['admin']->update($filter, $settings);

		// update ministry
		$ministrySettings = array(
			'$set' => array(
				'ministry_title' 		=> $post['ministry_title'],
				'ministry_description' 	=> $post['ministry_description'],
				'ministry_active' 		=> (int) $post['ministry_active'],
				'ministry_updated' 		=> new MongoDate()
			)
		);

		$ministryFilter = array('_id' => new MongoId($post['ministry_id']));
		$this->_collection['ministry']->update($ministryFilter, $ministrySettings);

		$_SESSION['msg'][] = array(
			'type' => 'success',
			'msg'  => 'Ministry successfully updated.');

		header('Location: /ministry/edit/'.$this->_id);
		exit;
	}

	protected function _cropImage() {
		require($this->_lib.'/cropper/crop-avatar.php');

		$crop = new CropAvatar();
		$cropResult = $crop->cropImage($_POST['avatar_src'], $_POST['avatar_data'], $_FILES['file']);

		$response = array('state' => 200);

		if ($cropResult['status']) {
			$uploadResult = $this->_uploadMinistryImage($cropResult['dst']);
			$uploadAbsolutePath = 'http://'.$_SERVER['SERVER_NAME'].'/uploads/ministry';

			$response['message'] = $uploadResult['msg'];
			$response['result']  = $uploadAbsolutePath.'/'.$uploadResult['filename'];

			echo json_encode($response);
		    exit;
		}

	    $response['message'] = $cropResult['msg'];
	    $response['result']  = $cropResult['result'];

	    echo json_encode($response);
	    exit;
	}

	protected function _uploadMinistryImage($fileTmpPath) {
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

			// check if file exists
			$existsFilter = array(
				'file_parent'  => $this->_id,
				'file_type'    => 'ministry',
				'file_active'  => 1,
				'file_primary' => 1);

			$exists = $this->_collection['file']->findOne($existsFilter);

			if (!empty($exists)) {
				unlink($this->_ministryPath.'/'.$exists['file_name']);

				// update datebase file name
				$settings = array('$set' => array(
					'file_name'    => $filename,
					'file_updated' => new MongoDate()));

				$filter = array('_id' => $exists['_id']);
				$this->_collection['file']->update($filter, $settings);

				$response = array(
					'status'   => 'success',
					'msg'      => 'Successfully uploaded image',
					'filename' => $filename);

				return $response;
			}
		}

		$response = array(
				'status'   => 'error',
				'msg'      => 'File not found!',
				'filename' => $filename);

		return $response;
	}

	/* Private Methods
	-------------------------------*/
}
