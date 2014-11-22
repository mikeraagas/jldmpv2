<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Control_Page_Ministry_News_View extends Control_Page {
	/* Constants
	-------------------------------*/
	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title    = 'JLDMP - Control';
	protected $_class    = 'ministry';
	protected $_template = '/ministry/news/view.phtml';

	protected $_ministry = null;
	protected $_newsPath = null;
	protected $_msg      = array();

	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {
		$this->_ministry = isset($this->_request['variables'][0]) ? $this->_request['variables'][0] : null;
		$this->_id       = isset($this->_request['variables'][1]) ? $this->_request['variables'][1] : null;

		// if not isset ministry or id
		if (!$this->_ministry || !$this->_id) { header('Location: /ministry/'.$this->_ministry.'/news'); exit; }

		$news = $this->_getNews();
		$this->_renderMsg();

		$this->_body = array(
			'class'			=> 'news',
			'msgs' 			=> $this->_msg,
			'ministry_id' 	=> $this->_ministry,
			'news' 	   		=> $news);
		
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

	/* Private Methods
	-------------------------------*/
}
