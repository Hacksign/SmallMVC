<?php
/**
 * License:
 * (MIT License)
 * Copyright (c) 2013 Hacksign (http://www.hacksign.cn)
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

/**
* dump一个变量.
*
* 以结构化格式输出一个变量.
*
* 使用方法:
* 
*     $this->dump($var)
*     $this->dump($var, 'xx:')
*
* @category 全局函数
* @param mixed $var 要输出的变量.
* @param string $label 结构化输出变量的前缀.
*
* @return string 以html代码格式化过得变量类型以及变量内容.
*/
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
/**
* 输出本框架的版权信息.
*
* 使用方法:
* 
*     echo copy_right();
*
* @category 全局函数
* @return string 版权信息.
*/
function copy_right(){
  return "<div style='text-align:center;'>
        <p>Power by <a href='http://www.hacksign.cn' target='_blank'>SmallMVC</a> ". SMVC_VERSION . "</p>
        <p>Author Hacksign</p>
      </div>";
}
/**
* 快速新建一个Model对象.
*
* 快速初始化工程model目录下的一个Model对象.
*
* 使用方法:
* 
*     $model = M('Article')
*     $model = M('Article', 'company_pool')
*
* @category 全局函数
* @param string $name model目录下的对象名称(不需要Model字符串).
* @param string $poolName 配置文件(默认为config/config.php)中的数据库池,用来区分多个数据库用.
*
* @return class 数据对象.
*/
function M($name = null, $poolName = null){
	$params_list = func_get_args();
	array_shift($params_list); // remove $name from params list
	empty($params_list) ? null : array_shift($params_list); // remove $poolName from params list
	//get SMVC loader object
	if(SMvc::instance(null, 'default') && SMvc::instance(null, 'loader')){
		$load = SMvc::instance(null, 'loader');
		$model = $load->model($name, $poolName, $params_list);
		return $model;
	}
	return null;
}
/**
* 快速新建一个Controller对象.
*
* 快速初始化工程controller目录下的一个controller对象.
*
* 使用方法:
* 
*     $controller = C('Article')
*
* @category 全局函数
* @param string $name controller目录下的对象名称(不需要Controller字符串).
*
* @return class 数据对象.
*/
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
/**
* 获取配置文件中以key为名称的值.
*
* 获取配置文件(默认为config/config.php)中定义的,以key为名称的变量值.
*
* 使用方法:
* 
*     $controller = G('Article')
*
* @category 全局函数
* @param string $key controller目录下的对象名称(不需要Controller字符串).
*
* @return mixed 配置文件中的值.
*/
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
/**
* 快速生成一个符合框架要求的url.
*
* 根据框架的路由模式,快速生成一个合法的url并返回.
*
* 使用方法:
* 
*     $url = U('Article')
*     $url = U('Article', '#notice')
*
* @category 全局函数
* @param string $key controller目录下的对象名称(不需要Controller字符串).
* @param string $suffix 生成的url后面额外附加的字符串.
*
* @return mixed 配置文件中的值.
*/
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
/**
* 重定向页面.
*
* 利用http协议的302状态,生成页面跳转响应.
*
* 使用方法:
* 
*     redirect('Article', 3);
*     $this->redirect('Article', 3, 'this is a test');
*
* @category 全局函数
* @param string $url 要转向的url.
* @param int $time 转向前等待的时间.
* @param string $msg 转向页面的提示信息.
*
* @return void
*/
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
            if(!$controller){
                $controller = C(G('system')['controller']);

            }
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
/**
* 加载php脚本.
*
* 加载一个.php文件到框架中.如果只想从工程目录下加载文件,则文件以'@.'开头,目录之间以'.'分隔.如果只想从框架目录下加载,则文件以'#.'开头,目录之间以'.'分隔.
*
* <b><font color=red>注意</font></b>:
*     此函数可能会改变$name参数的内容,$name按引用传递.$name在此函数返回后为该函数实际加载的文件名.
*
* 使用方法:
* 
*     load('test');
*     load('directory.test');
*     load('@.directory.test');
*     load('#.directory.test');
*
* @category 全局函数
* @param string $name 要加在的资源URI.
*
* @return mixed 成功加载返回加载的资源,否则返回false.
*/
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
/**
* load函数的别名,忽略对$filename参数的修改.
*
* 此函数为load函数的别名,只不过会忽略load函数对参数$filename的修改.
*
* 使用方法:
* 
*     import('test');
*     import('directory.test');
*     import('@.directory.test');
*     import('#.directory.test');
*
* @category 全局函数
* @param string $filename 要加在的资源URI.
*
* @return mixed 成功加载返回加载的资源,否则返回false.
*/
function import($filename){
	return load($filename);
}
/**
* 创建工程目录结构.
*
* 创建工程默认目录结构.
*
* @thorws EXCEPTION_ACCESS_DENIED 当工程目录不可写时.
*/
function create_project_directory(){
  clearstatcache();
  if(is_writable(PROJECT_ROOT.DS.APPDIR)){
    foreach(SMvc::instance(null, 'default')->config['project']['directory'] as $each_directory){
      if(!file_exists($each_directory)){
        mkdir($each_directory, 0755, false);
      }
    }
  }else{
    $path = preg_replace('/(\/+)|(\\+)/', DS, PROJECT_ROOT.DS.APPDIR);
    echo "<table width=100% height=100%><tr><td align=center>$path is not writable !</td></tr><table>";
    exit();
  }
}
/**
* 此函数移除潜在的xss字符串.
*
* 移除潜在xss攻击字符串.
* <b>注意</b>:
*     该函数不保证可以过滤掉所有xss攻击.
*     使用该函数过滤字符串时可能会获得与预期不符的结果,请谨慎使用此函数.
*
* 使用方法:
* 
*     $safe_string = remove_xss($var);
*
* @category 全局函数
* @param string $val 要检查的字符串.
*
* @return string 返回移除xss后的字符串.
*/
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
//parse http request headers if there is no function getallheaders 
if (!function_exists('getallheaders')) {
    function getallheaders() {
	$headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value; 
            }
        }
        return $headers;
    }
}
?>
