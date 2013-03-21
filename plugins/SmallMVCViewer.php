<?php
class SmallMVCViewer{
	var $viewVars = array();
	public function assign($key, $value = null){
		if(empty($key)){
			$e = new SmallMVCException("key must be set", DEBUG);
			throw $e;
		}
		if(isset($value))
			$this->viewVars[$key] = $value;
		else
			foreach($key as $k => $v)
				if(is_int($k))
					$this->viewVars[] = $v;
				else
					$this->viewVars[$k] = $v;
	}
	public function display($fileName = null, $viewVars = null){
		if(empty($fileName)) {
			$e = new SmallMVCException("\$fileName must be set", DEBUG);
			throw $e;
		}else{
			if(!preg_match('/\.html$/', $fileName))
				$fileName .= '.html';
			//if start with '#.' find in system directory, this is for system use only!
			if(preg_match('/^#\./', $fileName)){
				$fileName = preg_replace('/^#\.(.*)/', '$1', $fileName);
				$fileName = SMVC_BASEDIR . DS . 'view' .  DS . $fileName;
			}
			else
				$fileName = APPDIR . DS . 'view' .  DS . $fileName;
		}
		if(!file_exists($fileName)){
			$e = new SmallMVCException("display:$fileName", PAGE_NOT_FOUND);
			throw $e;
		}
		return $this->_view($fileName, $viewVars);
	}
	public function layout($template = null, $layout = null){
		if(empty($template)) {
			$e = new SmallMVCException("\$template must be set", DEBUG);
			throw $e;
		}else{
			if(!preg_match('/\.html$/', $template))
				$template .= '.html';
			$template = APPDIR . DS . 'view' .  DS . $template;
		}
		if(!file_exists($template)){
			$e = new SmallMVCException("template:$template", PAGE_NOT_FOUND);
			throw $e;
		}
		if($layout){
			if(!preg_match('/\.html$/', $layout))
				$layout = APPDIR. DS . 'view' . DS . $layout . '.html';
		}else
			$layout = substr($template, 0, strrpos($template, DS, -1)) . DS . 'layout.html';
		if(!file_exists($layout)){
			$e = new SmallMVCException("layout:$layout", PAGE_NOT_FOUND);
			throw $e;
		}
		if(preg_match('/^{__LAYOUT__}/', file_get_contents($layout, LOCK_EX))){
			$content = file_get_contents($template, LOCK_EX);
			$layoutName = $layout;
			$layout = file_get_contents($layout, LOCK_EX);
			$layout = "<?php 
				if(!isset(\$_SESSION['__prevent_template_view_directly_']) || (isset(\$_SESSION['__prevent_template_view_directly_']) && !\$_SESSION['__prevent_template_view_directly_'])){
					echo 'can not access this file directly!';
					exit(0);
						}
				?>\n".$layout;
			$regex = array(
				"/{__LAYOUT__}/s" => "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n",
			);
			$layout = preg_replace(array_keys($regex), array_values($regex), $layout);
			$cacheFile = APPDIR . DS . 'cache' . DS . md5($template) . '.php';
			if (is_file($cacheFile) && !( (filemtime($template) > filemtime($cacheFile)) || (filemtime($layoutName) > filemtime($cacheFile))) ) {//判断缓存是否有效
				 extract($this->viewVars, EXTR_OVERWRITE);
				 $this->_view($cacheFile);
				 return;
			}
			$content = preg_replace("/{__CONTENT__}/s", $content, $layout);
			if(!file_put_contents($cacheFile, $content, LOCK_EX)){
				$e = new SmallMVCException("can not wirte content to cache/ directroy", DEBUG);
				throw $e;
			}
			extract($this->viewVars, EXTR_OVERWRITE);
		  $this->_view($cacheFile);
		}else{
			$e = new SmallMVCException("Not a layout file:$layout", DEBUG);
			throw $e;
		}
	}
	private function _view($fileName, $viewVars = null){	
		if(empty($fileName)){
			$fileName = 'index.html';
		}
		if(!preg_match('/(\.html$)|(\.php$)/', $fileName)){
			$fileName .= '.html';
		}
		$this->assign_sys_var();
		extract($this->viewVars);
		if(isset($viewVars))
			extract($viewVars);
		try{
			$org_include_path = get_include_path();
			set_include_path(APP_INCLUDE_PATH . PS . $org_include_path);
			if(!headers_sent()){
				$charset = SMvc::instance(null, 'default')->config['default_charset'];
				header("content-Type: text/html; charset={$charset}");
        header("Cache-control: private");
				header("X-Powered-By:SmallMVC/".SMVC_VERSION);
			}
			//security check
			$content = file_get_contents($fileName);
			$regex = array(
				"/SMvc::instance\(.*?\)/s",
			);
			foreach($regex as $a){
				if(preg_match($a, $content)){
					$e = new SmallMVCException("there are some framework defined variable in your template file,please check!", DEBUG);	
					throw $e;
				}
			}
			require_once($fileName);
			set_include_path($org_include_path);
		}catch(Exception $e){
			$e->type = DEBUG;
			throw $e;
		}
	}
	private function assign_sys_var(){
		if(!empty($_SERVER['REQUEST_URI'])){
			$this->assign('curl',preg_replace('/^\/(.*)/', '$1' , $_SERVER['REQUEST_URI']));
		}
		if(!empty($_SERVER['SCRIPT_NAME'])){
			$controllerName = get_class(SMvc::instance(null,'controller'));
			$controllerName = preg_replace('/(.*)Controller$/i', '$1', $controllerName);
			$this->assign('entry', preg_replace('/^\/(.*)/', '$1' , $_SERVER['SCRIPT_NAME']));
			$this->assign('ccontroller', preg_replace('/^\/(.*)/', '$1' , $_SERVER['SCRIPT_NAME'] . '/' . $controllerName));
		}
		if(defined('WEB_ROOT')){
			$this->assign('appdir', preg_replace('/^\/*(.*)/', '$1', WEB_ROOT . '/' . APPDIR));
			$this->assign('webroot', preg_replace('/^\/*(.*)/', '$1', WEB_ROOT));
		}else{
			$e = new SmallMVCException("Please check WEB_ROOT defination in entry file(SmallMVC.php)", DEBUG);
			throw $e;
		}
	}
}
?>
