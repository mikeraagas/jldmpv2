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
		$this->_eventPath = dirname(__FILE__).'/../../../../../../uploads/event/';

		if ($this->_id == null) { header('Location: /ministry/'.$this->_ministry.'/members'); exit; }

		if (isset($_GET['action']) && $_GET['action'] == 'remove_image') { $this->_removeEventImage($_GET['id']); }
		if (isset($_GET['action']) && $_GET['action'] == 'set_primary') { $this->_setPrimaryImage($_GET['id']); }

		if (isset($_FILES['file'])) {
			if ($this->_validateUpload($_FILES['file'])) { $this->_uploadEventImages($_FILES); }
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

		if (!$required) { $this->_errors[] = 'All fields are required!'; return false; }

		$startDate = strtotime($post['event_start']);
		$endDate   = strtotime($post['event_end']);

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
			'file_parent' => $eventId,
			'file_type'   => 'event',
			'file_active' => 1);

		// get event images
		$query  = $this->_collection['file']->find($imagesFilter);
		$images = iterator_to_array($query);

		$event['event_images'] = $images;
		return $event;
	}

	protected function _editEvent($post) {
		$settings = array(
			'event_title' 	=> $post['event_title'],
			'event_text'  	=> $post['event_text'],
			'event_start'	=> strtotime($post['event_start']),
			'event_end'		=> strtotime($post['event_end']),
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

	protected function _setPrimaryImage($id) {
		// set existing primary image to 0
		$settings = array('file_primary' => 0);
		$filter = array(
			'file_parent'  => $this->_id,
			'file_primary' => 1,
			'file_type'    => 'event');

		$this->_collection['file']->update($filter, array('$set' => $settings));

		// update file set primary
		$primarySettings = array('file_primary' => 1);
		$primaryFilter   = array('_id' => new MongoId($id));
		$this->_collection['file']->update($primaryFilter, array('$set' => $primarySettings));

		$_SESSION['msg'][] = array(
			'type' => 'success',
			'msg'  => 'Image successfully set as primary.');

		$this->_redirect();
	}

	protected function _uploadEventImages() {
		foreach ($this->_fileTmpPaths as $key => $fileTmpPath) {
			// move uploaded file
			if (move_uploaded_file($fileTmpPath, $this->_eventPath.$this->_fileNames[$key])) {
				// base64_encode image
				$extension = explode('.', $this->_fileNames[$key]);
				$extension = end($extension);

				$rand = str_shuffle(basename($this->_fileNames[$key])).rand(11111, 99999);
				$filename  = md5($rand);
				$filename  = $filename.'.'.$extension;

				rename($this->_eventPath.$this->_fileNames[$key], $this->_eventPath.$filename);

				$settings = array(
					'file_parent' 	 => $this->_id,
					'file_name' 	 => $filename,
					'file_extension' => $this->_fileTypes[$key],
					'file_type'		 => 'event',
					'file_active'	 => 1,
					'file_primary'   => 0,
					'file_created'	 => new MongoDate(),
					'file_updated'	 => new MongoDate());

				// check primary is already set
				if (!$this->_checkIfPrimary()) { $settings['file_primary'] = 1; }

				$this->_collection['file']->insert($settings);

				// edit event update time
				$settings = array('event_updated' => new MongoDate());
				$filter = array('_id' => new MongoId($this->_id));

				$this->_collection['event']->update($filter, array('$set' => $settings));
				continue;
			}

			$_SESSION['msg'][] = array(
				'type' 	=> 'danger',
				'msg'	=> 'Image upload failed, possibly due to incorrect permissions on the upload folder.');

			unset($_SESSION['event_tmpimages']);

			$this->_redirect();
		}

		unset($fileTmpPath);

		$_SESSION['msg'][] = array(
			'type' 	=> 'success',
			'msg'	=> 'Successfully uploaded event images.');

		$this->_redirect();
	}

	protected function _removeEventImage($id) {
		$fileFilter = array('_id' => new MongoId($id));
		$image = $this->_collection['file']->findOne($fileFilter);

		$filter = array(
			'_id'         => new MongoId($id),
			'file_parent' => $this->_id,
			'file_type'   => 'event');

		$this->_collection['file']->remove($filter, array('justOne' => true));

		unlink($this->_eventPath.$image['file_name']);

		$_SESSION['msg'][] = array(
			'type'  => 'success',
			'msg'	=> 'Successfully removed image.');

		$this->_redirect();
	}

	protected function _checkIfPrimary() {
		$filter = array(
			'file_parent' => $this->_id,
			'file_active' => 1,
			'file_type'   => 'event');

		$query = $this->_collection['file']->find($filter);
		$images = iterator_to_array($query);

		foreach ($images as $key => $image) {
			if ($image['file_primary'] == 1) {
				return true;
			}
		}

		return false;
	}

	protected function _redirect() {
		header('Location: /ministry/'.$this->_ministry.'/event/edit/'.$this->_id);
		exit;
	}

	/* Private Methods
	-------------------------------*/
}
