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
 * @name DEBUG 异常类型DEBUG
 */
define('DEBUG', 0);
/**
 * @name ERROR 异常类型ERROR
 */
define('ERROR', 1);
/**
 * @name EXCEP 异常类型EXCEP
 */
define('EXCEP', 2);
/**
 * @name EXCEPTION_NOT_FOUND 异常类型NOT_FOUND,当任何东西未找到时,抛出此异常.
 */
define('EXCEPTION_NOT_FOUND', 3);
/**
 * @name EXCEPTION_ACCESS_DENIED 异常类型ACCESS_DENINED,访访问违例异常.
 */
define('EXCEPTION_ACCESS_DENIED', 4);
/**
 * @name EXCEPTION_LAYOUTFILE_ERROR 异常类型LAYOUTFILE_ERROR,模板文件有问题时抛出此异常.
 */
define('EXCEPTION_LAYOUTFILE_ERROR', 5);

/**
 * 框架异常类.
 *
 * @author Hacksign <evilsign@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT License
 * @category 框架核心文件
 */
class SmallMVCException extends Exception{
	var $type = null;
	
	function __construct($description, $type = null){
		if(!empty($type))
			$this->type = $type;
		parent::__construct($description);
	}	
}
/**
 * 框架异常处理类.
 *
 * @author Hacksign <evilsign@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT License
 * @category 框架核心文件
 */
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
  public static function handleException($e){
    SMvc::instance(new stdClass(), '_SMVC_EXCEPTION_PROCESSING');
		$controller = SMvc::instance(null, 'controller');
		if(!$controller){
			$controller = SMvc::instance(null, 'default')->config['system']['controller'];
			$controller = SMvc::instance(null, 'loader')->library($controller);
		}
		switch($e->type){
			case EXCEPTION_NOT_FOUND:
				if(SMvc::instance(null, 'default')->config['debug']){
					SmallMVCExceptionHandler::showTracePage($e, $controller);
				}else{
          if(!file_exists(SMvc::instance(null, 'default')->config['project']['directory']['cache'])){
            create_project_directory();
            $controller->display('#.welcome');
          }else if(!empty(SMvc::instance(null, 'default')->config['project']['page']['404'])){
						$controller->display(SMvc::instance(null, 'default')->config['project']['page']['404']);
					}else{
						$controller->assign('info', $e->message."<br/>404 - NOT FOUND ERROR :(");
						$controller->display('#.message');
					}
				}
				break;
			case DEBUG:
            case EXCEPTION_ACCESS_DENIED :
              $e->message = preg_replace('/(\/+)|(\\+)/', DS,$e->message);
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
/**
 * 框架异常处理函数.
 *
 * @author Hacksign <evilsign@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT License
 * @category 框架核心文件
 */
function SmallMVCErrorHandler($errno, $errstr, $errfile, $errline){
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
/**
 * 框架退出时调用的函数.
 *
 * @author Hacksign <evilsign@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT License
 * @category 框架核心文件
 */
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
