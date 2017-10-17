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

///////////////////////////////////////////
///  Config del DB
//////////////////////////////////////////

$host = isset($_GET['host']) ? $_GET['host'] : 'mariadb';
$db = isset($_GET['db']) ? $_GET['db'] : 'db';
$user = isset($_GET['user']) ? $_GET['user'] : 'dna';
$pass = isset($_GET['pass']) ? $_GET['pass'] : 'secret';

$pdo = new PDO('mysql:host='.$host.';dbname='.$db.'', $user, $pass);

$unsecureBaseUrl = isset($_GET['web_unsecure_base_url']) ? $_GET['web_unsecure_base_url'] : 'http://magento.dev.it/';
$pdo->query("UPDATE core_config_data SET value = '$unsecureBaseUrl' WHERE path LIKE 'web/unsecure/base_url'");

$secureBaseUrl =  isset($_GET['web_secure_base_url']) ? $_GET['web_secure_base_url'] : 'http://magento.dev.it/';
$pdo->query("UPDATE core_config_data SET value = '$secureBaseUrl' WHERE path LIKE 'web/secure/base_url'");

$cookieDomain =  isset($_GET['web_cookie_cookie_domain']) ? $_GET['web_cookie_cookie_domain'] : '*.magento.dev.it/';
$pdo->query("UPDATE core_config_data SET value = '$cookieDomain' WHERE path LIKE 'web/cookie/cookie_domain'");

$pdo = null;

///////////////////////////////////////////
///  Config dell'ambiente
//////////////////////////////////////////

// Dovrebbe essere il nome della rootfoolder a partire da questo file
// dovrebbe esistere sicuramente
$webroot = isset($_GET['webroot']) ? $_GET['webroot'] : 'magento';
$weburl = isset($_GET['weburl']) ? $_GET['weburl'] : 'magento.dev.it';
chdir($webroot);

// Scarico il file localxml
$localXml = file_get_contents($_GET['localxml']);
file_put_contents("app/etc/local.xml", $localXml);

// Ed i file di media
if (!file_exists("media")) {
    mkdir("media");
}

chdir("media");

$media = file_get_contents($_GET['media']);
file_put_contents("media.zip", $media);
shell_exec("unzip media.zip");

chdir("../");

$magerun = file_get_contents("https://files.magerun.net/n98-magerun.phar");
file_put_contents("n98-magerun.phar", $magerun);

shell_exec('php n98-magerun.phar cache:clean');
shell_exec('php n98-magerun.phar cache:flush');

chdir("../"); // sto in /var/www

$template = file_get_contents("magento.conf.tpl");
$template = str_replace("{{SERVER_NAME}}", $weburl, $template);
$template = str_replace("{{FOLDER_NAME}}", $webroot, $template);

chdir("sites-available");

file_put_contents($webroot . ".conf", $template);