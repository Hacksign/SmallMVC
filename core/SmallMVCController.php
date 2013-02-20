<?php
class SmallMVCController{
	public $load;
	private $viewReflection;
	private $view;
	private $config;

	function __construct(){
		$this->config = SMvc::instance(null,'default')->config; 
		$viewerName = $this->config['default_viewer'];
		$this->load = SMvc::instance(null, 'loader');
		$this->load->script($viewerName);
		$this->viewReflection = new ReflectionClass($viewerName);
		$this->view = $this->viewReflection->newInstanceArgs($this->config['default_viewer_args']);
	}
	function __call($name, $args = null){
		try{
			//parse viewer method first to prevent dead loop
			if(($rMethod = $this->viewReflection->getMethod($name))){
				empty($args) ? $rMethod->invoke($this->view) : $rMethod->invokeArgs($this->view, $args);
			}else{
				$e = new SmallMVCException("can not find method:$name", DEBUG);
				throw $e;
			}
		}catch(ReflectionException $e){
			//parse controller method, for short name of url address
			if(get_class(SMvc::instance(null,'controller')) === $this->config['default_controller']){
				$controller = $this->load->library($this->config['routing']['default_controller']);
				SMvc::instance($controller, 'controller');
			}else{
				$controller = SMvc::instance(null,'controller');
			}
			foreach(get_class_methods($controller) as $actionName){
				if(strcasecmp($actionName, $name) === 0){
					$controller->{$actionName}();
					return true;
				}
			}//end foreach
			$e = new SmallMVCException("action:{$name} dosen't exists in controller", DEBUG);
			throw $e;
		}//end catch
	}//end function _call
	protected function redirect($url, $time = 0, $msg = ''){
		redirect(SMVC_ENTRYSCRIPT . DS . $url, $time, $msg);
	}
	protected function getInstance($instName = null){
		if(empty($instName)){
				$e = new SmallMVCException("Instance Name is required", DEBUG);
				throw $e;
		}
		return SMvc::instance(null, $instName);
	}
}
?>
