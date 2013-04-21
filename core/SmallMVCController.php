<?php
class SmallMVCController{
	private $load;
	private $view;
	private $config;

	function __construct(){
		$this->config = SMvc::instance(null,'default')->config; 
		if(!empty($this->config['system']['viewer'][0])) $viewerName = $this->config['system']['viewer'][0];
		else{
			$e = new SmallMVCException("\$config['system']['viewer'] must be set! read manual for help!", DEBUG);
			throw $e;
		}
		if(empty($this->config['system']['viewer'][1])) $this->config['system']['viewer'][1] = array();
		$this->load = SMvc::instance(null, 'loader');
		$this->view = $this->load->library($this->config['system']['viewer'][0], $this->config['system']['viewer'][1]);
	}
	protected function __call($name, $args = null){
		try{
			//parse viewer method first to prevent dead loop
			$refClass = new ReflectionClass($this->view);
			if(($rMethod = $refClass->getMethod($name))){
				if(empty($args)) return $rMethod->invoke($this->view); else return $rMethod->invokeArgs($this->view, $args);
			}else{
				$e = new SmallMVCException("can not find method:$name", DEBUG);
				throw $e;
			}
		}catch(ReflectionException $e){
			//parse controller method, for short name of url address
			if(get_class(SMvc::instance(null,'controller')) === $this->config['system']['controller']){
				$controller = $this->load->library($this->config['routing']['controller']);
				SMvc::instance($controller, 'controller');
			}else{
				$controller = SMvc::instance(null,'controller');
			}
			foreach(get_class_methods($controller) as $actionName){
				if(strcasecmp($actionName, $name) === 0){
					return $controller->{$actionName}();
				}
			}//end foreach
			$e = new SmallMVCException("action:{$name} dosen't exists in controller", DEBUG);
			throw $e;
		}//end catch
	}//end function _call
	protected function redirect($url, $time = 0, $msg = ''){
		if(preg_match('#^(http)[s]*://#', $url))
			redirect($url, $time, $msg);
		else
			redirect(SMVC_ENTRYSCRIPT . DS . $url, $time, $msg);
	}
}
?>
