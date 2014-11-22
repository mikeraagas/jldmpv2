<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Control_Page_Ministry_News_Add extends Control_Page {
	/* Constants
	-------------------------------*/
	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title    = 'JLDMP - Control';
	protected $_class    = 'ministry';
	protected $_template = '/ministry/news/add.phtml';

	protected $_ministry = null;
	protected $_newsPath = null;
	protected $_post     = array();
	protected $_msg      = array();
	protected $_errors   = array();

	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {
		$this->_ministry = isset($this->_request['variables'][0]) ? $this->_request['variables'][0] : null;
		$this->_newsPath = $this->_uploads.'/news';

		// if not isset ministry
		if (!$this->_ministry) { header('Location: /ministries'); exit; }

		if (isset($_FILES['file']) && isset($_POST['avatar_src']) && isset($_POST['avatar_data'])) {
			$this->_cropImage();
		}

		if (isset($_POST['add_news'])) {
			if ($this->_validate($_POST)) {
				$this->_addNews($_POST);
			}

			$this->_post = $_POST;
		}

		$this->_renderMsg();

		$this->_body = array(
			'class'			=> 'news',
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
		return true;
	}

	protected function _addNews($post) {
		$newsSettings = array(
			'news_ministry' => $this->_ministry,
			'news_title'    => $post['news_title'],
			'news_details'  => $post['news_details'],
			'news_active'   => (int) $post['news_active'],
			'news_type'     => 'ministry',
			'news_created'  => new MongoDate(),
			'news_updated'  => new MongoDate());

		$this->_collection['news']->insert($newsSettings);
		$newsId = $newsSettings['_id']->{'$id'};

		// set member images
		if (isset($_SESSION['news_tmpimage']) && !empty($_SESSION['news_tmpimage'])) {
			$image = $_SESSION['news_tmpimage'];

			$fileFilter = array('_id' => $image['_id']);
			$file = $this->_collection['file']->findOne($fileFilter);

			if (!empty($file)) {
				$settings = array(
					'file_parent' => $newsId,
					'file_active' => 1);

				$this->_collection['file']->update($fileFilter, array('$set' => $settings));

				unset($_SESSION['news_tmpimage']);
			}
		}

		$_SESSION['msg'][] = array(
			'type' => 'success',
			'msg'  => 'News successfully added.');

		header('Location: /ministry/'.$this->_ministry.'/news');
		exit;
	}

	protected function _cropImage() {
		require($this->_lib.'/cropper/crop-avatar.php');

		$crop = new CropAvatar();
		$cropResult = $crop->cropImage($_POST['avatar_src'], $_POST['avatar_data'], $_FILES['file']);

		if ($cropResult['status']) {
			$filename = $this->_uploadNewsImage($cropResult['dst']);
			$uploadAbsolutePath = 'http://'.$_SERVER['SERVER_NAME'].'/uploads/news';
		}

		$response = array(
	        'state'   => 200,
	        'message' => $cropResult['msg'],
	        'result'  => isset($filename) ? $uploadAbsolutePath.'/'.$filename : $cropResult['result']
	    );

	    echo json_encode($response);
	    exit;
	}

	protected function _uploadNewsImage($fileTmpPath) {
		$file = basename($fileTmpPath);

		// move uploaded file
		if (rename($fileTmpPath, $this->_newsPath.'/'.$file)) {
			// base64_encode image
			$extension = explode('.', $file);
			$extension = end($extension);

			$rand 		= str_shuffle(basename($file)).rand(11111, 99999);
			$filename  	= md5($rand);
			$filename  	= $filename.'.'.$extension;

			rename($this->_newsPath.'/'.$file, $this->_newsPath.'/'.$filename);


			// check if image already exists
			if (isset($_SESSION['news_tmpimage'])) {
				$existsFilter = array(
					'file_name'   => $_SESSION['news_tmpimage']['file_name'],
					'file_active' => 0,
					'file_type'   => 'news');

				$exists = $this->_collection['file']->findOne($existsFilter);

				// if image exists update file and database
				if (!empty($exists)) {
					unlink($this->_newsPath.'/'.$_SESSION['news_tmpimage']['file_name']);

					// update datebase file name
					$settings = array('$set' => array('file_name' => $filename));
					$filter = array('_id' => $exists['_id']);

					$this->_collection['file']->update($filter, $settings);

					$_SESSION['news_tmpimage']['file_name'] = $filename;
					return $filename;
				}
			}

			$fileSettings = array(
				'file_name' 	 => $filename,
				'file_extension' => $extension,
				'file_type'		 => 'news',
				'file_active'	 => 0,
				'file_primary'	 => 1,
				'file_created'	 => new MongoDate(),
				'file_updated'	 => new MongoDate());

			$this->_collection['file']->insert($fileSettings);
			$fileId = $fileSettings['_id']->{'$id'};

			if (!isset($_SESSION['news_tmpimage'])) { $_SESSION['news_tmpimage'] = array(); }
			$_SESSION['news_tmpimage'] = $fileSettings;

			return $filename;
		}

		return false;
	}

	/* Private Methods
	-------------------------------*/
}
