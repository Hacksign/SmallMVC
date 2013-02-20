<?php
	session_start();
if(!defined('DS'))
	define('DS', DIRECTORY_SEPARATOR);
if(!defined('PS'))
	define('PS', PATH_SEPARATOR);
if(!defined('SMVC_VERSION'))
	define('SMVC_VERSION', '0.6.2');
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
define('APP_INCLUDE_PATH',
 	$_SERVER['DOCUMENT_ROOT'].WEB_ROOT.DS.APPDIR.DS.'config'.PS.
	$_SERVER['DOCUMENT_ROOT'].WEB_ROOT.DS.APPDIR.DS.'controller'.PS.
	$_SERVER['DOCUMENT_ROOT'].WEB_ROOT.DS.APPDIR.DS.'model'.PS.
	$_SERVER['DOCUMENT_ROOT'].WEB_ROOT.DS.APPDIR.DS.'view'.PS .
	$_SERVER['DOCUMENT_ROOT'].WEB_ROOT.DS.APPDIR.DS.'cache'.PS .
	$_SERVER['DOCUMENT_ROOT'].WEB_ROOT.DS.APPDIR.DS.'plugins'.PS
);

set_include_path(
	get_include_path() . PS .
	SMVC_CONFIGDIR . PS .
	SMVC_COREDIR . PS .
	SMVC_PLUGINDIR . PS
);
 class SMvc{
	var $config = null;
	var $controller = null;
	var $loader = null;
	var $urlSegments = null;
	var $dbs = null;
	
	public function __construct($id = 'default'){
		self::instance($this, $id);
		$_SESSION['__prevent_template_view_directly_'] = true;
	}
	function __destruct(){
		if(isset($_SESSION)){
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
		$this->setupErrorHandling();
		if(file_exists(SMVC_COREDIR . DS . 'SmallMVCLoader.php')){
			require_once('SmallMVCLoader.php');
			$this->loader = new SmallMVCLoader;
			Smvc::instance($this->loader, 'loader');
			if(file_exists(SMVC_CONFIGDIR . DS . 'config.php')){
				require_once(SMVC_CONFIGDIR . 'config.php');	
			}
			if(file_exists(APPDIR . DS . 'config'. DS .'config.php')){
				require_once(APPDIR . DS . 'config'. DS .'config.php');	
			}
			$this->config = $config;
			$this->loadCoreFiles();
			$this->setupUrlSegments();
			$this->setupController();
			$this->setupAutoloaders();
			$this->controller->{$this->urlSegments[2]}();
		}else{
			throw new Exception("SmallMVCLoader can't be loaded");
		}
	}
	private function setupErrorHandling(){
		if(defined('SMVC_ERROR_HANDLING') && SMVC_ERROR_HANDLING == 1){
		  error_reporting(E_ALL);
			if(file_exists(SMVC_COREDIR . 'SmallMVCErrorHandler.php')){
				require_once(SMVC_COREDIR . 'SmallMVCErrorHandler.php');  		  
				set_exception_handler(array('SmallMVCExceptionHandler','handleException'));
				set_error_handler('SmallMVCErrorHandler');
				register_shutdown_function('SmallMVCShutdownFunction');
			}else{
				echo 'can not find error handler file!';
				exit(0);
			}
		}
	}
	private function loadCoreFiles(){
		$this->loader->script('SmallMVCController');
	}
	private function setupUrlSegments(){
		$url = !empty($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '/'.$this->config['default_controller'].'/'.$this->config['default_action'];
		$this->urlSegments = explode('/', $url);
		if(!empty($this->urlSegments)){
			if(isset($this->urlSegments[0])){
				unset($this->urlSegments[0]);
			}
			if(!empty($this->urlSegments[1]) && !preg_match('/(^[a-zA-Z][a-zA-Z0-9_]*)Controller$/i', $this->urlSegments[1])){
				$this->urlSegments[1] = preg_replace('/(^[a-zA-Z][a-zA-Z0-9_]*)/i', "$1Controller", ucfirst(strtolower($this->urlSegments[1])));
			}else if(empty($this->urlSegments[1])){
				$this->urlSegments[1] = $this->config['routing']['default_controller'];
			}
			empty($this->urlSegments[2]) ? $this->urlSegments[2] = $this->config['routing']['default_action'] : null;
			foreach($this->urlSegments as $value => $key){
				if($value % 2 == 0 && $value != 0)
					$_GET[$this->urlSegments[$value - 1]] = $key;
				else
					$_GET[$key] = null;
			}
		}else
			$this->urlSegments = array(1 => $this->config['routing']['default_controller'], 2 => $this->config['routing']['default_action']);
	}
	private function setupController(){
		$controllerName = !empty($this->urlSegments[1]) ? preg_replace('/\W/', '', $this->urlSegments[1]) : $this->config['default_controller'];
		$this->controller = $this->loader->library($controllerName);
		Smvc::instance($this->controller,'controller');
	}
	private function setupAutoloaders(){
		if(!empty($this->config['autoloads']['scripts'])){
		  foreach($this->config['autoloads']['scripts'] as $script)
				$this->controller->load->script($script);
		}
		if(!empty($this->config['autoloads']['models'])){
			foreach($this->config['autoloads']['models'] as $modName => $params){
				if(!empty($params) && is_array($params)){
					$this->controller->load->model($modName,$params);
				}else if(is_int($modName))
					$this->controller->load->model($params);
			}//end foreach
		}//end if($models)
		if(!empty($this->config['autoloads']['libraries'])){
			foreach($this->config['autoloads']['libraries'] as $libName => $params){
				if(!empty($params) && is_array($params)){
					$lib = $this->controller->load->library($libName,$params);
				}else if(is_int($libName))
					$lib = $this->controller->load->library($params);

				if($lib)
					SMvc::instance($lib, $library[0]);
			}//end foreach
		}//end if($libraries)
	}
 }
 $app = new SMvc();
 $app->run();
?>
