<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Control_Page_Ministry_Event_Edit extends Control_Page {
	/* Constants
	-------------------------------*/
	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title    = 'JLDMP - Control';
	protected $_class    = 'ministry';
	protected $_template = '/ministry/event/edit.phtml';

	protected $_ministry  = null;
	protected $_id 		  = null;
	protected $_eventPath = null;
	protected $_post 	  = array();
	protected $_msg 	  = array();

	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {
		$this->_ministry  = isset($this->_request['variables'][0]) ? $this->_request['variables'][0] : null;
		$this->_id 		  = isset($this->_request['variables'][1]) ? $this->_request['variables'][1] : null;
		$this->_eventPath = $this->_uploads.'/event';

		if ($this->_id == null) { header('Location: /ministry/'.$this->_ministry.'/events'); exit; }

		if (isset($_GET['action']) && $_GET['action'] == 'remove_image') { $this->_removeEventImage($_GET['id']); }
		if (isset($_GET['action']) && $_GET['action'] == 'set_primary') { $this->_setPrimaryImage($_GET['id']); }

		if (isset($_FILES['file']) && isset($_POST['avatar_src']) && isset($_POST['avatar_data'])) {
			$this->_cropImage();
		}
		
		$this->_post = $this->_getEvent();

		if (isset($_POST['edit_event'])) {
			if ($this->_validate($_POST)) {
				$this->_editEvent($_POST);
			}

			$this->_post = $_POST;
		}

		$this->_renderMsg();
		
		$this->_body = array(
			'class'			=> 'event',
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

		$startDate = $_POST['event_start'] = strtotime($post['event_start']);
		$endDate   = $_POST['event_end'] = strtotime($post['event_end']);

		if (!$required) {
			$this->_errors[] = 'All fields are required!';			
			return false;
		}

		if ($startDate > $endDate) { $this->_errors[] = 'The start date must be before the end date.'; return false; }
		if ($endDate < $startDate) { $this->_errors[] = 'The end date must be after the start date.'; return false; }

		return true;
	}

	protected function _getEvent() {
		// get event
		$eventFilter = array(
			'_id'            => new MongoId($this->_id),
			'event_ministry' => $this->_ministry);

		$event = $this->_collection['event']->findOne($eventFilter);
		$eventId = $event['_id']->{'$id'};

		$imagesFilter = array(
			'file_parent'  => $eventId,
			'file_type'    => 'event',
			'file_primary' => 1,
			'file_active'  => 1);

		// get event images
		$image  = $this->_collection['file']->findOne($imagesFilter);
		$event['event_image'] = $image;

		return $event;
	}

	protected function _editEvent($post) {
		$settings = array(
			'event_title' 	=> $post['event_title'],
			'event_text'  	=> $post['event_text'],
			'event_start'	=> $post['event_start'],
			'event_end'		=> $post['event_end'],
			'event_active'	=> (int) $post['event_active'],
			'event_updated'	=> new MongoDate());

		$filter = array('_id' => new MongoId($this->_id));
		$this->_collection['event']->update($filter, array('$set' => $settings));

		$_SESSION['msg'][] = array(
			'type' => 'success',
			'msg'  => 'Event Successfully updated.');

		header('Location: /ministry/'.$this->_ministry.'/events');
		exit;
	}

	protected function _cropImage() {
		require($this->_lib.'/cropper/crop-avatar.php');

		$crop = new CropAvatar();
		$cropResult = $crop->cropImage($_POST['avatar_src'], $_POST['avatar_data'], $_FILES['file']);

		$response = array('state' => 200);

		if ($cropResult['status']) {
			$uploadResult = $this->_uploadEventImage($cropResult['dst']);
			$uploadAbsolutePath = 'http://'.$_SERVER['SERVER_NAME'].'/uploads/event';

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

			// check if file exists
			$existsFilter = array(
				'file_parent'  => $this->_id,
				'file_type'    => 'event',
				'file_active'  => 1,
				'file_primary' => 1);

			$exists = $this->_collection['file']->findOne($existsFilter);

			if (!empty($exists)) {
				unlink($this->_eventPath.'/'.$exists['file_name']);

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
