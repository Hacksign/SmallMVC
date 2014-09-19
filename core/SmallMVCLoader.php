<?php
class SmallMVCLoader{
	function __construct(){
	}
	//auto create model
	//param1:Model file name without .php ext
	//param2:PoolName which database will be used
	//param3:be geted through func_get_args(), params pass to model
	public function model($modelName, $poolName = null){
		$params_list = func_get_args();
		//remove modelName and poolName
		empty($params_list)? null : array_shift($params_list);
		empty($params_list)? null : array_shift($params_list);
		//end of remove
		$modelNameEmpty = false;
		if(empty($modelName)){
			$modelNameEmpty = true;
			$table = null;
		}else{
			$table = $modelName;
			$table = preg_replace("/(.*?)Model$/", "$1", $table);
		}
		$poolName = empty($poolName) ? 'database' : $poolName;
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
		if(!empty($controller) && isset($controller->$modelName))
			return $controller->$modelName;

		$fileName = $modelName . '.php';
		if(!$this->includeFile($fileName)){
			$modelName = SMvc::instance(null, 'default')->config['system']['model'];
			$fileName = $modelName.'.php';
			$this->includeFile($fileName);
			$modelName = preg_replace('/(.*)\.php$/', '$1', $fileName);
		}
		$refClass = new ReflectionClass($modelName);
		try{
			$params_list = array_shift($params_list);
			array_unshift($params_list, $poolName);
			array_unshift($params_list, $table);
			$modelInstance = $refClass->newInstanceArgs($params_list);
		}catch(ReflectionException $refExp){
			$e = new SmallMVCException($refExp->__toString(), DEBUG);
			throw $e;
		}
		if(!$modelNameEmpty && !empty($controller))//store model if it is a exists model
			$controller->{$modelName} = $modelInstance;
		return $modelInstance;
	}
	//auto create library
	//params1: libraray name
	//params2: params pass to library
	//params...
	public function library(&$libName){
		$params_list = func_get_args();
		//remove $libName
		empty($libName) ? null : array_shift($params_list);
		$alias = $libName;
		if(empty($alias)){
			$e = new SmallMVCException("Library name cannot be empty", DEBUG);
			throw $e;
		}
		if(!preg_match('!^[@a-zA-Z]\.{0,1}[a-zA-Z_.]+$!', $alias)){
			$e = new SmallMVCException("Library name '{$alias}' is an invalid syntax", DEBUG);
			throw $e;
		}
		if(method_exists($this, $alias)){
			$e = new SmallMVCException("Library name '{$alias}' is an invalid name", DEBUG);
			throw $e;
		}
		if($this->includeFile($libName)){
			$refClass = new ReflectionClass($libName);
			try{
				return $refClass->newInstanceArgs($params_list);
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
	public function script(&$scriptName){
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
		$appPath = APPDIR;
		$ps = explode(PS, get_include_path());
		$ps = array_merge($ps, SMvc::instance(null, 'default')->config['project']['directory']);
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
	private function includeFile(&$fileName = null){ // includeFile will modify $fileName to the File name which is included without the .php suffix
		if(!isset($fileName) || empty($fileName)){
			$e = new SmallMVCException("fileName must be set", DEBUG);
			throw $e;
		}
		if(!preg_match('/\.php$/', $fileName))
			$fileName .= '.php';
		if(preg_match('/^@\./', $fileName)){
			$fileName = preg_replace('/^@\.(.*)/', "$1", $fileName);
			$includePath = implode(PS, SMvc::instance(null, 'default')->config['project']['directory']);
			$includePath = str_replace(DS.DS, DS, $includePath);
		}else{
			$includePath = implode(PS, SMvc::instance(null, 'default')->config['project']['directory']) . PS . get_include_path();
		}
		$subPath = explode('.', $fileName);
		$fileName = implode('.', array_slice($subPath, -2, 2));
		$modifiedName = preg_replace('/(.*)\.php$/', '$1', $fileName);
		array_splice($subPath, -2, 2);
		!empty($subPath) ? $fileName = implode(DS, $subPath).DS.$fileName : null;
		$ps = explode(PS, $includePath);
		foreach($ps as $path){
			if(file_exists($path . DS . $fileName)){
				require_once($path . DS . $fileName);
				$fileName = $modifiedName;
				return true;
			}
		}
		return false;
	}
}
?>
