<?php
$config['routing']['search'] =  array();
$config['routing']['replace'] = array();
$config['routing']['default_controller'] = 'IndexController';
$config['routing']['default_action'] = 'index';
 
$config['default_controller'] = 'SmallMVCController';
$config['default_action'] = 'index';
$config['default_viewer'] = 'SmallMVCViewer';
$config['default_viewer_args'] = array();

$config['error_handler_class'] = 'SmallMVCErrorHandler';
$config['debug'] = false;

//auto loads
$config['autoloads']['scripts'] = array($config['default_controller'], 'SmallMVCFunctions', 'SmallMVCModel');
$config['autoloads']['libraries'] = array();
//$config['autoloads']['models'] = array('SmallMVCModel');

$config['default_charset'] = 'utf8';
?>
