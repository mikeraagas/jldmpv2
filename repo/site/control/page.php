<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * The base class for any class that defines a view.
 * A view controls how templates are loaded as well as 
 * being the final point where data manipulation can occur.
 *
 * @package    Eden
 */
abstract class Control_Page extends Eden_Class {
	/* Constants
	-------------------------------*/
	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_meta	= array();
	protected $_head 	= array();
	protected $_body 	= array();
	protected $_foot 	= array();
	
	protected $_title 		= null;
	protected $_class 		= null;
	protected $_template 	= null;
	protected $_db 			= null;
	protected $_request     = null;
	protected $_msg 		= array();

	// upload configs
	protected $_errors 		 = array();
	protected $_uploadErrors = array(
		UPLOAD_ERR_OK         => "No errors.",
		UPLOAD_ERR_INI_SIZE   => "Larger than upload_max_filesize.",
		UPLOAD_ERR_FORM_SIZE  => "Larger than form MAX_FILE_SIZE",
		UPLOAD_ERR_PARTIAL    => "Partial upload.",
		UPLOAD_ERR_NO_FILE    => "No file.",
		UPLOAD_ERR_NO_TMP_DIR => "No temporary directory.",
		UPLOAD_ERR_CANT_WRITE => "Can't write to disk.",
		UPLOAD_ERR_EXTENSION  => "File upload stopped by an extension."
    );

    protected $_fileNames 	 = array();
    protected $_fileTypes 	 = array();
    protected $_fileTmpPaths = array();
    protected $_fileSizes	 = array();
    protected $_fileErrors 	 = array();

    // table collections
    protected $_collection = array();
    protected $_dbMinistry = null;
    protected $_dbAdmin    = null;
    protected $_dbMember   = null;
    protected $_dbFile     = null;
	
	/* Private Properties
	-------------------------------*/
	/* Get
	-------------------------------*/
	/* Magic
	-------------------------------*/
	public function __construct() {
		$this->_request = control()->registry()->get('request');
		$this->_db 		= control()->getDatabase();

		// set database collections
		$this->_setCollections();
	}

	public function __toString() {
		try {
			$output = $this->render();
		} catch(Exception $e) {
			Eden_Error_Event::i()->exceptionHandler($e);
			return '';
		}
		
		if(is_null($output)) {
			return '';
		}
		
		return $output;
	}
	
	/* Public Methods
	-------------------------------*/
	/**
	 * Returns a string rendered version of the output
	 *
	 * @return string
	 */
	abstract public function render();
	
	/* Protected Methods
	-------------------------------*/	
	protected function _page() {
		$this->_head['page'] = $this->_class;
		
		$tpl  = control()->path('template');
		$page = control()->path('page');

		if ($this->_class == 'login') {
			if (isset($_SESSION['admin'])) {
				header('Location: /index');
				exit;
			}
	
			$body = control()->trigger('body')->template($tpl.$this->_template, $this->_body);
		}

		if ($this->_class != 'login') {
			if (!isset($_SESSION['admin'])) {
				header('Location: /login');
				exit;
			}

			$head = control()->trigger('head')->template($tpl.'/_head.phtml', $this->_head);
			$body = control()->trigger('body')->template($tpl.$this->_template, $this->_body);
			$foot = control()->trigger('foot')->template($tpl.'/_foot.phtml', $this->_foot);
		}
		
		return control()->template($tpl.'/_page.phtml', array(
			'meta' 			=> $this->_meta,
			'title'			=> $this->_title,
			'class'			=> $this->_class,
			'head'			=> isset($head) ? $head : null,
			'body'			=> $body,
			'foot'			=> isset($foot) ? $foot : null));
	}

	protected function _renderMsg() {
		if(isset($_SESSION['msg']) && !empty($_SESSION['msg'])) {
			$this->_msg = $_SESSION['msg'];
			unset($_SESSION['msg']);
		}
	}

	protected function _validateUpload($files) {
		$this->_fileNames 		= $files['name'];
		$this->_fileTypes 		= $files['type'];
		$this->_fileTmpPaths 	= $files['tmp_name'];
		$this->_fileSizes 		= $files['size'];
		$this->_fileErrors 		= $files['error'];

		// check file upload errors
		foreach ($this->_fileErrors as $error) {
			if ($error > 0) {
				$this->_errors[] = $this->_uploadErrors[$error];
				return false;
			}
		}

		$allowedExts = array('jpeg', 'jpg', 'png');
		$validType 	 = true;	

		// validate file type, img required
		foreach ($this->_fileTypes as $key => $type) {
			if ($type == 'image/gif' || 
				$type == 'image/jpeg' || 
				$type == 'image/jpg' || 
				$type == 'image/pjpeg' || 
				$type == 'image/x-png' ||
				$type == 'image/png') {
				continue;
			}

			$validType = false;
			unset($this->_fileTmpPaths[$key]);
		}

		foreach ($this->_fileNames as $key => $filename) {
			$extension = explode('.', $filename);
			$extension = end($extension);

			if (!in_array($extension, $allowedExts)) {
				$validType = false;
				unset($this->_fileTmpPaths[$key]);
			}
		}

		if ($validType == false) {
			$this->_errors[] = 'Invalid file type. Files must be an image!';
			return false;
		}

		return true;
	}

	protected function _setCollections() {
		$this->_collection = array(
			'ministry' => new MongoCollection($this->_db, 'ministry'),
			'member'   => new MongoCollection($this->_db, 'member'),
			'event'    => new MongoCollection($this->_db, 'event'),
			'admin'    => new MongoCollection($this->_db, 'admin'),
			'file'     => new MongoCollection($this->_db, 'file'));

		$this->_dbMinistry = new MongoCollection($this->_db, 'ministry');
		$this->_dbMember   = new MongoCollection($this->_db, 'member');
		$this->_dbAdmin    = new MongoCollection($this->_db, 'admin');
		$this->_dbFile     = new MongoCollection($this->_db, 'file');
	}

	/* Private Methods
	-------------------------------*/
}