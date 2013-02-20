<?php

/**
 * database.php
 *
 * application database configuration
 *
 * @package		TinyMVC
 * @author		Monte Ohrt
 */

$config['default']['plugin'] = 'SmallMVCPDO'; // plugin for db access
$config['default']['type'] = 'mysql';      // connection type
$config['default']['host'] = 'localhost';  // db hostname
$config['default']['name'] = 'dbname';     // db name
$config['default']['user'] = 'dbuser';     // db username
$config['default']['pass'] = 'dbpass';     // db password
$config['default']['persistent'] = false;  // db connection persistence?
$config['default']['charset'] = $config['default_charset'];  // connection charset

?>
