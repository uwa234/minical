<?php
class Template {
	//ci instance
	private $CI;
	//template Data
	var $template_data = array();

	public function __construct() 
	{
		$this->CI =& get_instance();
	}

	function set($content_area, $value)
	{
		$this->template_data[$content_area] = $value;
	}

	function load($template = '', $name ='', $view = '' , $view_data = array(), $return = FALSE)
	{
		$view_data['main_content'] = $view;

		return $this->CI->load->view('includes/'.$template, $view_data, $return);
	}
                               
}
?>