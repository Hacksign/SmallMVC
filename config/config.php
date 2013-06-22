<?php
$config['routing']['search'] =  array();
$config['routing']['replace'] = array();
$config['routing']['controller'] = 'IndexController';
$config['routing']['action'] = 'index';
//type:
//			urlroute
//			troditional
//$config['routing']['type'] = 'troditional';
$config['routing']['type'] = 'urlroute';
 
$config['system']['loader'] = 'SmallMVCLoader';
$config['system']['router'] = 'SmallMVCRouter';
$config['system']['controller'] = 'SmallMVCController';
$config['system']['action'] = $config['routing']['action'];
$config['system']['model'] = 'SmallMVCModel';
$config['system']['viewer'] = array('SmallMVCViewer',array());
$config['system']['error'] = array('file' => 'SmallMVCErrorHandler', 'class' => array('SmallMVCExceptionHandler','handleException'), 'function' => 'SmallMVCErrorHandler', 'shutdown' => 'SmallMVCShutdownFunction');
//$config['project']['page']['404'] = 'message.html'; //add this line to your project config.php to display an user defined 404 page. DO *NOT* uncomment this line in this file !
$config['project']['directory']['config'] = $_SERVER['DOCUMENT_ROOT'].WEB_ROOT.DS.APPDIR.DS.'config';
$config['project']['directory']['controller'] = $_SERVER['DOCUMENT_ROOT'].WEB_ROOT.DS.APPDIR.DS.'controller';
$config['project']['directory']['model'] = $_SERVER['DOCUMENT_ROOT'].WEB_ROOT.DS.APPDIR.DS.'model';
$config['project']['directory']['view'] = $_SERVER['DOCUMENT_ROOT'].WEB_ROOT.DS.APPDIR.DS.'view';
$config['project']['directory']['cache'] = $_SERVER['DOCUMENT_ROOT'].WEB_ROOT.DS.APPDIR.DS.'cache';
$config['project']['directory']['plugins'] = $_SERVER['DOCUMENT_ROOT'].WEB_ROOT.DS.APPDIR.DS.'plugins';

//auto loads
$config['autoloads']['scripts'] = array($config['system']['controller'], 'SmallMVCFunctions', $config['system']['model']);
$config['autoloads']['libraries'] = array($config['system']['router']);
$config['autoloads']['models'] = array();

$config['charset'] = 'utf8';
?>
