<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Control_Page_Ministry_Add extends Control_Page {
	/* Constants
	-------------------------------*/
	const ALL_FIELDS_REQUIRED = 'All fields are required!';
	const PASSWORD_NOT_MATCH  = 'Password did not match!';
	const USERNAME_ERROR  	  = 'Username must be 6 or more characters!';
	const PASSWORD_ERROR      = 'Password must be 8 or more characters!';
	const MINISTRY_EXISTS 	  = "Ministry already exists!";

	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title    = 'JLDMP - Control';
	protected $_class    = 'ministry';
	protected $_template = '/ministry/add.phtml';
	protected $_msg      = array();
	protected $_error    = null;
	
	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {

		if (isset($_POST['add_ministry'])) {
			if ($this->_setErrors($_POST)) {
				$this->_addMinistry($_POST);
			}

			$post = $_POST;
		}

		if (!empty($this->_error)) { 
			$this->_msg = array(
				'type' => 'danger',
				'msg'  => $this->_error);
		}

		if (isset($_SESSION['msg'])) {
			$this->_msg = $_SESSION['msg'];
			unset($_SESSION['msg']);
		}
		
		$this->_body = array(
			'post' => isset($post) ? $post : array(),
			'msg' => isset($this->_msg) ? $this->_msg: array());

		return $this->_page();
	}
	
	/* Protected Methods
	-------------------------------*/
	protected function _setErrors($post) {
		foreach ($post as $field => $value) {
			if ($value == '') {
				$this->_error = self::ALL_FIELDS_REQUIRED;
				return false;
			}
		}
		
		if (strlen($post['username']) < 5) { $this->_error = self::USERNAME_ERROR; return false; }
		if (strlen($post['password']) < 8 && strlen($post['confirm']) < 8) { $this->_error = self::PASSWORD_ERROR; return false; }
		if ($post['password'] != $post['confirm']) { $this->_error = self::PASSWORD_NOT_MATCH; return false; }
		if ($this->_checkExists($post['title'])) { $this->_error = self::MINISTRY_EXISTS; return false; }

		return true;
	}

	protected function _checkExists($title) {
		// check if exists
		$exists = $this->_db->search()
			->setTable('ministry')
			->setColumns('*')
			->filterByMinistryTitle($title)
			->getRow();

		if (!empty($exists)) {
			return true;
		}

		return false;
	}

	protected function _addMinistry($post) {
		// add admin
		$settings = array(
			'admin_name' 	 => $post['name'],
			'admin_email'    => $post['email'],
			'admin_username' => $post['username'],
			'admin_password' => md5($post['password']),
			'admin_active'   => 1,
			'admin_type'     => 2,
			'admin_created'  => time(),
			'admin_updated'  => time());

		$this->_db->insertRow('admin', $settings);
		$ministryId = $this->_db->getLastInsertedId();

		// add ministry
		$settings = array(
			'ministry_admin' 		=> $ministryId,
			'ministry_title' 		=> $post['title'],
			'ministry_description' 	=> $post['description'],
			'ministry_active' 		=> $post['active'],
			'ministry_created' 		=> time(),
			'ministry_updated' 		=> time());

		$this->_db->insertRow('ministry', $settings);

		$_SESSION['msg'] = array(
			'type' => 'success',
			'msg'  => 'Ministry successfully added.');

		header('Location: /ministry/add');
		exit;
	}

	/* Private Methods
	-------------------------------*/
}
