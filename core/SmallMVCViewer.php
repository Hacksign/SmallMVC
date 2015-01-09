<?php
class SmallMVCViewer {
	private $data;
	function __construct() {
		$this->data = new stdClass;
	}

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

	public function clear() {
		$this->data = new stdClass;
	}

	public function display($fileName, $layoutName = null, $getStaticHtml = false){
		return $this->_view($fileName, $layoutName, $getStaticHtml);
	}
	public function layout($fileName, $layoutName = 'layout.html', $getStaticHtml = false){
		return $this->_view($fileName, $layoutName, $getStaticHtml);
	}

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
		if(!file_exists($fileName)){
			$e = new SmallMVCException("display:$fileName", PAGE_NOT_FOUND);
			throw $e;
		}
		if(!empty($layoutName) && !file_exists($layoutName)){
			$e = new SmallMVCException("display:$fileName", PAGE_NOT_FOUND);
			throw $e;
		}
		//assign framework pre-defined variables
		if(!empty($_SERVER['SCRIPT_NAME'])){
			$controllerName = get_class(SMvc::instance(null,'controller'));
			$controllerName = preg_replace('/(.*)Controller$/i', '$1', $controllerName);
			$this->assign('_entry_', preg_replace('/^\/(.*)/', '$1' , $_SERVER['SCRIPT_NAME']));
			$this->assign('_controller_', preg_replace('/^\/(.*)/', '$1' , $_SERVER['SCRIPT_NAME'] . '/' . $controllerName));
		}
		if(!strlen(APPDIR)) $this->assign('_appdir_', '.');
		else $this->assign('_appdir_', APPDIR);
		if(!strlen(PROJECT_DIR)) $this->assign('_webroot_', '.');
		else $this->assign('_webroot_', PROJECT_DIR);
		$cacheFile = SMvc::instance(null, 'default')->config['project']['directory']['cache'] . DS . md5($fileName) . '.html';
		//see if cache file is up to date
		if (filemtime($fileName) > filemtime($cacheFile) || filemtime($layoutName) > filemtime($cacheFile)){
			$content = file_get_contents($fileName, LOCK_EX);
			if(!empty($layoutName)){
				$layoutContent = file_get_contents($layoutName, LOCK_EX);
				if(preg_match("/^{__LAYOUT__}/", $layoutContent)){
					$layoutContent = str_replace("{__LAYOUT__}", "", $layoutContent);
					$content = str_replace("{__CONTENT__}", $content, $layoutContent);
				}else{
					$e = new SmallMVCException("Not a layout file:$layout", DEBUG);
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
			$content = implode("\n", $newLines);
			if(!file_put_contents($cacheFile, $content, LOCK_EX)){
				$e = new SmallMVCException("can not wirte content to cache/ directroy", DEBUG);
				throw $e;
			}
		}
		$content = "";
		ob_start();
		require_once($cacheFile);
		$content = ob_get_contents();
		ob_end_clean();
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

	private function transformSyntax($input) {
		$from = array(
			'/(^|\[|,|\(|\+| )([a-zA-Z_][a-zA-Z0-9_]*)($|\.|\)|\[|\]|\+)/',
			'/(^|\[|,|\(|\+| )([a-zA-Z_][a-zA-Z0-9_]*)($|\.|\)|\[|\]|\+)/', // again to catch those bypassed by overlapping start/end characters 
			'/\./',
		);
		$to = array(
			'$1$this->data->$2$3',
			'$1$this->data->$2$3',
			'->'
		);

		$parts = explode(':', $input);
		$string = '<?php ';
		switch($parts[0]) {
			case 'if':
			case 'switch':
				$string .= $parts[0] . '(' . preg_replace($from, $to, $parts[1]) . ') { ' . ($parts[0] == 'switch' ? 'default: ' : '');
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
			case 'include':
				$string .= 'echo $this->display("' . $parts[1] . '");';
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
