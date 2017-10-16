<?php

// shell_exec('
//      /usr/bin/php
//      /var/www/set-magento.php
//          host=mariadb
//          db=db
//          user=dna
//          pass=secret
//          web_unsecure_base_url=http://magento.dev.it
//          web_secure_base_url=http://magento.dev.it
//          web_cookie_cookie_domain=*.magento.dev.it
// ');

parse_str(implode('&', array_slice($argv, 1)), $_GET);

$host = $_GET['host'];
$db = $_GET['db'];
$user = $_GET['user'];
$pass = $_GET['pass'];

$pdo = new PDO('mysql:host='.$host.';dbname='.$db.'', $user, $pass);

$unsecureBaseUrl = $_GET['web_unsecure_base_url'];
$pdo->query("UPDATE core_confi_data SET value = '$unsecureBaseUrl' WHERE path LIKE 'web/unsecure/base_url'");

$secureBaseUrl = $_GET['web_secure_base_url'];
$pdo->query("UPDATE core_confi_data SET value = '$secureBaseUrl' WHERE path LIKE 'web/secure/base_url'");

$cookieDomain = $_GET['web_cookie_cookie_domain'];
$pdo->query("UPDATE core_confi_data SET value = '$cookieDomain' WHERE path LIKE 'web/cookie/cookie_domain'");

$pdo = null;

// Dovrebbe essere il nome della rootfoolder a partire da questo file
$webroot = $_GET['webroot'];
chdir($webroot);

$localXml = file_get_contents($_GET['localxml']);
file_put_contents("app/etc/local.xml", $localXml);

$media = file_get_contents($_GET['media']);
file_put_contents("media.zip", $media);

if (!file_exists("media")) {
    mkdir("media");
}

chdir("media");

shell_exec("unzip media.zip");

shell_exec('php n98-magerun.phar cache:clean');
shell_exec('php n98-magerun.phar cache:flush');