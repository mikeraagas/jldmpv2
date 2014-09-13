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
		$this->_ministryPath = dirname(__FILE__).'/../../../../../uploads/ministry/';

		if (isset($_GET['action']) && $_GET['action'] == 'remove_image') { $this->_removeImage($_GET['id']); }
		if (isset($_FILES['file'])) {
			if ($this->_validateUpload($_FILES['file'])) { $this->_uploadMinistryImage(); }
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
			header('Location: /ministry');
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
				'ministry_active' 		=> $post['ministry_active'],
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

	protected function _uploadMinistryImage() {
		foreach ($this->_fileTmpPaths as $key => $fileTmpPath) {
			// move uploaded file
			if (move_uploaded_file($fileTmpPath, $this->_ministryPath.$this->_fileNames[$key])) {
				// base64_encode image
				$extension = explode('.', $this->_fileNames[$key]);
				$extension = end($extension);

				$rand = str_shuffle(basename($this->_fileNames[$key])).rand(11111, 99999);
				$filename  = md5($rand);
				$filename  = $filename.'.'.$extension;
				
				rename($this->_ministryPath.$this->_fileNames[$key], $this->_ministryPath.$filename);

				// check if image already exists
				$filter = array(
					'file_parent' => $this->_id,
					'file_active' => 1,
					'file_type'   => 'ministry');

				$exists = $this->_collection['file']->findOne($filter);

				// if image exists update file and database
				if (!empty($exists)) {
					unlink($this->_ministryPath.$exists['file_name']);

					// update datebase file name
					$settings = array('$set' => array('file_name' => $filename));
					$filter   = array('_id' => new MongoId($exists['_id']->{'$id'}));

					$this->_collection['file']->update($filter, $settings);

					$_SESSION['msg'][] = array(
						'type' 	=> 'success',
						'msg'	=> 'Successfully updated ministry image.');

					header('Location: /ministry/edit/'.$this->_id);
					exit;
				}

				// add ministry image
				$fileSettings = array(
					'file_parent' 	 => $this->_id,
					'file_name' 	 => $filename,
					'file_extension' => $this->_fileTypes[$key],
					'file_type'		 => 'ministry',
					'file_active'	 => 1,
					'file_primary'	 => 1,
					'file_created'	 => new MongoDate(),
					'file_updated'	 => new MongoDate());

				$this->_collection['file']->insert($fileSettings);
				
				// edit ministry updated to time
				$ministrySettings = array('ministry_updated' => new MongoDate());
				$ministryFilter   = array('_id', new MongoId($this->_id));

				$this->_collection['ministry']->update($ministryFilter, $ministrySettings);
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
			'msg'	=> 'Successfully uploaded ministry profile image.');

		$this->_redirect();
	}

	protected function _removeImage($id) {
		$filter = array(
			'_id' 	  => new MongoId($id),
			'file_parent' => $this->_id,
			'file_active' => 1,
			'file_type'   => 'ministry');

		$image = $this->_collection['file']->findOne($filter);

		$filter = array('_id' => new MongoId($id));
		$this->_collection['file']->remove($filter, array('justOne' => true));

		unlink($this->_ministryPath.$image['file_name']);

		$_SESSION['msg'][] = array(
			'type'  => 'success',
			'msg'	=> 'Successfully removed image.');

		$this->_redirect();
	}

	protected function _redirect() {
		header('Location: /ministry/edit/'.$this->_id);
		exit;
	}

	/* Private Methods
	-------------------------------*/
}
