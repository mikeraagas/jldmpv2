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
	protected $_msg   	 = array();
	
	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {
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

		$adminInfo = $this->_db->search('admin')
			->setColumns('*')
			->filterByAdminUsername($username)
			->filterByAdminPassword(md5($password))
			->getRow();

		if (empty($adminInfo)) {
			$_SESSION['msg'] = array(
				'type' => 'danger',
				'msg' => 'Incorrect username or password');

			header('Location: /login');
			exit;
		}

		$loginSession = array(
			'admin_id' 			=> $adminInfo['admin_id'],
			'admin_name' 		=> $adminInfo['admin_name'],
			'admin_username' 	=> $adminInfo['admin_username'],
			'admin_email' 		=> $adminInfo['admin_email']);

		$_SESSION['admin'] = $loginSession;

		header('Location: /index');	
		exit;
	}

	/* Private Methods
	-------------------------------*/
}
