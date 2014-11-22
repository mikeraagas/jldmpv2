<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Control_Page_Ministry_Event_Add extends Control_Page {
	/* Constants
	-------------------------------*/
	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title    = 'JLDMP - Control';
	protected $_class    = 'ministry';
	protected $_template = '/ministry/event/add.phtml';

	protected $_ministry 	 = null;
	protected $_eventPath	 = null;
	protected $_errors   	 = array();
	protected $_post 	 	 = array();
	protected $_msg 	 	 = array();

	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {
		$this->_ministry  = isset($this->_request['variables'][0]) ? $this->_request['variables'][0] : null;
		$this->_eventPath = $this->_uploads.'/event';

		if (isset($_POST['add_event'])) {
			if ($this->_validate($_POST)) {
				$this->_addEvent($_POST);
			}

			$this->_post = $_POST;	
		}

		if (isset($_GET['action']) && $_GET['action'] == 'remove_upload') { $this->_removeUploadedImage($_GET['id']); }
		if (isset($_GET['action']) && $_GET['action'] == 'set_primary') { $this->_setAsPrimary($_GET['key']); }

		if (isset($_FILES['file']) && isset($_POST['avatar_src']) && isset($_POST['avatar_data'])) {
			$this->_cropImage();
		}

		// if (isset($_FILES['file'])) {
		// 	if ($this->_validateUpload($_FILES['file'])) { $this->_uploadEventImages($_FILES); }
		// }

		$this->_renderMsg();

		$this->_body = array(
			'class' 		=> 'event',
			'msgs'			=> $this->_msg,
			'errors' 		=> $this->_errors,
			'ministry_id' 	=> $this->_ministry,
			'post' 			=> $this->_post);

		return $this->_page();
	}
	
	/* Protected Methods
	-------------------------------*/
	protected function _validate($post) {
		$required = true;
		foreach ($post as $field) {
			if ($field == '') { $required = false; }
		}

		if (!$required) {
			$this->_errors[] = 'All fields are required!';
			return false;
		}

		$startDate = $_POST['event_start'] = strtotime($post['event_start']);
		$endDate   = $_POST['event_end'] = strtotime($post['event_end']);

		if ($startDate > $endDate) { $this->_errors[] = 'The start date must be before the end date.'; return false; }
		if ($endDate < $startDate) { $this->_errors[] = 'The end date must be after the start date.'; return false; }
		return true;
	}

	protected function _addEvent($post) {
		$slug = str_replace(' ', '-', $post['event_title']);

		$settings = array(
			'event_ministry' 	=> $this->_ministry,
			'event_title' 		=> $post['event_title'],
			'event_text'		=> $post['event_text'],
			'event_slug'		=> $slug,
			'event_type'		=> 'ministry',
			'event_start'		=> $post['event_start'],
			'event_end'			=> $post['event_end'],
			'event_active'		=> (int) $post['event_active'],
			'event_created'		=> new MongoDate(),
			'event_updated'		=> new MongoDate());

		$this->_collection['event']->insert($settings);
		$eventId = $settings['_id']->{'$id'};

		// set event images
		if (isset($_SESSION['event_tmpimage']) && !empty($_SESSION['event_tmpimage'])) {
			$image = $_SESSION['event_tmpimage'];

			$existsFilter = array('_id' => $image['_id']);
			$exists = $this->_collection['file']->findOne($existsFilter);

			if (!empty($exists)) {
				$settings = array(
					'file_parent' => $eventId,
					'file_active' => 1);

				$filter = array('_id' => $image['_id']);
				$this->_collection['file']->update($filter, array('$set' => $settings));

				unset($_SESSION['event_tmpimage']);
			}
		}

		$_SESSION['msg'][] = array(
			'type' => 'success',
			'msg'  => 'Event successfully created.');

		header('Location: /ministry/'.$this->_ministry.'/events');
		exit;
	}

	protected function _cropImage() {
		require($this->_lib.'/cropper/crop-avatar.php');

		$crop = new CropAvatar();
		$cropResult = $crop->cropImage($_POST['avatar_src'], $_POST['avatar_data'], $_FILES['file']);

		if ($cropResult['status']) {
			$filename = $this->_uploadEventImage($cropResult['dst']);
			$uploadAbsolutePath = 'http://'.$_SERVER['SERVER_NAME'].'/uploads/event';
		}

		$response = array(
	        'state'   => 200,
	        'message' => $cropResult['msg'],
	        'result'  => isset($filename) ? $uploadAbsolutePath.'/'.$filename : $cropResult['result']
	    );

	    echo json_encode($response);
	    exit;
	}

	protected function _uploadEventImage($fileTmpPath) {
		$file = basename($fileTmpPath);

		// move uploaded file
		if (rename($fileTmpPath, $this->_eventPath.'/'.$file)) {
			// base64_encode image
			$extension = explode('.', $file);
			$extension = end($extension);

			$rand 		= str_shuffle(basename($file)).rand(11111, 99999);
			$filename  	= md5($rand);
			$filename  	= $filename.'.'.$extension;

			rename($this->_eventPath.'/'.$file, $this->_eventPath.'/'.$filename);


			// check if image already exists
			if (isset($_SESSION['event_tmpimage'])) {
				$existsFilter = array(
					'file_name'   => $_SESSION['event_tmpimage']['file_name'],
					'file_active' => 0,
					'file_type'   => 'event');

				$exists = $this->_collection['file']->findOne($existsFilter);

				// if image exists update file and database
				if (!empty($exists)) {
					unlink($this->_eventPath.'/'.$_SESSION['event_tmpimage']['file_name']);

					// update datebase file name
					$settings = array('$set' => array('file_name' => $filename));
					$filter = array('_id' => $exists['_id']);

					$this->_collection['file']->update($filter, $settings);

					$_SESSION['event_tmpimage']['file_name'] = $filename;
					return $filename;
				}
			}

			$fileSettings = array(
				'file_name' 	 => $filename,
				'file_extension' => $extension,
				'file_type'		 => 'event',
				'file_active'	 => 0,
				'file_primary'	 => 1,
				'file_created'	 => new MongoDate(),
				'file_updated'	 => new MongoDate());

			$this->_collection['file']->insert($fileSettings);
			$fileId = $fileSettings['_id']->{'$id'};

			if (!isset($_SESSION['event_tmpimage'])) { $_SESSION['event_tmpimage'] = array(); }
			$_SESSION['event_tmpimage'] = $fileSettings;

			return $filename;
		}

		return false;
	}

	/* Private Methods
	-------------------------------*/
}
