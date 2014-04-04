<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Control_Page_Ministry_Edit extends Control_Page {
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
	protected $_template = '/ministry/edit.phtml';
	protected $_id 		 = null;
	protected $_msg      = array();
	protected $_error    = null;
	
	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {
		$this->_id = isset($this->_request['variables'][0]) ? $this->_request['variables'][0] : null;
		$post 	   = array();

		$ministry = $this->_db->search()
			->setTable('ministry')
			->setColumns('*')
			->filterByMinistryId($this->_id)
			->getRow();

		if (empty($ministry)) {
			header('Location: /ministry');
			exit;
		}

		foreach ($ministry as $key => $value) {
			$post[$key] = $value;
		}
		
		$admin = $this->_db->search()
			->setTable('admin')
			->setColumns('*')
			->filterByAdminId($ministry['ministry_admin'])
			->getRow();

		foreach ($admin as $key => $value) {
			$post[$key] = $value;
		}

		if (isset($_POST['edit_ministry'])) {
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
			'post' => $post,
			'msg'  => isset($this->_msg) ? $this->_msg: array());

		return $this->_page();
	}
	
	/* Protected Methods
	-------------------------------*/
	protected function _setErrors($post) {
		foreach ($post as $field => $value) {
			if ($value == '' && $field != 'admin_password' && $field != 'admin_confirm') {
				$this->_error = self::ALL_FIELDS_REQUIRED;
				return false;
			}
		}
		
		if (strlen($post['admin_username']) < 5) { $this->_error = self::USERNAME_ERROR; return false; }
		return true;
	}

	protected function _addMinistry($post) {
		// add admin
		$adminSettings = array(
			'admin_name' 	 => $post['admin_name'],
			'admin_email'    => $post['admin_email'],
			'admin_username' => $post['admin_username'],
			'admin_updated'  => time());

		$adminfilter[] = array('admin_id=%s', $post['admin_id']);

		$this->_db->updateRows('admin', $adminSettings, $adminfilter);

		// add ministry
		$ministrySettings = array(
			'ministry_title' 		=> $post['ministry_title'],
			'ministry_description' 	=> $post['ministry_description'],
			'ministry_active' 		=> $post['ministry_active'],
			'ministry_updated' 		=> time());

		$ministryFilter[] = array('ministry_id=%s', $post['ministry_id']);

		$this->_db->updateRows('ministry', $ministrySettings, $ministryFilter);

		$_SESSION['msg'] = array(
			'type' => 'success',
			'msg'  => 'Ministry successfully updated.');

		header('Location: /ministry/edit/'.$this->_id);
		exit;
	}

	/* Private Methods
	-------------------------------*/
}
