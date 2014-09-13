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
abstract class Front_Page extends Eden_Class {
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
	
	protected $_title 		= NULL;
	protected $_class 		= NULL;
	protected $_template 	= NULL;

	protected $_db          = null;
	protected $_request     = null;
	protected $_collection  = null;
	
	/* Private Properties
	-------------------------------*/
	/* Get
	-------------------------------*/
	/* Magic
	-------------------------------*/
	public function __construct() {
		$this->_request = front()->registry()->get('request');
		$this->_db 		= front()->getDatabase();

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
		
		$page = front()->path('page');
		$tpl  = front()->path('template');

		$head = front()->trigger('head')->template($tpl.'/_head.phtml', $this->_head);
		$body = front()->trigger('body')->template($tpl.$this->_template, $this->_body);
		$foot = front()->trigger('foot')->template($tpl.'/_foot.phtml', $this->_foot);
		
		//page
		return front()->template($tpl.'/_page.phtml', array(
			'meta' 			=> $this->_meta,
			'title'			=> $this->_title,
			'class'			=> $this->_class,
			'head'			=> $head,
			'body'			=> $body,
			'foot'			=> $foot));
	}

	protected function _setCollections() {
		$this->_collection = array(
			'ministry' => new MongoCollection($this->_db, 'ministry'),
			'member'   => new MongoCollection($this->_db, 'member'),
			'event'    => new MongoCollection($this->_db, 'event'),
			'admin'    => new MongoCollection($this->_db, 'admin'),
			'file'     => new MongoCollection($this->_db, 'file'));
	}
	
	/* Private Methods
	-------------------------------*/
}