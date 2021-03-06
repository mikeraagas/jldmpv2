<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Control_Page_Event_Add extends Control_Page {
	/* Constants
	-------------------------------*/
	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title    = 'JLDMP - Control';
	protected $_class    = 'event';
	protected $_template = '/event/add.phtml';

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
		$this->_eventPath = dirname(__FILE__).'/../../../../../uploads/event/';

		if (isset($_POST['add_event'])) {
			if ($this->_validate($_POST)) {
				$this->_addEvent($_POST);
			}

			$this->_post = $_POST;
		}

		if (isset($_GET['action']) && $_GET['action'] == 'remove_upload') { $this->_removeUploadedImage($_GET['id']); }
		if (isset($_GET['action']) && $_GET['action'] == 'set_primary') { $this->_setAsPrimary($_GET['key']); }

		if (isset($_FILES['file'])) {
			if ($this->_validateUpload($_FILES['file'])) { $this->_uploadEventImages($_FILES); }
		}

		$this->_renderMsg();

		$this->_body = array(
			'class' 		=> 'event',
			'msg'			=> $this->_msg,
			'errors' 		=> $this->_errors,
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

		if (!$required) { $this->_errors[] = 'All fields are required!'; return false; }

		$startDate = strtotime($post['event_start']);
		$endDate   = strtotime($post['event_end']);

		if ($startDate > $endDate) { $this->_errors[] = 'The start date must be before the end date.'; return false; }
		if ($endDate < $startDate) { $this->_errors[] = 'The end date must be after the start date.'; return false; }
		return true;
	}

	protected function _addEvent($post) {
		$slug = str_replace(' ', '-', $post['event_title']);

		$settings = array(
			'event_title' 		=> $post['event_title'],
			'event_text'		=> $post['event_text'],
			'event_slug'		=> $slug,
			'event_type'		=> 'general',
			'event_start'		=> strtotime($post['event_start']),
			'event_end'			=> strtotime($post['event_end']),
			'event_active'		=> (int) $post['event_active'],
			'event_created'		=> new MongoDate(),
			'event_updated'		=> new MongoDate());

		$this->_collection['event']->insert($settings);
		$eventId = $settings['_id']->{'$id'};

		// set event images
		if (isset($_SESSION['event_tmpimages']) && !empty($_SESSION['event_tmpimages'])) {
			$images = $_SESSION['event_tmpimages'];

			foreach ($images as $image) {
				$fileId = $image['_id']->{'$id'};

				$existsFilter = array('_id' => new MongoId($fileId));
				$exists = $this->_collection['file']->findOne($existsFilter);

				if (empty($exists)) continue;

				$settings = array(
					'file_parent' => $eventId,
					'file_active' => 1);

				$filter = array('_id' => new MongoId($fileId));
				$this->_collection['file']->update($filter, array('$set' => $settings));
			}

			unset($_SESSION['event_tmpimages']);
		}

		$_SESSION['msg'][] = array(
			'type' => 'success',
			'msg'  => 'Event successfully created.');

		header('Location: /events');
		exit;
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

				$fileSettings = array(
					'file_name' 	 => $filename,
					'file_extension' => $this->_fileTypes[$key],
					'file_type'		 => 'event',
					'file_active'	 => 0,
					'file_primary'   => 0,
					'file_created'	 => new MongoDate(),
					'file_updated'	 => new MongoDate());

				// check primary is already set
				$primarySet = $this->_checkIfPrimary();
				if (!$primarySet) { $fileSettings['file_primary'] = 1; }

				$this->_collection['file']->insert($fileSettings);

				if (!isset($_SESSION['event_tmpimages'])) { $_SESSION['event_tmpimages'] = array(); }
				$_SESSION['event_tmpimages'][] = $fileSettings;

				continue;
			}

			$_SESSION['msg'][] = array(
				'type' 	=> 'danger',
				'msg'	=> 'Image upload failed, possibly due to incorrect permissions on the upload folder.');

			unset($_SESSION['event_tmpimages']);

			header('Location: /event/add');
			exit;
		}

		unset($fileTmpPath);

		$_SESSION['msg'][] = array(
			'type' 	=> 'success',
			'msg'	=> 'Successfully uploaded event images.');

		header('Location: /event/add');
		exit;
	}

	protected function _removeUploadedImage($id) {
		$uploaded = $_SESSION['event_tmpimages'];

		foreach ($uploaded as $key => $image) {
			if ($image['_id']->{'$id'} == $id) {
				unlink($this->_eventPath.$_SESSION['event_tmpimages'][$key]['file_name']);
				unset($_SESSION['event_tmpimages'][$key]);

				$filter = array('_id' => new MongoId($id));
				$this->_collection['file']->remove($filter, array('justOne' => true));

				$_SESSION['msg'][] = array(
					'type' 	=> 'success',
					'msg'	=> 'Successfully removed uploaded image.');

				header('Location: /event/add');
				exit;
			}
		}

		if (empty($_SESSION['event_tmpimages'])) { unset($_SESSION['event_tmpimages']); }

		$_SESSION['msg'][] = array(
			'type' 	=> 'danger',
			'msg'	=> 'Image does not exists in uploaded files!');

		header('Location: /event/add');
		exit;
	}

	protected function _setAsPrimary($key) {
		if (isset($_SESSION['event_tmpimages'])) {
			// set other uploaded files to not primary
			foreach ($_SESSION['event_tmpimages'] as $k => $image) {
				if ($image['file_primary'] == 1) {
					$_SESSION['event_tmpimages'][$k]['file_primary'] = 0;

					$settings = array('file_primary' => 0);
					$filter   = array('_id' => $image['_id']->{'$id'});

					$this->_collection['file']->update($filter, array('$set' => $settings));
				}
			}

			// update selected file to primary
			$_SESSION['event_tmpimages'][$key]['file_primary'] = 1;

			$id = $_SESSION['event_tmpimages'][$key]['_id']->{'$id'};
			$primarySettings = array('file_primary' => 1);
			$primaryFilter   = array('_id' => $id);

			$this->_collection['file']->update($primaryFilter, $primarySettings);

			$_SESSION['msg'][] = array(
				'type' => 'success',
				'msg'  => 'Successfully set image as primary.');
			
			header('Location: /event/add');
			exit;
		}
	}

	protected function _updateDbImage($id, $settings) {
		$filter[] = array('file_id=%s', $id);

		$this->_db->updateRows('file', $settings, $filter);
		return;
	}

	protected function _checkIfPrimary() {
		if (isset($_SESSION['event_tmpimages'])) {
			foreach ($_SESSION['event_tmpimages'] as $key => $image) {
				if ($image['file_primary'] == 1) {
					return true;
				}
			}
		}

		return false;
	}

	/* Private Methods
	-------------------------------*/
}
