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
 * 框架模板引擎.
 *
 * @author Hacksign <evilsign@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT License
 * @category 框架核心文件
 */
class SmallMVCViewer {
  /**
   * @var $data 模板引擎内部变量,用于存放assign的值,模板中可直接使用$this->data访问.
   */
	private $data;
  /**
   * 构造函数,只负责对data初始化.默认值stdClass.
   *
   * @return void
   */
	function __construct() {
		$this->data = new stdClass;
	}

 /**
  * 将变量输出到模板.
  *
  * 此函数将一个后台变量输出到模板,使得模板可以访问此变量.
  * 如果key为数组类型/对象类型,则忽略value参数,并以key所指类型中的'key'作为名称'value'作为值,进行assign操作.
  *
  * 使用方法:
  * 
  *     $this->assign('var', $some_var)
  *     $this->assign('var', 'some_str')
  *     $this->assign($some_self_defined_type)
  *
  * @param string $key 模板变量名.
  * @param mixed $value 后台变量
  *
  * @return void
  */
	public function assign($key, $value = '') {
		if(is_array($key)) {
			foreach($key as $n=>$v)
				$this->data->$n = $v;
		} elseif(is_object($key)) {
			foreach(get_object_vars($key) as $n=>$v)
				$this->data->$n = $v;
		} else {
			$this->data->$key = $value;
		}
	}

 /**
  * 清空模板引擎中所有的模板变量.
  *
  * 此函数会将之前assign的所有变量清空,实际上,是将所有变量赋值为一个stdClass.
  *
  * 使用方法:
  * 
  *     $this->clear()
  *
  * @return void
  */
	public function clear() {
		$this->data = new stdClass;
	}

 /**
  * 显示一个页面.
  *
  * 此函数从工程的view目录下和框架的view目录下,寻找第一个参数指定的页面(不含.html后缀),解析后并显示或返回解析后的代码.
  *
  * 如果页面存放于view的子目录下,则页面名称前需要加子目录名,并以'/'分隔各个目录名和文件名即可.
  *
  * 如果只想在工程的view目录下寻找,则文件名可以以'@.'开始.如果只想在框架的view目录下寻找,则文件名可以以'#.'开始(此功能理论上只允许框架使用).
  *
  * 使用方法:
  * 
  *     $this->display('index')
  *     $this->display('index', 'common/layout')
  *     $html = $this->display('index', 'common/layout', true)
  *
  * @param string $fileName 要显示的页面,如果此页面在view目录的子目录下,则前面加子目录名称,页面不需要后缀.
  * @param mixed $layoutName 模板页面名称,如果此页面在view目录的子目录下,则前面加子目录名称,页面不需要后缀.
  * @param bool $getStaticHtml 是否返回解析后的html内容.
  *
  * @return string 经过解析后的HTML代码.
  */
	public function display($fileName, $layoutName = null, $getStaticHtml = false){
		return $this->_view($fileName, $layoutName, $getStaticHtml);
	}
 /**
  * 调用模板显示一个页面.
  *
  * 此函数除$layoutName参数默认为'layout.html'外,和display参数完全相同.
  *
  * 使用方法:
  * 
  *     $this->layout('index')
  *     $this->layout('index', 'common/layout')
  *     $html = $this->layout('index', 'common/layout', true)
  *
  * @param string $fileName 要显示的页面,如果此页面在view目录的子目录下,则前面加子目录名称,页面不需要后缀.
  * @param mixed $layoutName 模板页面名称,如果此页面在view目录的子目录下,则前面加子目录名称,页面不需要后缀.
  * @param bool $getStaticHtml 是否返回解析后的html内容.
  *
  * @return string 经过解析后的HTML代码.
  */
	public function layout($fileName, $layoutName = 'layout.html', $getStaticHtml = false){
		return $this->_view($fileName, $layoutName, $getStaticHtml);
	}

 /**
  * 参考layout()和display函数的说明.
  *
  * @throws EXCEPTION_NOT_FOUND 类型异常,如果要显示的页面或模板页面无法找到.
  * @throws EXCEPTION_ACCESS_DENIED 类型异常,如果要显示的页面或模板页面无法找到.
  * @throws EXCEPTION_LAYOUTFILE_ERROR 类型异常,如果要显示的页面或模板页面无法找到.
  *
  * @param string $fileName 要显示的页面,如果此页面在view目录的子目录下,则前面加子目录名称,页面不需要后缀.
  * @param mixed $layoutName 模板页面名称,如果此页面在view目录的子目录下,则前面加子目录名称,页面不需要后缀.
  * @param bool $getStaticHtml 是否返回解析后的html内容.
  *
  * @return string 经过解析后的HTML代码.
  */
	private function _view($fileName, $layoutName = null, $getStaticHtml = false){	
		//file must end up with '.html' suffix
		if(!preg_match('/\.html$/', $fileName)) $fileName .= '.html';
		if(!empty($layoutName) && !preg_match('/\.html$/', $layoutName)) $layoutName .= '.html';
		//if start with '#.' find in system directory, this is for system use only!
		if(preg_match('/^#\./', $fileName)){
			$fileName = preg_replace('/^#\.(.*)/', '$1', $fileName);
			$fileName = SMVC_BASEDIR . DS . 'view' .  DS . $fileName;
		}else{
			$fileName = SMvc::instance(null, 'default')->config['project']['directory']['view'] . DS . $fileName;
		}
		if(!empty($layoutName) && preg_match('/^#\./', $layoutName)){
			$layoutName = preg_replace('/^#\.(.*)/', '$1', $layoutName);
			$layoutName = SMVC_BASEDIR . DS . 'view' .  DS . $layoutName;
		}else if(!empty($layoutName)){
			$layoutName = SMvc::instance(null, 'default')->config['project']['directory']['view'] . DS . $layoutName;
		}
		//check whether file exists
		while(strstr($fileName, DS.DS)){
			$fileName = str_replace(DS.DS, DS, $fileName);
		}
		if(!file_exists($fileName)){
			$e = new SmallMVCException("display:$fileName", EXCEPTION_NOT_FOUND);
			throw $e;
		}
		if(!empty($layoutName) && !file_exists($layoutName)){
			$e = new SmallMVCException("display:$fileName", EXCEPTION_NOT_FOUND);
			throw $e;
		}
		//assign framework pre-defined variables
		if(!empty($_SERVER['SCRIPT_NAME'])){
			$controllerName = get_class(SMvc::instance(null,'controller'));
			$controllerName = preg_replace('/(.*)Controller$/i', '$1', $controllerName);
			$this->assign('_entry_', preg_replace('/^\/(.*)/', '$1' , $_SERVER['SCRIPT_NAME']));
			$this->assign('_controller_', preg_replace('/^\/(.*)/', '$1' , $controllerName));
		}
		if(!strlen(APPDIR)) $this->assign('_appdir_', '.');
		else $this->assign('_appdir_', APPDIR);
		if(!strlen(PROJECT_DIR)) $this->assign('_webroot_', '.');
		else $this->assign('_webroot_', PROJECT_DIR);
		if(defined('SMVC_VERSION')) $this->assign('_SMVC_VERSION_', SMVC_VERSION);
		$cacheFile = SMvc::instance(null, 'default')->config['project']['directory']['cache'] . DS . 'views' . DS . md5($fileName) . '.html';
    $regex = DS.'+';
    $cacheFile = preg_replace("/(\/+)|(\+)/", DS, $cacheFile);
		//see if cache file is up to date
		if (filemtime($cacheFile) === false || filemtime($fileName) > filemtime($cacheFile) || filemtime($layoutName) > filemtime($cacheFile)){
			$content = file_get_contents($fileName, LOCK_EX);
			if(!empty($layoutName)){
				$layoutContent = file_get_contents($layoutName, LOCK_EX);
				if(preg_match("/^{__LAYOUT__}/", $layoutContent)){
					$layoutContent = str_replace("{__LAYOUT__}", "", $layoutContent);
					$content = str_replace("{__CONTENT__}", $content, $layoutContent);
				}else{
					$e = new SmallMVCException("Not a layout file:$layout", EXCEPTION_LAYOUTFILE_ERROR);
					throw $e;
				}
			}
			$lines = explode("\n", $content);
			$newLines = array();
			$matches = null;
			foreach($lines as $line){
				$line = trim($line);
				$num = preg_match_all('/\{\{\s*?([^{}]+?)\s*?\}\}/', $line, $matches);
				for($i = 0; $i < $num; $i++) {
					$match = $matches[0][$i];
					$new = $this->transformSyntax($matches[1][$i]);
					if($new) $line = str_replace($match, $new, $line);
				}
				$newLines[] = $line;
			}
      if(!file_exists(dirname($cacheFile))){
        if(is_writable(SMvc::instance(null, 'default')->config['project']['directory']['cache'])){
          mkdir(dirname($cacheFile), 0755, false);
        }else if(!SMvc::instance(null, '_SMVC_EXCEPTION_PROCESSING')){
          $e = new SmallMVCException(SMvc::instance(null, 'default')->config['project']['directory']['cache']." is not writable", EXCEPTION_ACCESS_DENIED);
          throw $e;
        }
      }
			$content = implode("\n", $newLines);
			if(!file_put_contents($cacheFile, $content, LOCK_EX)){
				if(SMvc::instance(null, '_SMVC_EXCEPTION_PROCESSING')){
					$cacheFile = tempnam(sys_get_temp_dir(), '_smvc_');
					file_put_contents($cacheFile, $content, LOCK_EX);
				}else{
					$e = new SmallMVCException("can not wirte content to cache/ directroy", EXCEPTION_ACCESS_DENIED);
					throw $e;
				}
			}
		}
		$content = "";
		ob_start();
		require_once($cacheFile);
		$content = ob_get_contents();
		ob_end_clean();
		if(SMvc::instance(null, '_SMVC_EXCEPTION_PROCESSING')){
			unlink($cacheFile);
		}
		if($getStaticHtml){
			return $content;
		}else{
			if(!headers_sent()){
				$charset = SMvc::instance(null, 'default')->config['charset'];
				header("content-Type: text/html; charset={$charset}");
        header("Cache-control: private");
				header("X-Powered-By:SmallMVC/".SMVC_VERSION);
			}
			echo $content;
			return null;
		}
	}

 /**
  * 模板变量替换.
  *
  * 此函数将assign的后台变量复制到模板引擎的$this->data变量中
  *
  * @throws DEBUG 模板变量出现语法错误. 
  *
  * @param string $input
  *
  * @return void
  */
	private function transformSyntax($input) {
		$from = array(
			'/(^|\[|,|\(|\+| )([a-zA-Z_][a-zA-Z0-9_]*)($|\.|,|\)|\[|\]|\+)/',
			'/(^|\[|,|\(|\+| )([a-zA-Z_][a-zA-Z0-9_]*)($|\.|,|\)|\[|\]|\+)/', // again to catch those bypassed by overlapping start/end characters 
			'/([a-zA-Z_][a-zA-Z0-9_]*)\s*?([<>=!]+)\s*?(\'|"[a-zA-Z0-9_]+\'|")/',
			'/\./',
		);
		$to = array(
			'$1$this->data->$2$3',
			'$1$this->data->$2$3',
			'$this->data->$1 $2 $3',
			'->'
		);

		//$parts = explode(':', $input);
		$pos = strpos($input, ':');
		if($pos !== false){
			$parts[0] = substr($input, 0, $pos);
			$parts[1] = substr($input, $pos + 1);
		}else{
			$parts[] = $input;
		}
		$string = '<?php ';
		/*
		 *{{some_variable}}
		 *{{if:(empty(title)), value1, value2}}
		 *{{if:(empty(title))}}{{end}}
		 *{{if:(empty(title))}}aaaa{{else}}bbbb{{end}}
		 *{{foreach:items,each_item}}{{end}}
		 *{{foreach:items,each_key, each_value}}{{end}}
		 *{{switch:title}}{{case:value1}}{{case:value2}}{{endswitch}}
		 */
		switch($parts[0]) {
			case 'if':
				if(substr_count($parts[1], ',') >= 2){
					$left_parenthesis_count = substr_count($parts[1], '(');
					$right_parenthesis_count = substr_count($parts[1], ')');
					if($left_parenthesis_count != $right_parenthesis_count){
						$e = new SmallMVCException('Template syntax error, parenthesis closure not match.', DEBUG);
						throw $e;
					}
					$index = strrpos($parts[1], ')');
					if($index !== false){
						$op_str = $parts[1];
						$aa = substr($op_str, 0, $index + 1);
						//replace variables in first block which is defined in $this->data template variables
						$matches = null;
						$num = preg_match_all('/(\'|"){0,}\b([a-zA-Z_][a-zA-Z0-9_]*?)\b(\'|"){0,}/s', $aa, $matches);
						for($i = 0; $i < $num; ++$i){
							if(isset($this->data->$matches[0][$i])){
								$aa = str_replace($matches[0][$i], '$this->data->'.$matches[0][$i], $aa);
							}
						}
						$pieces[] = $aa;
						$op_str = substr($op_str, $index + 2);
						$bb = explode(',', $op_str);
						$pieces = array_merge($pieces, $bb);
					}else{
						$pieces = explode(',', $parts[1]);
					}
					$condition = $pieces[0];
					$true_value = preg_replace($from, $to, $pieces[1]);
					$false_value = preg_replace($from, $to, $pieces[2]);
					$string .= "if($condition){echo {$true_value};}else{echo {$false_value};}";
				}else{
				 	$string .= $parts[0] . '(' . preg_replace($from, $to, $parts[1]) . ') { ';
				}
				break;
			case 'switch':
				$string .= $parts[0] . '(' . preg_replace($from, $to, $parts[1]) . ') { default: ';
				break;
			case 'foreach':
				$pieces = explode(',', $parts[1]);
				$string .= 'foreach(' . preg_replace($from, $to, $pieces[0]) . ' as ';
				$string .= preg_replace($from, $to, $pieces[1]);
				if(sizeof($pieces) == 3) // prepares the $value portion of foreach($var as $key=>$value)
					$string .= '=>' . preg_replace($from, $to, $pieces[2]);
				$string .= ') { ';
				break;
			case 'end':
			case 'endswitch':
				$string .= '}';
				break;
			case 'else':
				$string .= '} else {';
				break;
			case 'case':
				$string .= 'break; case ' . preg_replace($from, $to, $parts[1]) . ':';
				break;
			default:
				$string .= 'echo ' . preg_replace($from, $to, $parts[0]) . ';';
				break;
		}
		$string .= ' ?>';
		return $string;
	}
}
?>
