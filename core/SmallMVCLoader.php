<?php
class SmallMVCLoader{
	function __construct(){
	}
	//auto create model
	//param1:Model file name without .php ext
	//param2:params pass to Model
	public function model($modelName, $params = array()){
		$modelNameEmpty = false;
		if(empty($modelName)){
			$modelNameEmpty = true;
		}else{
			$table = $modelName;
			$table = preg_replace("/(.*?)Model$/", "$1", $table);
		}
		(preg_match("/Model$/", $modelName) || $modelNameEmpty)? null : $modelName .= 'Model';
		if(!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_]+$/', $modelName) && !$modelNameEmpty){
			$e = new SmallMVCException("Model name '{$modelName}' is an invalid syntax", DEBUG);
			throw $e;
		}
		if(method_exists($this, $modelName)){
			$e = new SmallMVCException("Model name '{$modelName}' exists in SmallMVCLoader change another name plz.", DEBUG);
			throw $e;
		}
	
		//get controller object
		$controller = SMvc::instance(null, 'controller');
		if(isset($controller->$modelName))
			return $controller->$modelName;

		$fileName = $modelName . '.php';
		if(!$this->includeFile($fileName)){
			if(is_array($params) && !empty($params[0]))
				$poolName = $params[0];
			else
				$poolName = !empty($params) && is_string($params) ? $params : 'database';
			$modelName = SMvc::instance(null, 'default')->config[$poolName]['plugin'];
			$fileName = $modelName.'.php';
			$this->includeFile($fileName);
		}
		$refClass = new ReflectionClass($modelName);
		try{
			if(empty($params)) $params = array($table);
			else if(!is_array($params)) $params = array_merge(array($table), array($params));
			$modelInstance = $refClass->newInstanceArgs($params);
		}catch(ReflectionException $e){
			$e->type = DEBUG;
			throw $e;
		}
		if(!$modelNameEmpty)//store model if it is a exists model
			$controller->{$modelName} = $modelInstance;
		return $modelInstance;
	}
	//auto create library
	public function library($libName, $params = array()){
		$alias = $libName;
		if(empty($alias)){
			$e = new SmallMVCException("Library name cannot be empty", DEBUG);
			throw $e;
		}
		if(!preg_match('!^[@a-zA-Z]\.{0,1}[a-zA-Z_]+$!', $alias)){
			$e = new SmallMVCException("Library name '{$alias}' is an invalid syntax", DEBUG);
			throw $e;
		}
		if(method_exists($this, $alias)){
			$e = new SmallMVCException("Library name '{$alias}' is an invalid name", DEBUG);
			throw $e;
		}
		if($this->includeFile($libName)){
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
		}else{
			$e = new SmallMVCException("Library:'{$libName}' not found!", DEBUG);
			throw $e;
		}
	}
	//only load script but do not auto create
	public function script($scriptName){
		if(!preg_match('/^[0-9a-zA-Z@][a-zA-Z_.0-9]+$/', $scriptName)){
			$e = new SmallMVCException("Invalid script name '{$scriptName}'", DEBUG);
			throw $e;
		}
		return $this->includeFile($scriptName);
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
			$e = new SmallMVCException("fileName must be set", DEBUG);
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
