<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Control_Page_Ministry extends Control_Page {
	/* Constants
	-------------------------------*/
	const RANGE = 10;

	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title    = 'JLDMP - Control';
	protected $_class    = 'ministry';
	protected $_template = '/ministry.phtml';
	protected $_msg      = array();
	
	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {
		if (isset($_POST['add_ministry'])) {
			$this->_addMinistry($_POST);
		}

		$ministries = $this->_getMinistries();

		if(isset($_SESSION['msg']) && !empty($_SESSION['msg'])) {
			$this->_msg = $_SESSION['msg'];
			unset($_SESSION['msg']);		
		}

		if (isset($_SESSION['addHistory'])) {
			$addHistory = $_SESSION['addHistory'];
			unset($_SESSION['addHistory']);
		}

		$this->_body = array(
			'ministries' => $ministries,
			'msg' 		 => $this->_msg,
			'addHistory' => isset($addHistory) ? $addHistory : array());

		return $this->_page();
	}
	
	/* Protected Methods
	-------------------------------*/
	protected function _getMinistries() {
		$ministries = $this->_db->search()
			->setTable('ministry')
			->setColumns('*')
			->setRange(self::RANGE)
			->getRows();

		return $ministries;
	}

	protected function _addMinistry($post) {
		// check if exists
		$exists = $this->_db->search()
			->setTable('ministry')
			->setColumns('*')
			->filterByMinistryTitle($post['title'])
			->getRow();

		if (!empty($exists)) {
			$_SESSION['msg'] = array(
				'type'   => 'danger',
				'msg'  	 => 'Ministry already exists',
				'action' => 'add');

			$_SESSION['addHistory'] = $post;

			header('Location: /ministry');
			exit;
		}

		$settings = array(
			'ministry_title' 		=> $post['title'],
			'ministry_description' 	=> $post['description'],
			'ministry_active' 		=> $post['active'],
			'ministry_created' 		=> time(),
			'ministry_updated' 		=> time());

		$this->_db->insertRow('ministry', $settings);

		$_SESSION['msg'] = array(
			'type' => 'success',
			'msg'  => 'Ministry successfully added.');

		header('Location: /ministry');
		exit;
	}

	/* Private Methods
	-------------------------------*/
}
