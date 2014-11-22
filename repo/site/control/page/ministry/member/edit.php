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
		$this->_memberPath = $this->_uploads.'/member';

		if ($this->_id == null) { header('Location: /ministry/'.$this->_ministry.'/members'); exit; }
		if (isset($_GET['action']) && $_GET['action'] == 'remove_image') { $this->_removeImage($_GET['id']); }
		
		if (isset($_FILES['file']) && isset($_POST['avatar_src']) && isset($_POST['avatar_data'])) {
			$this->_cropImage();
		}

		$this->_post = $this->_getMember();
		$this->_post = $this->_getMemberImage($this->_post);

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

	protected function _editMember($post) {
		$settings = array(
			'member_fullname' => $post['member_fullname'],
			'member_email'    => $post['member_email'],
			'member_phone'    => $post['member_phone'],
			'member_address'  => $post['member_address'],
			'member_age'      => $post['member_age'],
			'member_type'     => $post['member_type'],
			'member_gender'   => $post['member_gender'],
			'member_active'   => (int) $post['member_active'],
			'member_updated'  => new MongoDate());

		$filter = array('_id' => new MongoId($this->_id));
		$this->_collection['member']->update($filter, array('$set' => $settings));

		$_SESSION['msg'][] = array(
			'type' => 'success',
			'msg'  => 'Member Successfully updated.');

		header('Location: /ministry/'.$this->_ministry.'/members');
		exit;
	}

	protected function _cropImage() {
		require($this->_lib.'/cropper/crop-avatar.php');

		$crop = new CropAvatar();
		$cropResult = $crop->cropImage($_POST['avatar_src'], $_POST['avatar_data'], $_FILES['file']);

		$response = array('state' => 200);

		if ($cropResult['status']) {
			$uploadResult = $this->_uploadMemberImage($cropResult['dst']);
			$uploadAbsolutePath = 'http://'.$_SERVER['SERVER_NAME'].'/uploads/member';

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

			// check if file exists
			$existsFilter = array(
				'file_parent'  => $this->_id,
				'file_type'    => 'member',
				'file_active'  => 1,
				'file_primary' => 1);

			$exists = $this->_collection['file']->findOne($existsFilter);

			if (!empty($exists)) {
				unlink($this->_memberPath.'/'.$exists['file_name']);

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
