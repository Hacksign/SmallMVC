<?php
$config['debug'] = false;

$config['routing']['search'] =  array();
$config['routing']['replace'] = array();
$config['routing']['controller'] = 'IndexController';
$config['routing']['action'] = 'index';
//type:
//			pathinfo
//			troditional
//$config['routing']['type'] = 'troditional';
$config['routing']['type'] = 'pathinfo';
 
$config['system']['loader'] = 'SmallMVCLoader';
$config['system']['router'] = 'SmallMVCRouter';
$config['system']['controller'] = 'SmallMVCController';
$config['system']['action'] = $config['routing']['action'];
$config['system']['model'] = 'SmallMVCModel';
$config['system']['viewer'] = array('SmallMVCViewer',array());
$config['system']['error'] = array('file' => 'SmallMVCErrorHandler', 'class' => array('SmallMVCExceptionHandler','handleException'), 'function' => 'SmallMVCErrorHandler', 'shutdown' => 'SmallMVCShutdownFunction');
//$config['project']['page']['404'] = 'message.html'; //add this line to your project config.php to display an user defined 404 page. DO *NOT* uncomment this line in this file !
//$config['project']['page']['error'] = 'error.html'; //add this line to your project config.php to display an user defined error page. DO *NOT* uncomment this line in this file !
$config['project']['directory']['config'] = PROJECT_ROOT.DS.APPDIR.DS.'config';
$config['project']['directory']['controller'] = PROJECT_ROOT.DS.APPDIR.DS.'controller';
$config['project']['directory']['model'] = PROJECT_ROOT.DS.APPDIR.DS.'model';
$config['project']['directory']['view'] = PROJECT_ROOT.DS.APPDIR.DS.'view';
$config['project']['directory']['cache'] = PROJECT_ROOT.DS.APPDIR.DS.'cache';
$config['project']['directory']['plugins'] = PROJECT_ROOT.DS.APPDIR.DS.'plugins';

//auto loads
$config['autoloads']['scripts'] = array($config['system']['controller'], 'SmallMVCFunctions', $config['system']['model']);
$config['autoloads']['libraries'] = array($config['system']['router']);
$config['autoloads']['models'] = array();

$config['charset'] = 'utf8';
?>
