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
//param1:the Model file name
//param2:the params pass to Model
function M($name = null, $poolName = null){
	$params_list = func_get_args();
	array_shift($params_list); // remove $name from params list
	empty($params_list) ? null : array_shift($params_list);
	//get SMVC loader object
	if(SMvc::instance(null, 'default') && SMvc::instance(null, 'loader')){
		$load = SMvc::instance(null, 'loader');
		$model = $load->model($name, $poolName, $params_list);
		return $model;
	}
	return null;
}
function C($name = null){
	$params_list = func_get_args();
	//remove $name
	empty($params_list) ? null : array_shift($params_list);
	if(empty($name)){
		$e = new SmallMVCException("Controller name is empty", DEBUG);
		throw $e;
	}
	(preg_match("/Controller$/", $name))? null : $name .= 'Controller';
	//get SMVC controller object
	if(SMvc::instance(null, 'default') && SMvc::instance(null, 'controller')){
		$controllerObj = SMvc::instance(null, 'controller');
		if(!empty($controllerObj->$name))
			return $controllerObj->$name;
	}
	if(SMvc::instance(null, 'default') && SMvc::instance(null, 'loader')){
		$load = SMvc::instance(null, 'loader');
		return $load->library($name, $params_list);
	}
	$e = new SmallMVCException("Controller Object doesn't exists", DEBUG);
	throw $e;
}
function G($key = null){
	if(empty($key)){
		$e = new SmallMVCException("You should declare one key from config variable", DEBUG);
		throw $e;
	}
	//get config
	if(SMvc::instance(null, 'default')){
		$config = SMvc::instance(null, 'default')->config[$key];
		if(isset($config)) return $config;
		else return null;
	}else{
		$e = new SmallMVCException("Default controller doesnt set, can not get config", DEBUG);
		throw $e;
	}
}
function U($url = null, $suffix = null){
	if(SMvc::instance(null, 'default')){
		$config = SMvc::instance(null, 'default')->config;
		switch($config['routing']['type']){
			case 'troditional':
				$tmp_url = explode('/', $url);
				$c = array_shift($tmp_url);
				$a = array_shift($tmp_url);
				$url = "?_c=$c&_a=$a";
				for($i = 0; $i < count($tmp_url); ++$i){
					$url .= ($i % 2 === 0) ? '&' : '=';
					$url .= $tmp_url[$i];
				}
				break;
			case 'pathinfo':
				break;
			default:
		}
		$url .= empty($suffix) ? '' : $suffix;
		return $url;
	}else{
		$e = new SmallMVCException("Can not get SMVC instance", DEBUG);
		throw $e;
	}
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
function load(&$name = null){
	if(empty($name)){
		$e = new SmallMVCException("import name is empty", DEBUG);
		throw $e;
	}
	//get SMVC Loader
	if(SMvc::instance(null, 'default') && SMvc::instance(null, 'loader'))
		$load = SMvc::instance(null, 'loader');
	if($load)
		return $load->script($name);
	else
		return false;
}
//const version of import which allowed pass a const string
function import($filename){
	return load($filename);
}
function remove_xss($val) {
	 // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
	 // this prevents some character re-spacing such as <java\0script>
	 // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
	 $val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $val);

	 // straight replacements, the user should never need these since they're normal characters
	 // this prevents like <IMG SRC=@avascript:alert('XSS')>
	 $search = 'abcdefghijklmnopqrstuvwxyz';
	 $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	 $search .= '1234567890!@#$%^&*()';
	 $search .= '~`";:?+/={}[]-_|\'\\';
	 for ($i = 0; $i < strlen($search); $i++) {
			// ;? matches the ;, which is optional
			// 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

			// @ @ search for the hex values
			$val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ;
			// @ @ 0{0,7} matches '0' zero to seven times
			$val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ;
	 }

	 // now the only remaining whitespace attacks are \t, \n, and \r
	 $ra1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
	 $ra2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
	 $ra = array_merge($ra1, $ra2);

	 $found = true; // keep replacing as long as the previous round replaced something
	 while ($found == true) {
			$val_before = $val;
			for ($i = 0; $i < sizeof($ra); $i++) {
				 $pattern = '/';
				 for ($j = 0; $j < strlen($ra[$i]); $j++) {
						if ($j > 0) {
							 $pattern .= '(';
							 $pattern .= '(&#[xX]0{0,8}([9ab]);)';
							 $pattern .= '|';
							 $pattern .= '|(&#0{0,8}([9|10|13]);)';
							 $pattern .= ')*';
						}
						$pattern .= $ra[$i][$j];
				 }
				 $pattern .= '/i';
				 $replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag
				 $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
				 if ($val_before == $val) {
						// no replacements were made, so exit the loop
						$found = false;
				 }
			}
	 }
	 return $val;
}
?>
