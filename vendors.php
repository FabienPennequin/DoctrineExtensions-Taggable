<?php

set_time_limit(0);

$deps = array(
    array('doctrine/common', 'https://github.com/doctrine/common.git', 'origin/2.1.x'),
    array('doctrine/dbal', 'https://github.com/doctrine/dbal.git', 'origin/2.1.x'),
    array('doctrine/orm', 'https://github.com/doctrine/doctrine2.git', 'origin/2.1.x'),
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
