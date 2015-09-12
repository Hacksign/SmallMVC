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
 * 框架路由类.
 *
 * 目前支持两种路由模式:传统模式和path info模式.默认为path info模式.
 * 关键数据结构:
 *
 *    $urlSegment array structure
 *
 *    $urlSegment[0] => not defined reserved
 *    
 *    $urlSegment[1] => Controller Name(without .php suffix)
 *
 *    $urlSegment[2] => Action Name
 *
 * @author Hacksign <evilsign@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT License
 * @category 框架核心文件
 */
class SmallMVCRouter{
  /**
   * @var $SMVCOBJ 框架对象实例.
   */
	private $SMVCOBJ = null;
  /**
   * 构造函数.
   *
   * 解析框架/项目配置文件中的$config['routing']['type']字段,决定路由模式.
   * 默认为path info模式.
   */
	function __construct(){
		$this->SMVCOBJ = SMvc::instance(null, 'default');
		switch($this->SMVCOBJ->config['routing']['type']){
			case 'troditional':
				$this->troditional();
				break;
			case 'pathinfo':
			default:
				$this->pathinfo();
		}
	}
  /**
   * 传统url模式的解析器
   *
   * url中有两个内置的字段_c和_a,本别代表controller和action.
   * 这两个变量通过GET传递.
   */
	private function troditional(){
		if(empty($_GET['_c'])){
			$_GET['_c'] = $this->SMVCOBJ->config['system']['controller'];
		}
		if(empty($_GET['_a'])){
			$_GET['_a'] = (!empty($this->SMVCOBJ->config['routing']['action']) ? $this->SMVCOBJ->config['routing']['action'] : $this->SMVCOBJ->config['system']['action']);
		}
		$this->SMVCOBJ->urlSegments[1] = $_GET['_c'];
		unset($_GET['_c']);
		$this->SMVCOBJ->urlSegments[2] = $_GET['_a'];
		unset($_GET['_a']);
		if(!empty($this->SMVCOBJ->urlSegments[1]) && !preg_match('/(^[a-zA-Z][a-zA-Z0-9_]*)Controller$/i', $this->SMVCOBJ->urlSegments[1])){
			$this->SMVCOBJ->urlSegments[1] = preg_replace('/(^[a-zA-Z][a-zA-Z0-9_]*)/i', "$1Controller", ucfirst(strtolower($this->SMVCOBJ->urlSegments[1])));
		}else if(empty($this->SMVCOBJ->urlSegments[1])){
			$this->SMVCOBJ->urlSegments[1] = $this->SMVCOBJ->config['routing']['controller'];
		}
		empty($this->SMVCOBJ->urlSegments[2]) ? $this->SMVCOBJ->urlSegments[2] = $this->SMVCOBJ->config['routing']['action'] : null;
	}

  /**
   * 默认的path info解析器.
   */
	private function pathinfo(){
		$url = !empty($_SERVER['REQUEST_URI']) ? str_replace(PROJECT_ENTRYSCRIPT, '', $_SERVER['REQUEST_URI']) : '/'.$this->SMVCOBJ->config['system']['controller'].'/'.(!empty($this->SMVCOBJ->config['routing']['action']) ? $this->SMVCOBJ->config['routing']['action'] : $this->SMVCOBJ->config['system']['action']);
		$this->SMVCOBJ->urlSegments = explode('/', $url);
		if(!empty($this->SMVCOBJ->urlSegments)){
			if(isset($this->SMVCOBJ->urlSegments[0])){
				unset($this->SMVCOBJ->urlSegments[0]);
			}
			if(!empty($this->SMVCOBJ->urlSegments[1]) && !preg_match('/(^[a-zA-Z][a-zA-Z0-9_]*)Controller$/i', $this->SMVCOBJ->urlSegments[1])){
				$this->SMVCOBJ->urlSegments[1] = preg_replace('/(^[a-zA-Z][a-zA-Z0-9_]*)/i', "$1Controller", ucfirst(strtolower($this->SMVCOBJ->urlSegments[1])));
			}else if(empty($this->SMVCOBJ->urlSegments[1])){
				$this->SMVCOBJ->urlSegments[1] = $this->SMVCOBJ->config['routing']['controller'];
			}
			empty($this->SMVCOBJ->urlSegments[2]) ? $this->SMVCOBJ->urlSegments[2] = $this->SMVCOBJ->config['routing']['action'] : null;
			//parse params to $_GET
			foreach($this->SMVCOBJ->urlSegments as $value => $key){
				if($value % 2 == 0 && $value != 0)
					$_GET[$this->SMVCOBJ->urlSegments[$value - 1]] = $key;
				else if(strpos($key, '?') === FALSE) $_GET[$key] = null;
			}
		}else $this->SMVCOBJ->urlSegments = array(1 => $this->SMVCOBJ->config['routing']['controller'], 2 => $this->SMVCOBJ->config['routing']['action']);
	}
}
?>
