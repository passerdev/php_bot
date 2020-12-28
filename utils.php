<?php

$config = file_get_contents(__DIR__ . '/config.json');
$config = json_decode($config, true);
function getConfig() {
    global $config;
    return $config;
}