<?php
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/Bot.php';
require_once __DIR__ . '/Logger.php';

$config = getConfig();
$logger = new Logger($config['logs_folder'], !$config['webhook']);
$bot = new Bot($config, $logger);
$bot->start();
