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
		$this->_eventPath = dirname(__FILE__).'/../../../../../../uploads/event/';

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
			'event_ministry' 	=> $this->_ministry,
			'event_title' 		=> $post['event_title'],
			'event_text'		=> $post['event_text'],
			'event_slug'		=> $slug,
			'event_type'		=> 1,
			'event_start'		=> strtotime($post['event_start']),
			'event_end'			=> strtotime($post['event_end']),
			'event_active'		=> $post['event_active'],
			'event_created'		=> time(),
			'event_updated'		=> time());

		$this->_db->insertRow('event', $settings);
		$fileId = $this->_db->getLastInsertedId();

		// set event images
		if (isset($_SESSION['event_tmpimages']) && !empty($_SESSION['event_tmpimages'])) {
			$images = $_SESSION['event_tmpimages'];

			foreach ($images as $image) {
				$file = $this->_db->search()
					->setTable('file')
					->setColumns('*')
					->filterByFileId($image['file_id'])
					->getRow();

				if (empty($file)) continue;

				$settings = array(
					'file_parent' => $fileId,
					'file_active' => 1);

				$this->_updateDbImage($file['file_id'], $settings);
			}

			unset($_SESSION['event_tmpimages']);
		}

		$_SESSION['msg'][] = array(
			'type' => 'success',
			'msg'  => 'Event successfully created.');

		header('Location: /ministry/'.$this->_ministry.'/events');
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

				$settings = array(
					'file_name' 	 => $filename,
					'file_extension' => $this->_fileTypes[$key],
					'file_type'		 => 'event',
					'file_active'	 => 0,
					'file_created'	 => time(),
					'file_updated'	 => time());

				// check primary is already set
				$primarySet = $this->_checkIfPrimary();
				if (!$primarySet) { $settings['file_primary'] = 1; }

				$this->_db->insertRow('file', $settings);
				$fileId = $this->_db->getLastInsertedId();

				$file = $this->_db->search()
					->setTable('file')
					->setColumns('*')
					->filterByFileId($fileId)
					->getRow();

				if (!isset($_SESSION['event_tmpimages'])) { $_SESSION['event_tmpimages'] = array(); }

				$_SESSION['event_tmpimages'][] = $file;
				continue;
			}

			$_SESSION['msg'][] = array(
				'type' 	=> 'danger',
				'msg'	=> 'Image upload failed, possibly due to incorrect permissions on the upload folder.');

			unset($_SESSION['event_tmpimages']);

			header('Location: /ministry/'.$this->_ministry.'/event/add');
			exit;
		}

		unset($fileTmpPath);

		$_SESSION['msg'][] = array(
			'type' 	=> 'success',
			'msg'	=> 'Successfully uploaded event images.');

		header('Location: /ministry/'.$this->_ministry.'/event/add');
		exit;
	}

	protected function _removeUploadedImage($id) {
		$uploaded = $_SESSION['event_tmpimages'];

		foreach ($uploaded as $key => $image) {
			if ($image['file_id'] == $id) {
				unlink($this->_eventPath.$_SESSION['event_tmpimages'][$key]['file_name']);
				unset($_SESSION['event_tmpimages'][$key]);

				$filter[] = array('file_id=%s', $id);
				$this->_db->deleteRows('file', $filter);

				$_SESSION['msg'][] = array(
					'type' 	=> 'success',
					'msg'	=> 'Successfully removed uploaded image.');

				header('Location: /ministry/'.$this->_ministry.'/event/add');
				exit;
			}
		}

		if (empty($_SESSION['event_tmpimages'])) { unset($_SESSION['event_tmpimages']); }

		$_SESSION['msg'][] = array(
			'type' 	=> 'danger',
			'msg'	=> 'Image does not exists in uploaded files!');

		header('Location: /ministry/'.$this->_ministry.'/event/add');
		exit;
	}

	protected function _setAsPrimary($key) {
		if (isset($_SESSION['event_tmpimages'])) {
			foreach ($_SESSION['event_tmpimages'] as $k => $image) {
				if ($image['file_primary'] == 1) {
					$_SESSION['event_tmpimages'][$k]['file_primary'] = 0;

					$settings = array('file_primary' => 0);
					$filter[] = array('file_id=%s', $image['file_id']);

					$this->_db->updateRows('file', $settings, $filter);
				}
			}

			$_SESSION['event_tmpimages'][$key]['file_primary'] = 1;

			$settings = array('file_primary' => 1);
			$this->_updateDbImage($_SESSION['event_tmpimages'][$key]['file_id'], $settings);

			$_SESSION['msg'][] = array(
				'type' => 'success',
				'msg'  => 'Successfully set image as primary.');
			
			header('Location: /ministry/'.$this->_ministry.'/event/add');
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
