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

function printDebug($str) {
    if (isset($_GET['debug'])) {
        echo $str;
    }
}

///////////////////////////////////////////
///  Config del DB
//////////////////////////////////////////

if (isset($_GET['host'])
    && isset($_GET['db'])
    && isset($_GET['user'])
    && isset($_GET['pass'])
    && isset($_GET['web_unsecure_base_url'])
    && isset($_GET['web_secure_base_url'])
    && isset($_GET['web_cookie_cookie_domain'])) {

    printDebug('Mi preparo per fixare il DB');

    $host = $_GET['host'];
    $db = $_GET['db'];
    $user = $_GET['user'];
    $pass = $_GET['pass'];

    try {
        $pdo = new PDO('mysql:host='.$host.';dbname='.$db.'', $user, $pass);

        if (isset($_GET['web_unsecure_base_url'])) {
            printDebug('Cambio web unsecure base URL');
            $unsecureBaseUrl = $_GET['web_unsecure_base_url'];
            if ($pdo->query("UPDATE core_config_data SET value = '$unsecureBaseUrl' WHERE path LIKE 'web/unsecure/base_url'")) {
                printDebug('Web unsecure base URL cambiato in ' . $unsecureBaseUrl);
            } else {
                printDebug('Web unsecure base URL NON cambiato in ' . $unsecureBaseUrl);
            }
        }

        if (isset($_GET['web_secure_base_url'])) {
            printDebug('Cambio web secure base URL');
            $secureBaseUrl = $_GET['web_secure_base_url'];
            if ($pdo->query("UPDATE core_config_data SET value = '$secureBaseUrl' WHERE path LIKE 'web/secure/base_url'")) {
                printDebug('Web secure base URL cambiato in ' . $secureBaseUrl);
            } else {
                printDebug('Web secure base URL NON cambiato in ' . $secureBaseUrl);
            }
        }

        if (isset($_GET['web_cookie_cookie_domain'])) {
            printDebug('Cambio web cookie domain');
            $cookieDomain = $_GET['web_cookie_cookie_domain'];
            if ($pdo->query("UPDATE core_config_data SET value = '$cookieDomain' WHERE path LIKE 'web/cookie/cookie_domain'")) {
                printDebug('Cookie domain cambiato in ' . $cookieDomain);
            } else {
                printDebug('Cookie domain NON cambiato in ' . $cookieDomain);
            }
        }
    } catch (Exception $e) {
        printDebug('Eccezione ' . $e->getMessage());
    }

    $pdo = null;
}

///////////////////////////////////////////
///  Config dell'ambiente
//////////////////////////////////////////

if (isset($_GET['webroot']) && isset($_GET['weburl'])) {
    $webroot = $_GET['webroot'];
    $weburl =  $_GET['weburl'];

    if (!file_exists($webroot)) {
        mkdir($webroot);
        printDebug('Creata directory ' . $webroot);
    }

    // Entro nella webroot
    chdir($webroot);
    // Sto in /var/www/WEBROOT

    // Se è previsto un file localxml, lo butto in app/etc/
    if (isset($_GET['localxml'])) {
        $localXml = file_get_contents($_GET['localxml']);
        file_put_contents("app/etc/local.xml", $localXml);
        printDebug('Caricato il file localxml');
    }

    // Se è previsto un archivio media
    if (isset($_GET['media'])) {
        if (!file_exists("media")) {
            mkdir("media");
            printDebug('Creata cartella media');
        }

        chdir("media");
        // Entrato in media, sto in /var/www/WEBROOT/media

        $media = file_get_contents($_GET['media']);
        file_put_contents("media.zip", $media);
        shell_exec("unzip media.zip");
        printDebug('Popolata cartella media');

        chdir("../");
        // Uscito da media, sto in /var/www/WEBROOT
    }

    if (isset($_GET['install-n98'])) {
        $magerun = file_get_contents("https://files.magerun.net/n98-magerun.phar");
        file_put_contents("n98-magerun.phar", $magerun);

        printDebug('Installato n98');
    }

    if (isset($_GET['flush-cache'])) {
        shell_exec('php n98-magerun.phar cache:clean');
        shell_exec('php n98-magerun.phar cache:flush');

        printDebug('Flushata cache');
    }

    chdir("../");
    // Uscito dalla webroot, sto in /var/www

    $template = file_get_contents("magento.conf.tpl");
    $template = str_replace("{{SERVER_NAME}}", $weburl, $template);
    $template = str_replace("{{FOLDER_NAME}}", $webroot, $template);

    printDebug('Creato file di config');

    chdir("sites-available");
    // Sto in /var/www/sites-available
    file_put_contents($webroot . ".conf", $template);
    printDebug('Piazzato file di config');

}