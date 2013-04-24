<?php
	session_start();
if(!defined('DS'))
	define('DS', DIRECTORY_SEPARATOR);
if(!defined('PS'))
	define('PS', PATH_SEPARATOR);
if(!defined('SMVC_VERSION'))
	define('SMVC_VERSION', '0.8.6');
if(!defined('SMVC_BASEDIR'))
	define('SMVC_BASEDIR', dirname(__FILE__). DS);
if(!defined('SMVC_CONFIGDIR'))
	define('SMVC_CONFIGDIR', SMVC_BASEDIR . 'config' . DS);
if(!defined('SMVC_COREDIR'))
	define('SMVC_COREDIR', SMVC_BASEDIR . 'core' . DS);
if(!defined('SMVC_PLUGINDIR'))
	define('SMVC_PLUGINDIR', SMVC_BASEDIR . 'plugins' . DS);
define('SMVC_ERROR_HANDLING', 1);
if(!defined('APPDIR')){
	echo "APPDIR must be defined!";
	exit(0);
}
if(!defined('WEB_ROOT') && !empty($_SERVER['SCRIPT_NAME'])){
	define('SMVC_ENTRYSCRIPT', $_SERVER['SCRIPT_NAME']);
	define('WEB_ROOT', dirname($_SERVER['SCRIPT_NAME']));
}

set_include_path(
	get_include_path() . PS .
	SMVC_CONFIGDIR . PS .
	SMVC_COREDIR . PS .
	SMVC_PLUGINDIR . PS
);
 class SMvc{
	var $config = null;
	var $controller = null;
	var $load = null;
	var $urlSegments = null;
	var $dbs = null;
	static $scriptExecComplete = null;
	
	public function __construct($id = 'default'){
		self::$scriptExecComplete = false;
		self::instance($this, $id);
		$_SESSION['__prevent_template_view_directly_'] = true;
	}
	function __destruct(){
		self::$scriptExecComplete = true;
		if(isset($_SESSION)){
			if(count($_SESSION) === 1 && !empty($_SESSION['__prevent_template_view_directly_']))
				session_destroy();
		}
	}

	public static function &instance($newInstance=null,$id='default'){
		static $instance = array();
		if(isset($newInstance) && is_object($newInstance))
			$instance[$id] = $newInstance;
		return $instance[$id];
	}
	public function run(){
		if(file_exists(SMVC_CONFIGDIR . DS . 'config.php')){
			require_once(SMVC_CONFIGDIR . 'config.php');	
		}
		if(file_exists(APPDIR . DS . 'config'. DS .'config.php')){
			require_once(APPDIR . DS . 'config'. DS .'config.php');	
		}
		$this->config = $config;
		if(!preg_match('/^[a-z0-9].*\.php$/i',$this->config['system']['loader']))
			$this->config['system']['loader'] .= '.php';
		if(file_exists(SMVC_COREDIR . DS . $this->config['system']['loader'])){
			require_once($this->config['system']['loader']);
			$this->load = new SmallMVCLoader;
			Smvc::instance($this->load, 'loader');
			$this->setupErrorHandling();
			$this->setupAutoloaders();
			$this->setupUrlSegments();
			$this->setupController();
			$this->controller->{$this->urlSegments[2]}();
		}else{
			throw new Exception("SmallMVCLoader can't be loaded");
		}
	}
	private function setupErrorHandling(){
		if(defined('SMVC_ERROR_HANDLING') && SMVC_ERROR_HANDLING == 1){
		  error_reporting(E_ALL);
			if(!preg_match('/^[a-z0-9].*\.php$/i',$this->config['system']['error']['file']))
				$this->config['system']['error']['file'] .= '.php';
			if(file_exists(SMVC_COREDIR . $this->config['system']['error']['file'])){
				require_once(SMVC_COREDIR . $this->config['system']['error']['file']);  		  
				set_exception_handler($this->config['system']['error']['class']);
				set_error_handler($this->config['system']['error']['function']);
				register_shutdown_function($this->config['system']['error']['shutdown']);
			}else{
				echo 'can not find error handler file!';
				exit(0);
			}
		}
	}
	private function setupUrlSegments(){
		$url = !empty($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '/'.$this->config['system']['controller'].'/'.(!empty($this->config['routing']['action']) ? $this->config['routing']['action'] : $this->config['system']['action']);
		$this->urlSegments = explode('/', $url);
		if(!empty($this->urlSegments)){
			if(isset($this->urlSegments[0])){
				unset($this->urlSegments[0]);
			}
			if(!empty($this->urlSegments[1]) && !preg_match('/(^[a-zA-Z][a-zA-Z0-9_]*)Controller$/i', $this->urlSegments[1])){
				$this->urlSegments[1] = preg_replace('/(^[a-zA-Z][a-zA-Z0-9_]*)/i', "$1Controller", ucfirst(strtolower($this->urlSegments[1])));
			}else if(empty($this->urlSegments[1])){
				$this->urlSegments[1] = $this->config['routing']['controller'];
			}
			empty($this->urlSegments[2]) ? $this->urlSegments[2] = $this->config['routing']['action'] : null;
			foreach($this->urlSegments as $value => $key){
				if($value % 2 == 0 && $value != 0)
					$_GET[$this->urlSegments[$value - 1]] = $key;
				else
					$_GET[$key] = null;
			}
		}else
			$this->urlSegments = array(1 => $this->config['routing']['controller'], 2 => $this->config['routing']['action']);
	}
	private function setupController(){
		$controllerName = !empty($this->urlSegments[1]) ? preg_replace('/\W/', '', $this->urlSegments[1]) : $this->config['system']['controller'];
		$this->controller = $this->load->library($controllerName);
		Smvc::instance($this->controller,'controller');
	}
	private function setupAutoloaders(){
		if(!empty($this->config['autoloads']['scripts'])){
		  foreach($this->config['autoloads']['scripts'] as $script)
				$this->load->script($script);
		}
		if(!empty($this->config['autoloads']['models'])){
			foreach($this->config['autoloads']['models'] as $modName => $params){
				if(!empty($params) && is_array($params)){
					$this->load->model($modName,$params);
				}else if(is_int($modName))
					$this->load->model($params);
			}//end foreach
		}//end if($models)
		if(!empty($this->config['autoloads']['libraries'])){
			foreach($this->config['autoloads']['libraries'] as $libName => $params){
				if(!empty($params) && is_array($params)){
					$lib = $this->load->library($libName,$params);
				}else if(is_int($libName))
					$lib = $this->load->library($params);

				if($lib)
					SMvc::instance($lib, $libName);
			}//end foreach
		}//end if($libraries)
	}
 }
 $app = new SMvc();
 $app->run();
?>
