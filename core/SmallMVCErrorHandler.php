<?php
define('DEBUG', 0);
define('ERROR', 1);
define('EXCEP', 2);
define('PAGE_NOT_FOUND', 3);
define('FILE_NOT_FOUND', 3);
class SmallMVCException extends Exception{
	var $type = null;
	
	function __construct($description, $type = null){
		if(!empty($type))
			$this->type = $type;
		parent::__construct($description);
	}	
}
class SmallMVCExceptionHandler extends Exception{
	private static function showTracePage($e, $controller){
		$backtrace = $e->getTrace();
		for($i = 0; $i < count($backtrace); $i++){
			if(!empty($backtrace[$i + 1])){
				$backtrace[$i]['function'] = $backtrace[$i + 1]['function'];
				if(empty($backtrace[$i]['file']))
					$backtrace[$i]['file'] = $backtrace[$i + 1]['file'];
				if(empty($backtrace[$i]['line']))
					$backtrace[$i]['line'] = $backtrace[$i + 1]['line'];
			}else
				$backtrace[$i]['function'] = 'Entry';
		}
		$controller->assign('backtrace', $backtrace);
		$controller->assign('info', $e->message);
		$controller->display('#.backtrace');
	}
  public static function handleException(SmallMVCException $e){
		SMvc::instance(new stdClass(), '_SMVC_IN_EXCEPTION');
		$controller = SMvc::instance(null, 'controller');
		if(!$controller){
			$controller = SMvc::instance(null, 'default')->config['system']['controller'];
			$controller = SMvc::instance(null, 'loader')->library($controller);
		}
		switch($e->type){
			case FILE_NOT_FOUND:
			case PAGE_NOT_FOUND:
				if(SMvc::instance(null, 'default')->config['debug']){
					SmallMVCExceptionHandler::showTracePage($e, $controller);
				}else{
					if(!empty(SMvc::instance(null, 'default')->config['project']['page']['404'])){
						$controller->display(SMvc::instance(null, 'default')->config['project']['page']['404']);
					}else{
						$controller->assign('info', $e->message."<br/>404 - NOT FOUND ERROR :(");
						$controller->display('#.message');
					}
				}
				break;
			case DEBUG:
			default:
				if(SMvc::instance(null, 'default')->config['debug']){
					SmallMVCExceptionHandler::showTracePage($e, $controller);
				}else{
					if(!empty(SMvc::instance(null, 'default')->config['project']['page']['error'])){
						$controller->display(SMvc::instance(null, 'default')->config['project']['page']['error']);
					}else{
						$controller->assign('info', "Ops~.something is wrong!");
						$controller->display('#.message');
					}
				}
				break;
		}
		SMvc::$scriptExecComplete = true;
	}
}
function SmallMVCErrorHandler($errno, $errstr, $errfile, $errline){
	SMvc::instance(new stdClass(), '_SMVC_IN_EXCEPTION');
	if(error_reporting() === 0){
		return;
	}
	if(error_reporting() & $errno){
		$controller = SMvc::instance(null, 'controller');
		if(empty($controller)){
			$controller = SMvc::instance(null, 'loader')->library(SMvc::instance(null, 'default')->config['system']['controller']);
		}
		switch($errno){
			case E_WARNING:
				break;
			case E_PARSE:
				break;
			case E_NOTICE:
				break;
			case E_CORE_ERROR:
				break;
			case E_CORE_WARNING:
				break;
			case E_COMPILE_ERROR:
				break;
			case E_USER_ERROR:
				break;
			case E_USER_WARNING:
				break;
			case E_USER_NOTICE:
				break;
			case E_STRICT:
				break;
			case E_ALL:
				break;
			default:
				if(SMvc::instance(null,'default')->config['debug']){
					$message = "<span style='text-align: left; border: 1px solid black; color: black; display: block; margin: 1em 0; padding: .33em 6px'>
								<b>Message:</b> {$errstr}<br />
								<b>File:</b> {$errfile}<br />
								<b>Line:</b> {$errline}
								</span>";
				}else{
					$message = "Fatal Erro !Please check your log file!";
				}
				break;
		}
		if(!SMvc::$scriptExecComplete && !empty($message)){
			$controller->assign('info', $message);
			$controller->display('#.message');
		}
	}
}
function SmallMVCShutdownFunction(){
	$controller = SMvc::instance(null, 'controller');
	if(empty($controller)){
		$controller = SMvc::instance(null, 'loader')->library(SMvc::instance(null, 'default')->config['system']['controller']);
	}
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
	if($e && !SMvc::$scriptExecComplete){
		$controller->assign('info', $message);
		$controller->display('#.message');
	}
}
?>
