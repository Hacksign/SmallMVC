<?php
define('DEBUG', 0);
define('ERROR', 1);
define('EXCEP', 2);
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
		if(SMvc::instance(null, 'default')->config['debug']){
			$controller = SMvc::instance(null, 'controller');
			$controller->assign('info', $e->message);
			$controller->assign('backtrace', $e->getTrace());
			$controller->display('#.backtrace');
		}else{
			echo "Ops~.something is wrong!";
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
	$e = error_get_last();
	if($e && SMvc::instance(null,'default')->config['debug']){
		echo "<span style='text-align: left; border: 1px solid black; color: black; display: block; margin: 1em 0; padding: .33em 6px'>
					<b>Message:</b> {$e['message']}<br />
					<b>File:</b> {$e['file']}<br />
					<b>Line:</b> {$e['line']}
					</span>";
	}else if($e){
		echo "Fatal Erro !Please check your log file!";
	}
}
?>
