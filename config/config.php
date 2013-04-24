<?php
$config['routing']['search'] =  array();
$config['routing']['replace'] = array();
$config['routing']['controller'] = 'IndexController';
$config['routing']['action'] = 'index';
 
$config['system']['loader'] = 'SmallMVCLoader';
$config['system']['controller'] = 'SmallMVCController';
$config['system']['action'] = $config['routing']['action'];
$config['system']['model'] = 'SmallMVCModel';
$config['system']['viewer'] = array('SmallMVCViewer',array('sfsdfs'));
$config['system']['error'] = array('file' => 'SmallMVCErrorHandler', 'class' => array('SmallMVCExceptionHandler','handleException'), 'function' => 'SmallMVCErrorHandler', 'shutdown' => 'SmallMVCShutdownFunction');
$config['system']['directory']['config'] = $_SERVER['DOCUMENT_ROOT'].WEB_ROOT.DS.APPDIR.DS.'config';
$config['system']['directory']['controller'] = $_SERVER['DOCUMENT_ROOT'].WEB_ROOT.DS.APPDIR.DS.'controller';
$config['system']['directory']['model'] = $_SERVER['DOCUMENT_ROOT'].WEB_ROOT.DS.APPDIR.DS.'model';
$config['system']['directory']['view'] = $_SERVER['DOCUMENT_ROOT'].WEB_ROOT.DS.APPDIR.DS.'view';
$config['system']['directory']['cache'] = $_SERVER['DOCUMENT_ROOT'].WEB_ROOT.DS.APPDIR.DS.'cache';
$config['system']['directory']['plugins'] = $_SERVER['DOCUMENT_ROOT'].WEB_ROOT.DS.APPDIR.DS.'plugins';

//auto loads
$config['autoloads']['scripts'] = array($config['system']['controller'], 'SmallMVCFunctions', $config['system']['model']);
$config['autoloads']['libraries'] = array();
//$config['autoloads']['models'] = array('SmallMVCModel');

$config['charset'] = 'utf8';
?>
