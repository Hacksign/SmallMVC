<?php
/**
 * 项目配置文件.
 */
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

/*
 * //Database config example section 
 * $config['database']['plugin'] = 'database.SmallMVCDriverPDO'; // plugin for db access
 * // default param passed to model which created with M() method is array($tableName, $poolName, $your_arams ...) this configuration set start position in above array which pass to your model class
 * $config['database']['first_param_position'] = 0;
 * $config['database']['type'] = 'mysql';      // connection type
 * $config['database']['host'] = 'localhost';  // db hostname
 * $config['database']['port'] = '3306';  // db port
 * $config['database']['name'] = 'dbname';     // db name
 * $config['database']['user'] = 'dbuser';     // db username
 * $config['database']['pass'] = 'dbpass';     // db password
 * $config['database']['persistent'] = false;  // db connection persistence?
 * $config['database']['charset'] = $config['charset'];  // connection charset
 * */
?>
