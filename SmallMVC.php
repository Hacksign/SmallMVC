<?php
if(!defined('DS'))
	define('DS', DIRECTORY_SEPARATOR);
if(!defined('PS'))
	define('PS', PATH_SEPARATOR);
if(!defined('SMVC_VERSION'))
	define('SMVC_VERSION', '0.9.6');
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
if(!defined('PROJECT_ROOT') && !empty($_SERVER['SCRIPT_NAME'])){
	define('PROJECT_ENTRYSCRIPT', $_SERVER['SCRIPT_NAME']);
	define('PROJECT_ROOT', $_SERVER['DOCUMENT_ROOT'] . DS . dirname($_SERVER['SCRIPT_NAME']));
	define('PROJECT_DIR', preg_replace('/^\/(.*)/', '$1', dirname($_SERVER['SCRIPT_NAME'])));
	define('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);
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
	}
	function __destruct(){
		self::$scriptExecComplete = true;
	}

	public static function &instance($newInstance=null,$id='default'){
		static $instance = array();
		if(isset($newInstance) && is_object($newInstance))
			$instance[$id] = $newInstance;
		return $instance[$id];
	}
	public function run(){
		//加载全局配置文件
		if(file_exists(SMVC_CONFIGDIR . DS . 'config.php')){
			require_once(SMVC_CONFIGDIR . 'config.php');	
			//加载项目配置文件,项目配置文件内容会覆盖全局配置文件
			if(file_exists(PROJECT_ROOT . DS . APPDIR . DS . 'config'. DS .'config.php')){
				require_once(PROJECT_ROOT . DS . APPDIR . DS . 'config'. DS .'config.php');	
			}
			$this->config = $config;
			if(!preg_match('/^[a-z0-9].*\.php$/i',$this->config['system']['loader'])) $this->config['system']['loader'] .= '.php';
			if(file_exists(SMVC_COREDIR . DS . $this->config['system']['loader'])){
				//工具类Loader,用于加载框架各种文件
				require_once($this->config['system']['loader']);
				$this->load = new SmallMVCLoader;
				Smvc::instance($this->load, 'loader');
				//初始化错误处理
				$this->setupErrorHandling();
				//初始化框架utils,以及项目指定的自动加载脚本
				$this->setupAutoloaders();
				//初始化URL解析类
				$this->setupController();
				$this->controller->{$this->urlSegments[2]}();
			}else{
				throw new Exception(SMVC_COREDIR . DS . $this->config['system']['loader']." not found!");
			}
		}else{
				throw new Exception(SMVC_CONFIGDIR.DS."config.php not found!");
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

				if($lib) SMvc::instance($lib, $libName);
			}//end foreach
		}//end if($libraries)
	}
	private function setupController(){
		$controllerName = !empty($this->urlSegments[1]) ? preg_replace('/\W/', '', $this->urlSegments[1]) : $this->config['system']['controller'];
		$this->controller = $this->load->library($controllerName);
		Smvc::instance($this->controller,'controller');
	}
 }
 $app = new SMvc();
 $app->run();
?>
