<?php
defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));

require __DIR__ . '/../protected/config/settings.php';
require __DIR__ . '/../dependencies/yiisoft/yii/framework/yii.php';

Yii::$classMap = require __DIR__  . '/../dependencies/composer/autoload_classmap.php';

$oConsoleApp = Yii::createConsoleApplication(__DIR__ . '/config/console.php');
$oConsoleApp->commandRunner->addCommands(__DIR__ . '/commands/');
$oConsoleApp->run();