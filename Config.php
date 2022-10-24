<?php
//Ключ защиты
if(!defined('SITE_KEY'))
{
    header("HTTP/1.1 404 Not Found");
    exit(file_get_contents('./views/404.html'));
}

return [
    'db_name' => 'db1',
    'db_host' => 'localhost',
    'db_port' => '5432',
    'db_user' => 'login',
    'db_pass' => 'pass',
];

