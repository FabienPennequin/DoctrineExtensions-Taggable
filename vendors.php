<?php

set_time_limit(0);

$vendorDir = __DIR__.'/vendor';

$version = isset($_SERVER['DOCTRINE_VERSION']) ? 'origin/'.$_SERVER['DOCTRINE_VERSION'] : 'origin/master';

$deps = array(
    array('doctrine/common', 'https://github.com/doctrine/common.git', $version),
    array('doctrine/dbal', 'https://github.com/doctrine/dbal.git', $version),
    array('doctrine/orm', 'https://github.com/doctrine/doctrine2.git', $version),
);

foreach ($deps as $dep) {
    list($name, $url, $rev) = $dep;

    $installDir = $vendorDir.'/'.$name;
    $install = false;
    if (!is_dir($installDir)) {
        $install = true;
        echo "> Installing $name\n";

        system(sprintf('git clone %s %s', escapeshellarg($url), escapeshellarg($installDir)));
    }

    if (!$install) {
        echo "> Updating $name\n";
    }

    system(sprintf('cd %s && git fetch origin && git reset --hard %s', escapeshellarg($installDir), escapeshellarg($rev)));
}
