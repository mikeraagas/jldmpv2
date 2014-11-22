<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Control_Page_Ministry_News_Edit extends Control_Page {
	/* Constants
	-------------------------------*/
	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title    = 'JLDMP - Control';
	protected $_class    = 'ministry';
	protected $_template = '/ministry/news/edit.phtml';

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
		$this->_id       = isset($this->_request['variables'][1]) ? $this->_request['variables'][1] : null;
		$this->_newsPath = $this->_uploads.'/news';

		// if not isset ministry or id
		if (!$this->_ministry || !$this->_id) { header('Location: /ministry/'.$this->_ministry.'/news'); exit; }

		if (isset($_FILES['file']) && isset($_POST['avatar_src']) && isset($_POST['avatar_data'])) {
			$this->_cropImage();
		}

		$this->_post = $this->_getNews();

		if (isset($_POST['add_news'])) {
			if ($this->_validate($_POST)) {
				$this->_editNews($_POST);
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
	protected function _getNews() {
		// get news
		$newsFilter = array(
			'_id'            => new MongoId($this->_id),
			'news_ministry' => $this->_ministry);

		$news = $this->_collection['news']->findOne($newsFilter);
		$newsId = $news['_id']->{'$id'};

		$imagesFilter = array(
			'file_parent' => $newsId,
			'file_type'   => 'news',
			'file_active' => 1);

		// get news images
		$image  = $this->_collection['file']->findOne($imagesFilter);
		$news['news_image'] = $image;

		return $news;
	}

	protected function _validate($post) {
		// check if field is empty
		$required = true;
		foreach ($post as $key => $field) {
			if ($field == '') { $required = false; }
		}

		if (!$required) { $this->_errors[] = 'All fields are required!'; return false; }
		return true;
	}

	protected function _editNews($post) {
		$settings = array(
			'news_title'    => $post['news_title'],
			'news_details'  => $post['news_details'],
			'news_active'   => (int) $post['news_active'],
			'news_updated'  => new MongoDate());

		$filter = array('_id' => new MongoId($this->_id));
		$this->_collection['news']->update($filter, array('$set' => $settings));

		$_SESSION['msg'][] = array(
			'type' => 'success',
			'msg'  => 'News successfully updated.');

		header('Location: /ministry/'.$this->_ministry.'/news');
		exit;
	}

	protected function _cropImage() {
		require($this->_lib.'/cropper/crop-avatar.php');

		$crop = new CropAvatar();
		$cropResult = $crop->cropImage($_POST['avatar_src'], $_POST['avatar_data'], $_FILES['file']);

		$response = array('state' => 200);

		if ($cropResult['status']) {
			$uploadResult = $this->_uploadNewsImage($cropResult['dst']);
			$uploadAbsolutePath = 'http://'.$_SERVER['SERVER_NAME'].'/uploads/news';

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

			// check if file exists
			$existsFilter = array(
				'file_parent'  => $this->_id,
				'file_type'    => 'news',
				'file_active'  => 1,
				'file_primary' => 1);

			$exists = $this->_collection['file']->findOne($existsFilter);

			if (!empty($exists)) {
				unlink($this->_newsPath.'/'.$exists['file_name']);

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
