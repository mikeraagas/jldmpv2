<?php //-->
/*
 * This file is part a custom application package.
 */

/**
 * Default logic to output a page
 */
class Control_Page_Ministry_News extends Control_Page {
	/* Constants
	-------------------------------*/
	const RANGE = 10;

	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_title    = 'JLDMP - Control';
	protected $_class    = 'ministry';
	protected $_template = '/ministry/news.phtml';

	protected $_ministry 	= null;
	protected $_var 	 	= null;
	protected $_newsPath    = null;
	protected $_msg			= array();
	
	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	public function render() {
		$this->_ministry = isset($this->_request['variables'][0]) ? $this->_request['variables'][0] : null;
		$this->_var 	 = isset($this->_request['variables'][1]) ? $this->_request['variables'][1] : null;
		
		if (isset($_GET['action']) && $_GET['action'] == 'remove_news') $this->_removeNews($_GET['id']);

		// set pagination
		$pg    = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
		$count = $this->_countNews();
		$pages = ceil($count/self::RANGE);

		$news = $this->_getNews();
		$news = $this->_getNewsImage($news);

		$this->_renderMsg();

		$this->_body = array(
			'class'       => 'news',
			'var'         => $this->_var,
			'msg'         => $this->_msg,
			'ministry_id' => $this->_ministry,
			'pg'          => $pg,
			'pages'       => $pages,
			'news'        => $news);

		return $this->_page();
	}
	
	/* Protected Methods
	-------------------------------*/
	protected function _getNews() {
		$pg 	= isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
		$start 	= ($pg-1) * self::RANGE;
		$filter = $this->_setFilter();

		$query = $this->_collection['news']->find($filter)
			->skip($start)
			->limit(self::RANGE)
			->sort(array('news_updated' => -1));

		$events = iterator_to_array($query);
		return $events;
	}

	protected function _setFilter() {
		$filter = array('news_ministry' => $this->_ministry);

		switch ($this->_var) {
			case 'active':		$filter['news_active'] = 1; break;
			case 'not-active':	$filter['news_active'] = 0; break;
		}

		if (isset($_GET['q'])) { $filter['news_title'] = $_GET['q']; }		
		return $filter;
	}

	protected function _countNews() {
		$filter = $this->_setFilter();
		$query  = $this->_collection['news']->find($filter);
		$count  = $query->count();

		return $count;
	}

	protected function _getNewsImage($news) {
		foreach ($news as $key => $item) {
			$filter = array(
				'file_parent'  => $item['_id']->{'$id'},
				'file_active'  => 1,
				'file_primary' => 1,
				'file_type'    => 'news');

			$image = $this->_collection['file']->findOne($filter);
			$news[$key]['news_image'] = $image;
		}

		return $news;
	}

	protected function _removeNews($id) {
		$this->_newsPath = $this->_uploads.'/news';

		// remove images
		$filter = array(
			'file_parent' => $id,
			'file_active' => 1,
			'file_type'   => 'news');

		$images = $this->_collection['file']->find($filter);

		if (!empty($images)) {
			foreach ($images as $image) {
				unlink($this->_newsPath.$image['file_name']);
			}

			$imageFilter = array(
				'file_parent' => $id,
				'file_active' => 1,
				'file_type'   => 'news');

			$this->_collection['file']->remove($imageFilter, array('justOne' => false));
		}

		$newsFilter = array('_id' => new MongoId($id));
		$this->_collection['news']->remove($newsFilter, array('justOne' => true));

		header('Location: /ministry/'.$this->_ministry.'/news');
		exit;
	}

	/* Private Methods
	-------------------------------*/
}
