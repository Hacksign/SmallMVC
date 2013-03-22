<?php
$config['routing']['search'] =  array();
$config['routing']['replace'] = array();
$config['routing']['controller'] = 'IndexController';
$config['routing']['action'] = 'index';
 
$config['system']['loader'] = 'SmallMVCLoader';
$config['system']['controller'] = 'SmallMVCController';
$config['system']['action'] = 'index';
$config['system']['model'] = 'SmallMVCModel';
$config['system']['viewer'] = array('SmallMVCViewer',array('sfsdfs'));
$config['system']['error'] = array('file' => 'SmallMVCErrorHandler', 'class' => array('SmallMVCExceptionHandler','handleException'), 'function' => 'SmallMVCErrorHandler', 'shutdown' => 'SmallMVCShutdownFunction');

//auto loads
$config['autoloads']['scripts'] = array($config['system']['controller'], 'SmallMVCFunctions', $config['system']['model']);
$config['autoloads']['libraries'] = array();
//$config['autoloads']['models'] = array('SmallMVCModel');

$config['charset'] = 'utf8';
?>
