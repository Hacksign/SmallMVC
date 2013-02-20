<?php
function dump($var){
	echo "<span style='color:lightblue;' title='file:".__FILE__." line:".__LINE__."'>" . $var . "</span><br/>";
}
function default_index(){
	echo "<div style='text-align:center;width:100%;line-height:20px;margin-top:20%;'>Welcome use SmallMVC Framework</div>";
}
function copy_right(){
return "<div style='text-align:center;'>
		  <p>Power by <a href='http://www.hacksign.cn' target='_blank'>SmallMVC</a> ". SMVC_VERSION . "</p>
		  <p>Author Hacksign</p>
		</div>";
}
function create_default_directories(){
	if(defined('APPDIR')){
		if(!file_exists(APPDIR)){
			if(!mkdir(APPDIR)){
				$e = new Exception("Cannot create directory '".APPDIR ."'");
				$e->type = DEBUG;
				throw $e;
			}
		}
		if(!file_exists(APPDIR . DIRECTORY_SEPARATOR . 'controller')){
			if(!mkdir(APPDIR . DIRECTORY_SEPARATOR . 'controller')){
				$e = new Exception("Cannot create directory '".APPDIR . DIRECTORY_SEPARATOR . 'controller'."'");
				$e->type = DEBUG;
				throw $e;
			}
		}
		if(!file_exists(APPDIR . DIRECTORY_SEPARATOR . 'model')){
			if(!mkdir(APPDIR . DIRECTORY_SEPARATOR . 'model')){
				$e = new Exception("Cannot create directory '".APPDIR . DIRECTORY_SEPARATOR . 'model'."'");
				$e->type = DEBUG;
				throw $e;
			}
		}
		if(!file_exists(APPDIR . DIRECTORY_SEPARATOR . 'view')){
			if(!mkdir(APPDIR . DIRECTORY_SEPARATOR . 'view')){
				$e = new Exception("Cannot create directory '".APPDIR . DIRECTORY_SEPARATOR . 'view'."'");
				$e->type = DEBUG;
				throw $e;
			}
		}
		if(!file_exists(APPDIR . DIRECTORY_SEPARATOR . 'plugins')){
			if(!mkdir(APPDIR . DIRECTORY_SEPARATOR . 'plugins')){
				$e = new Exception("Cannot create directory '".APPDIR . DIRECTORY_SEPARATOR . 'plugins'."'");
				$e->type = DEBUG;
				throw $e;
			}
		}
		if(!file_exists(APPDIR . DIRECTORY_SEPARATOR . 'config')){
			if(!mkdir(APPDIR . DIRECTORY_SEPARATOR . 'config')){
				$e = new Exception("Cannot create directory '".APPDIR . DIRECTORY_SEPARATOR . 'config'."'");
				$e->type = DEBUG;
				throw $e;
			}
		}
		if(!file_exists(APPDIR . DIRECTORY_SEPARATOR . 'cache')){
			if(!mkdir(APPDIR . DIRECTORY_SEPARATOR . 'cache')){
				$e = new Exception("Cannot create directory '".APPDIR . DIRECTORY_SEPARATOR . 'cache'."'");
				$e->type = DEBUG;
				throw $e;
			}
		}
	}
}

//param1:the Model file name
//param2:the params pass to Model
function M($name = null, $params = null){
	if(empty($name)){
		$e = new Exception("Model name is empty");
		$e->type = DEBUG;
		throw $e;
	}
	(preg_match("/Model$/", $name))? null : $name .= 'Model';

	//get SMVC controller object
	if(SMvc::instance(null, 'default') && SMvc::instance(null, 'controller')){
		$controllerObj = SMvc::instance(null, 'controller');
		if(isset($controllerObj->$name)){
			return $controllerObj->$name;
		}
		$controllerObj->$name = $controllerObj->load->model($name, $params);
		return $controllerObj->$name;
	}
	
	return null;
}
function C($name = null, $params = null){
	if(empty($name)){
		$e = new Exception("Controller name is empty");
		$e->type = DEBUG;
		throw $e;
	}
	(preg_match("/Controller$/", $name))? null : $name .= 'Controller';
	//get SMVC controller object
	if(SMvc::instance(null, 'default') && SMvc::instance(null, 'controller')){
		$controllerObj = SMvc::instance(null, 'controller');
		if(isset($controllerObj->$name)){
			return $controllerObj->$name;
		}
	}

	return $controllerObj->load->library($name, $params);
}
function redirect($url, $time=0, $msg='') {
    if (empty($msg))
        $msg = "<table style='text-align:center;height:100%;width:100%;'><tr><td>System will redirect to {$url} in {$time} second(s).</td></tr></table>";
		else
        $msg = "<table style='text-align:center;height:100%;width:100%;'><tr><td>$msg</td></tr></table>";
    if (!headers_sent()) {
        if (0 === $time) {
            header("Location: " . $url);
        } else {
            header("refresh:{$time};url={$url}");
            echo($msg);
        }
        exit();
    } else {
        $str = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
        if ($time != 0)
            $str .= $msg;
        exit($str);
    }
}
function import($name = null){
	if(empty($name)){
		$e = new Exception("import name is empty");
		$e->type = DEBUG;
		throw $e;
	}
	//get SMVC controller object
	if(SMvc::instance(null, 'default') && SMvc::instance(null, 'controller'))
		$controllerObj = SMvc::instance(null, 'controller');
	if($controllerObj)
		return $controllerObj->load->script($name);
	else
		return false;
}
?>
