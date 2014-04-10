<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Control_Page_Event_Edit extends Control_Page {
	/* Constants
	-------------------------------*/
	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title    = 'JLDMP - Control';
	protected $_class    = 'event';
	protected $_template = '/event/edit.phtml';

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
		$this->_id 		  = isset($this->_request['variables'][0]) ? $this->_request['variables'][0] : null;
		$this->_eventPath = dirname(__FILE__).'/../../../../../uploads/event/';

		if ($this->_id == null) { header('Location: /events'); exit; }

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

		// control()->output($this->_post);
		// exit;

		$this->_renderMsg();
		
		$this->_body = array(
			'class'		=> 'event',
			'msg'		=> $this->_msg,
			'errors' 	=> $this->_errors,
			'post' 		=> $this->_post);

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
		$event = $this->_db->search()
			->setTable('event')
			->setColumns('*')
			->addFilter('event_id = "'.$this->_id.'"')
			->getRow();

		$images = $this->_db->search()
			->setTable('file')
			->setColumns('*')
			->addFilter('file_parent = '.$event['event_id'].' AND file_type = "event" AND file_active = 1')
			->getRows();

		$event['event_images'] = $images;

		return $event;
	}

	protected function _editEvent($post) {
		$settings = array(
			'event_title' 	=> $post['event_title'],
			'event_text'  	=> $post['event_text'],
			'event_start'	=> strtotime($post['event_start']),
			'event_end'		=> strtotime($post['event_end']),
			'event_active'	=> $post['event_active'],
			'event_updated'	=> time());

		$filter[] = array('event_id=%s', $this->_id);
		$this->_db->updateRows('event', $settings, $filter);

		$_SESSION['msg'][] = array(
			'type' => 'success',
			'msg'  => 'Event Successfully updated.');

		header('Location: /events');
		exit;
	}

	protected function _setPrimaryImage($id) {
		// set existing primary image to 0
		$settings = array('file_primary' => 0);
		$filter[] = array('file_parent=%s', $this->_id);
		$filter[] = array('file_primary=%s', 1);
		$filter[] = array('file_type=%s', 'event');

		$this->_db->updateRows('file', $settings, $filter);
		$this->_updateDbPrimary($id);

		$_SESSION['msg'][] = array(
			'type' => 'success',
			'msg'  => 'Image successfully set as primary.');

		$this->_redirect();
	}

	protected function _updateDbPrimary($id) {
		$settings = array('file_primary' => 1);
		$filter[] = array('file_id=%s', $id);

		$this->_db->updateRows('file', $settings, $filter);
		return;
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
					'file_created'	 => time(),
					'file_updated'	 => time());

				// check primary is already set
				$this->_db->insertRow('file', $settings);

				// edit event update time
				$settings = array('event_updated' => time());
				$filter[] = array('event_id=%s', $this->_id);

				$this->_db->updateRows('event', $settings, $filter);
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
		$image = $this->_db->search()
			->setTable('file')
			->setColumns('*')
			->addFilter('file_id = '.$id.' AND file_parent = '.$this->_id.' AND file_type = "event"')
			->getRow();

		$filter[] = array('file_id=%s', $id);
		$filter[] = array('file_parent=%s', $this->_id);
		$filter[] = array('file_type=%s', 'event');

		$this->_db->deleteRows('file', $filter);

		unlink($this->_eventPath.$image['file_name']);

		$_SESSION['msg'][] = array(
			'type'  => 'success',
			'msg'	=> 'Successfully removed image.');

		$this->_redirect();
	}

	protected function _redirect() {
		header('Location: /event/edit/'.$this->_id);
		exit;
	}

	/* Private Methods
	-------------------------------*/
}
