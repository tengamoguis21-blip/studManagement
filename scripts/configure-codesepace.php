<?php

$name = getenv('CODESPACE_NAME');
if (!$name) {
    exit(0);
}
$domain = getenv('GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN') ?: 'app.github.dev';
$url = 'https://'.$name.'-8000.'.$domain;
$path = __DIR__.'/../.env';
if (!is_file($path)) {
    exit(0);
}
$contents = file_get_contents($path);
$line = 'APP_URL='.$url;
$contents = preg_match('/^APP_URL=/m', $contents)
    ? preg_replace('/^APP_URL=.*$/m', $line, $contents)
    : $contents."\n".$line."\n";
file_put_contents($path, $contents);
echo 'Application URL configured: '.$url.PHP_EOL;