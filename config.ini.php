<?php
//Configuration
$config = array();

//Default language id
$config['default_language_id'] = 1;

$config['version'] 		= '201801-1';

//data base config
$config['dbdriver'] 	= 'mysqli';
$config['tablepre']		= '';
$config['db']['master']['dbhost']       = 'localhost';
$config['db']['master']['dbport']       = '3306';
$config['db']['master']['dbuser']       = 'col_sofa';
$config['db']['master']['dbpwd']        = 'PjFdvsdyBu0iIBfj';
$config['db']['master']['dbname']       = 'col_tools';
$config['db']['master']['dbcharset']    = 'UTF-8';
$config['db']['slave']                  = $config['db']['master'];
$config['session_expire'] 	= 3600;
$config['lang_type'] 		= 'zh_cn';
$config['cookie_pre'] 		= '230B_';
$config['thumb']['cut_type'] = 'gd';
$config['thumb']['impath'] = '';

//debug
$config['debug'] 			= true;

return $config;

