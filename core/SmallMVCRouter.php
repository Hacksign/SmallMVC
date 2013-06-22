<?php
//$urlSegment array structure
//$urlSegment[0] => not defined reserved
//$urlSegment[1] => Controller Name(without .php suffix)
//$urlSegment[2] => Action Name
class SmallMVCRouter{
	private $SMVCOBJ = null;
	function __construct(){
		$this->SMVCOBJ = SMvc::instance(null, 'default');
		switch($this->SMVCOBJ->config['routing']['type']){
			case 'troditional':
				$this->troditional();
				break;
			case 'urlroute':
			default:
				$this->urlroute();
		}
	}
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
	private function urlroute(){
		$url = !empty($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '/'.$this->SMVCOBJ->config['system']['controller'].'/'.(!empty($this->SMVCOBJ->config['routing']['action']) ? $this->SMVCOBJ->config['routing']['action'] : $this->SMVCOBJ->config['system']['action']);
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
				else
					$_GET[$key] = null;
			}
		}else $this->SMVCOBJ->urlSegments = array(1 => $this->SMVCOBJ->config['routing']['controller'], 2 => $this->SMVCOBJ->config['routing']['action']);
	}
}
?>
