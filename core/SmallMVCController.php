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
 * 框架控制器引擎.
 *
 * @author Hacksign <evilsign@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT License
 * @category 框架核心文件
 */
class SmallMVCController{
	private $load;
	private $view;
	private $config;

	function __construct(){
		$this->config = SMvc::instance(null,'default')->config; 
		if(!empty($this->config['system']['viewer'][0])) $viewerName = $this->config['system']['viewer'][0];
		else{
			$e = new SmallMVCException("\$config['system']['viewer'] must be set! read manual for help!", DEBUG);
			throw $e;
		}
		if(empty($this->config['system']['viewer'][1])) $this->config['system']['viewer'][1] = array();
		$this->load = SMvc::instance(null, 'loader');
		$this->view = $this->load->library($this->config['system']['viewer'][0], $this->config['system']['viewer'][1]);
	}
	public function __call($name, $args = null){
		try{
			//parse viewer method first to prevent dead loop
			$refClass = new ReflectionClass($this->view);
			if(($rMethod = $refClass->getMethod($name))){
				if(empty($args)) return $rMethod->invoke($this->view); else return $rMethod->invokeArgs($this->view, $args);
			}else{
				$e = new SmallMVCException("can not find method:$name", DEBUG);
				throw $e;
			}
		}catch(ReflectionException $e){
			//parse controller method, for short name of url address
			if(get_class(SMvc::instance(null,'controller')) === $this->config['system']['controller']){
				try{
					$controller = $this->load->library($this->config['routing']['controller']);
				}catch(SmallMVCException $se){
					//if Default controller doesn't exists in config.php display default page
					$controller = $this->load->library($this->config['system']['controller']);
					SMvc::instance($controller, 'controller');
				}
				SMvc::instance($controller, 'controller');
			}else{
				$controller = SMvc::instance(null,'controller');
			}
			foreach(get_class_methods($controller) as $actionName){
				if(strcasecmp($actionName, $name) === 0){
					return $controller->{$actionName}();
				}
			}//end foreach
			$e = new SmallMVCException("action:{$name} dosen't exists in controller", DEBUG);
			throw $e;
		}//end catch
	}//end function _call
	protected function redirect($url, $time = 0, $msg = ''){
		if(preg_match('#^(http)[s]*://#', $url)) redirect($url, $time, $msg);
		else if(preg_match('#^[a-z]#i', $url)) redirect(PROJECT_ENTRYSCRIPT . DS . $url, $time, $msg);
		else redirect(PROJECT_ENTRYSCRIPT . $url, $time, $msg);
	}
}
?>
