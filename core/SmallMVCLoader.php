<?php
class SmallMVCLoader{
	function __construct(){
	}
	//auto create model
	//param1:Model file name without .php ext
	//param2:params pass to Model
	public function model($modelName, $param = array()){
		if(empty($modelName)){
			$e = new Exception("Model name cannot be empty");
			$e->type = DEBUG;
			throw $e;
		}
		if(!preg_match('!^[a-zA-Z][a-zA-Z0-9_]+$!', $modelName)){
			$e = new Exception("Model name '{$modelName}' is an invalid syntax");
			$e->type = DEBUG;
			throw $e;
		}
		if(method_exists($this, $modelName)){
			$e = new Exception("Model name '{$modelName}' is an invalid name");
			$e->type = DEBUG;
			throw $e;
		}
	
		//get controller object
		$controller = SMvc::instance(null, 'controller');
		if(isset($controller->$modelName))
			return $controller->$modelName;

		$fileName = $modelName . '.php';
		try{
			if(!file_exists(APPDIR.DS.'model'.DS.$fileName)){
				$aliasName = $modelName;
				$modelName = 'SmallMVCModel';
				$fileName = $modelName . '.php';
				$this->includeFile($fileName);
			}else{
				$aliasName = $modelName;
				$this->includeFile($fileName);
			}
		}catch(Exception $e){
			$e = new Exception("Unknow file '{$fileName}'");
			$e->type = DEBUG;
			throw $e;
		}
		$table = preg_replace("/(.*?)Model$/", "$1", $aliasName);
		$refClass = new ReflectionClass($modelName);
		try{
			if(!is_array($params))
				$params = array_merge($table, array($params));
			$controller->{$aliasName} = $refClass->newInstanceArgs($params);
		}catch(ReflectionException $e){
			$e->type = DEBUG;
			throw $e;
		}
		$controller->{$aliasName} = new $modelName($table, $param);
		return $controller->$aliasName;
	}
	//auto create library
	public function library($libName, $params = array()){
		$alias = $libName;
		if(empty($alias)){
			$e = new Exception("Library name cannot be empty");
			$e->type = DEBUG;
			throw $e;
		}
		if(!preg_match('!^[@a-zA-Z]\.{0,1}[a-zA-Z_]+$!', $alias)){
			$e = new Exception("Library name '{$alias}' is an invalid syntax");
			$e->type = DEBUG;
			throw $e;
		}
		if(method_exists($this, $alias)){
			$e = new Exception("Library name '{$alias}' is an invalid name");
			$e->type = DEBUG;
			throw $e;
		}
			
		$this->includeFile($libName);
		if(preg_match('/^@\./', $libName)){
			$libName = preg_replace('/^@\.(.*)/', '$1', $libName);
		}
		$refClass = new ReflectionClass($libName);
		try{
			if(!is_array($params))
				$params = array($params);
			return $refClass->newInstanceArgs($params);
		}catch(ReflectionException $e){
			$e->type = DEBUG;
			throw $e;
		}
	}
	//only load script but do not auto create
	public function script($scriptName){
		if(!preg_match('/^[0-9a-zA-Z@][a-zA-Z_.0-9]+$/', $scriptName)){
			$e = new Exception("Invalid script name '{$scriptName}'");
			$e->type = DEBUG;
			throw $e;
		}
		return $this->includeFile($scriptName);
	}
	//auto create db object
	public function database($poolName = null, $table = null){
		$config = SMvc::instance(null, 'default')->config;
		if(!$poolName)
			$poolName = isset($config['default_pool']) ? $config['default_pool'] : 'default';
		if($poolName && isset(SMvc::instance(null, 'default')->dbs[$poolName])){
			return SMvc::instance(null, 'default')->dbs[$poolName];
		}
		if($poolName && isset($config[$poolName]) && !empty($config[$poolName]['plugin'])){
			try{
				$fileName = $config[$poolName]['plugin'] . '.php';
				$this->includeFile($fileName);
			}catch(Exception $e){
				$e = new Exception("Cannot find '{$fileName}'");
				$e->type = DEBUG;
				throw $e;
			}
			$modelClass = new ReflectionClass($config[$poolName]['plugin']);
			SMvc::instance(null, 'default')->dbs[$poolName] = $modelClass->newInstanceArgs(array($config[$poolName]));	
			return SMvc::instance(null, 'default')->dbs[$poolName];
		}
		return null;
	}
	private function fileExists($fileName = null){
		/*check errors and prepare data*/
		if(!isset($fileName) || empty($fileName))
			return false;
		if(!preg_match('/\.php$/', $fileName))
			$fileName .= '.php';
		/**********/
		$appPath = APPDIR;
		$ps = explode(PS, get_include_path().PS.APP_INCLUDE_PATH);
		foreach($ps as $path){
			if(preg_match('/^@\./', $fileName) && preg_match("/^$appPath/", $path)){
				$testPath = $path. DS . preg_replace('/^@\.(.*)/', "$1", $fileName);
			}else{
				$testPath = $path. DS .$fileName;
			}

			if(file_exists($testPath)) return true;
			else unset($testPath);
		}
		return false;
	}
	private function includeFile($fileName = null){
		if(!isset($fileName) || empty($fileName)){
			$e = new Exception("fileName must be set");
			$e->type = DEBUG;
			throw $e;
		}
		if(!preg_match('/\.php$/', $fileName))
			$fileName .= '.php';
		if(preg_match('/^@\./', $fileName)){
			$fileName = preg_replace('/^@\.(.*)/', "$1", $fileName);
			$includePath = APP_INCLUDE_PATH;
		}else{
			$includePath = APP_INCLUDE_PATH . get_include_path();
		}
		$subPath = explode('.', $fileName);
		$fileName = implode('.', array_slice($subPath, -2, 2));
		array_splice($subPath, -2, 2);
		!empty($subPath) ? $fileName = implode(DS, $subPath).DS.$fileName : null;
		$ps = explode(PS, $includePath);
		foreach($ps as $path){
			if(file_exists($path . DS . $fileName)){
				require_once($path . DS . $fileName);
				return true;
			}
		}
		return false;
	}
}
?>
