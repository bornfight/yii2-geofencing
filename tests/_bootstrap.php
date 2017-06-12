<?php
// This is global bootstrap for autoloading

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

$_SERVER['SCRIPT_FILENAME'] = __FILE__;
$_SERVER['SCRIPT_NAME'] = '/' . __DIR__;

require 'vendor/autoload.php';
require 'vendor/yiisoft/yii2/Yii.php';

Yii::setAlias('@degordian/geofencing', __DIR__ . '/../src');