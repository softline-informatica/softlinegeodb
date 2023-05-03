<?php

/**
* MVC_View
*
* @package		softlineMVC
* @author		Juanga Covas - A hack of TinyMVC by	Monte Ohrt
*/

class MVC_Smarty_View {

	public $mvc_app_time = 0;
	private $CI = NULL;
	private $mvc = NULL;

	/**
	* class constructor
	*
	* @access	public
	*/
	function __construct() {
		
		$this->CI = mvc::instance(null, 'controller');
		$this->mvc = mvc::instance();
	}

	/**
	* assign
	*
	* assign view variable to smarty
	*/
	public function assign($key, $value=null) {
		
		if ($value)
			$this->CI->smarty->assign($key, $value);
		else		
			$this->CI->smarty->assign($key);		
	}

	/**
	* display
	*
	* echo the smarty template
	*
	*/
	public function display($template, $tpl_vars_arr = array()) {
		
		if (! isset($this->CI->smarty)) {
			throw new Exception("Smarty template engine instance is not available");			
		}

		if ($tpl_vars_arr) {
			$this->CI->smarty->assign($tpl_vars_arr);
		}

		if ($this->mvc->config['timer']) {

			$this->_timer($template); 

			//$smarty_time = $this->mvc->timer('mvc_smarty_start','mvc_smarty_end');			
			//return;
		}
		
		$this->CI->smarty->display($template);
	}

	public function fetch($template, $tpl_vars_arr = array()) 
	{
		if (! isset($this->CI->smarty)) {
			throw new Exception("Smarty template engine instance is not available");			
		}
		if ($tpl_vars_arr) {
			$this->CI->smarty->assign($tpl_vars_arr);
		}

		return $this->CI->smarty->fetch($template);
	}
		
	private function _timer($template) {
		
		$this->mvc->timer('mvc_app_end');
		//mvc::instance()->timer('mvc_smarty_start');
		$this->mvc_app_time = sprintf('%0.5f', $this->mvc->timer('mvc_app_start','mvc_app_end'));
		$this->assign('mvc_app_time', $this->mvc_app_time);
		//$template_c = $this->fetch($template);
		//$this->mvc->timer('mvc_smarty_end');
		//echo $template_c;		
	}
}
