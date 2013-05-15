<?php
class SmallMVCModel{
	private $driver = null;
	function __construct($poolName){
		$params_list = func_get_args();
		//remove poolName
		empty($params_list) ? null : array_shift($params_list);
		$userModelDriver = SMvc::instance(null, 'default')->config[$poolName]['plugin'];
		import($userModelDriver);
		try{
			$refClass = new ReflectionClass($userModelDriver);
			$this->driver = $refClass->newInstanceArgs($params_list);
		}catch(ReflectionException $refExp){
			$e = new SmallMVCException($refExp->__toString(), DEBUG);
			throw $e;
		}

	}
	function __call($name, $args = null){
		return call_user_func_array(array($this->driver, $name), $args);
	}
}
?>
