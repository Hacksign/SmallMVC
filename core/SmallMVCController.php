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
	public function __call($name, $args = null){
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
				try{
					$controller = $this->load->library($this->config['routing']['controller']);
				}catch(SmallMVCException $se){
					//if Default controller doesn't exists in config.php display default page
					$controller = $this->load->library($this->config['system']['controller']);
					SMvc::instance($controller, 'controller');
				}
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
		if(preg_match('#^(http)[s]*://#', $url)) redirect($url, $time, $msg);
		else if(preg_match('#^[a-z]#i', $url)) redirect(PROJECT_ENTRYSCRIPT . DS . $url, $time, $msg);
		else redirect(PROJECT_ENTRYSCRIPT . $url, $time, $msg);
	}
	public function index(){
		try{
			$controller = $this->load->library($this->config['routing']['controller']);
			if(in_array('index', get_class_methods($controller))){
				SMvc::instance(null, 'default')->urlSegments[1] = get_class($controller);
				SMvc::instance(null, 'default')->urlSegments[2] = 'index';
				$controller->redirect(U('Index/index'));
			}
			else{throw new SmallMVCException("Dispaly help page", DEBUG);}
		}catch(SmallMVCException $e){
			$this->assign('_SMVC_VERSION_', SMVC_VERSION);
			$this->display('#.welcome');
		}
	}
}
?>
