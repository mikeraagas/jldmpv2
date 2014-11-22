<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Control_Page_Login extends Control_Page {
	/* Constants
	-------------------------------*/
	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title    = 'JLDMP - Control';
	protected $_class    = 'login';
	protected $_template = '/login.phtml';

	protected $_collection = null;
	protected $_msg   	   = array();
	
	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {
		$this->_collection = new MongoCollection($this->_db, 'admin');

		if (isset($_POST['login'])) {
			$this->_login();
		}

		if(isset($_SESSION['msg']) && !empty($_SESSION['msg'])) {
			$this->_msg = $_SESSION['msg'];
			unset($_SESSION['msg']);		
		}

		$this->_body = array(
			'msgs' => $this->_msg);

		return $this->_page();
	}
	
	/* Protected Methods
	-------------------------------*/
	protected function _login() {
		$username = $_POST['username'];
		$password = $_POST['password'];

		$filter = array(
			'admin_username' => $username,
			'admin_password' => md5($password));

		$admin = $this->_collection->findOne($filter);

		if (empty($admin)) {
			$_SESSION['msg'][] = array(
				'type' => 'danger',
				'msg'  => 'Incorrect username or password');

			header('Location: /login');
			exit;
		}

		$loginSession = array(
			'admin_id' 			=> $admin['_id']->{'$id'},
			'admin_name' 		=> $admin['admin_name'],
			'admin_username' 	=> $admin['admin_username'],
			'admin_email' 		=> $admin['admin_email']);

		$_SESSION['admin'] = $loginSession;

		header('Location: /index');	
		exit;
	}

	/* Private Methods
	-------------------------------*/
}
