<?php
define('DEBUG', 0);
define('ERROR', 1);
define('EXCEP', 2);
define('PAGE_NOT_FOUND', 3);
class SmallMVCException extends Exception{
	var $type = null;
	
	function __construct($description, $type = null){
		if(!empty($type))
			$this->type = $type;
		parent::__construct($description);
	}	
}
class SmallMVCExceptionHandler extends Exception{
  public static function handleException(SmallMVCException $e){
		$controller = SMvc::instance(null, 'controller');
		if(SMvc::instance(null, 'default')->config['debug']){
			switch($e->type){
				case PAGE_NOT_FOUND:
					$controller->assign('info', $e->message."<br/>404 - PAGE NOT FOUND :(");
					$controller->display('#.message');
					break;
				case DEBUG:
					$controller->assign('backtrace', $e->getTrace());
					$controller->assign('info', $e->message);
					$controller->display('#.backtrace');
					break;
			}
		}else{
			$controller->assign('info', "Ops~.something is wrong!");
			$controller->display('#.message');
		}
	}
}
function SmallMVCErrorHandler($errno, $errstr, $errfile, $errline){
	if(error_reporting() === 0){
		return;
	}
	if(error_reporting() & $errno){
	}
}
function SmallMVCShutdownFunction(){
	$controller = SMvc::instance(null, 'controller');
	$e = error_get_last();
	if($e && SMvc::instance(null,'default')->config['debug']){
		$message = "<span style='text-align: left; border: 1px solid black; color: black; display: block; margin: 1em 0; padding: .33em 6px'>
					<b>Message:</b> {$e['message']}<br />
					<b>File:</b> {$e['file']}<br />
					<b>Line:</b> {$e['line']}
					</span>";
	}else if($e){
		$message = "Fatal Erro !Please check your log file!";
	}
	$controller->assign('info', $message);
	$controller->display('#.message');
}
?>
