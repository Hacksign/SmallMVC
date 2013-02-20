<?php
define('DEBUG', 0);
define('ERROR', 1);
define('EXCEP', 2);
class SmallMVCExceptionHandler extends Exception{
  public static function handleException(Exception $e){
		$font_size = "15px";
		echo "<html> <head> <title>SmallMVC Errors</title> <style type=\"text/css\"> body {padding:0;margin:0;} .tnnd{width:100%;margin:0 auto;font-size:{$font_size};} /*for ff*/ html,body{height:100%;} .infoBox{text-align:center;width:100%;height:100%;display:table;} .info{display:table-cell;vertical-align:middle;} /*for IE6*/ *html .infoBox{position:absolute;top:50%;width:100%;text-align:center;display:block;height:auto} *html .info{position:relative;top:-50%;text-align:center;} /*for IE7*/ *+html .infoBox{position:absolute;top:50%;width:100%;text-align:center;display:block;height:auto} *+html .info{position:relative;top:-50%;text-align:center;} #footer{position:absolute;bottom:10px;text-align:center;width:100%;font-size:{$font_size};} </style> </head> <body> <div class='infoBox'> <div class='info'> <div class='tnnd'>";
		if(SMvc::instance(null,'default')->config['debug'])
			switch($e->type){
				case ERROR:
					echo "<span style='font-weight:bold;'>{$e->message}</span>";
					break;
				case EXCEP:
					echo "<p>{$e->message}</p> <p>File:{$e->file}</p> <p>Line:{$e->line}</p>";
					break;
				case DEBUG:
					echo "<p>{$e->message}</p> <p>File:{$e->file}</p> <p>Line:{$e->line}</p>";
					break;
				default:
					echo "<p>{$e->message}</p> <p>File:{$e->file}</p> <p>Line:{$e->line}</p>";
			}
		else{
			echo "Ops~.something is wrong!";
		}
		echo	"</div> </div> </div> </body> </html>";
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
