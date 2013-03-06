<?php
function dump($var, $label=null) {
    $label = ($label === null) ? '' : rtrim($label) . ' ';
		$output = "<div style='text-align:left;'>";
		ob_start();
		var_dump($var);
		$output .= ob_get_clean();
		if (!extension_loaded('xdebug')) {
			$output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
			$output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
		}
		$output .= "</div>";
		echo $output;
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
				$e = new SmallMVCException("Cannot create directory '".APPDIR ."'", DEBUG);
				throw $e;
			}
		}
		if(!file_exists(APPDIR . DIRECTORY_SEPARATOR . 'controller')){
			if(!mkdir(APPDIR . DIRECTORY_SEPARATOR . 'controller')){
				$e = new SmallMVCException("Cannot create directory '".APPDIR . DIRECTORY_SEPARATOR . 'controller'."'", DEBUG);
				throw $e;
			}
		}
		if(!file_exists(APPDIR . DIRECTORY_SEPARATOR . 'model')){
			if(!mkdir(APPDIR . DIRECTORY_SEPARATOR . 'model')){
				$e = new SmallMVCException("Cannot create directory '".APPDIR . DIRECTORY_SEPARATOR . 'model'."'", DEBUG);
				throw $e;
			}
		}
		if(!file_exists(APPDIR . DIRECTORY_SEPARATOR . 'view')){
			if(!mkdir(APPDIR . DIRECTORY_SEPARATOR . 'view')){
				$e = new SmallMVCException("Cannot create directory '".APPDIR . DIRECTORY_SEPARATOR . 'view'."'", DEBUG);
				throw $e;
			}
		}
		if(!file_exists(APPDIR . DIRECTORY_SEPARATOR . 'plugins')){
			if(!mkdir(APPDIR . DIRECTORY_SEPARATOR . 'plugins')){
				$e = new SmallMVCException("Cannot create directory '".APPDIR . DIRECTORY_SEPARATOR . 'plugins'."'", DEBUG);
				throw $e;
			}
		}
		if(!file_exists(APPDIR . DIRECTORY_SEPARATOR . 'config')){
			if(!mkdir(APPDIR . DIRECTORY_SEPARATOR . 'config')){
				$e = new SmallMVCException("Cannot create directory '".APPDIR . DIRECTORY_SEPARATOR . 'config'."'", DEBUG);
				throw $e;
			}
		}
		if(!file_exists(APPDIR . DIRECTORY_SEPARATOR . 'cache')){
			if(!mkdir(APPDIR . DIRECTORY_SEPARATOR . 'cache')){
				$e = new SmallMVCException("Cannot create directory '".APPDIR . DIRECTORY_SEPARATOR . 'cache'."'", DEBUG);
				throw $e;
			}
		}
	}
}

//param1:the Model file name
//param2:the params pass to Model
function M($name = null, $params = null){
	//get SMVC controller object
	if(SMvc::instance(null, 'default') && SMvc::instance(null, 'controller')){
		$controllerObj = SMvc::instance(null, 'controller');
		$model = $controllerObj->load->model($name, $params);
		return $model;
	}
	return null;
}
function C($name = null, $params = null){
	if(empty($name)){
		$e = new SmallMVCException("Controller name is empty", DEBUG);
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
						$controller = SMvc::instance(null, 'controller');
						$controller->assign('info', $msg);
            $controller->display('#.message');
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
		$e = new SmallMVCException("import name is empty", DEBUG);
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
