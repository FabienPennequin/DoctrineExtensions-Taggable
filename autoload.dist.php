<?php

/*
 * This file is part of the Doctrine Extensions Taggable package.
 * (c) 2011 Fabien Pennequin <fabien@pennequin.me>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (!class_exists('Symfony\\Component\\ClassLoader\\UniversalClassLoader')) {
    require __DIR__.'/vendor/Symfony/Component/ClassLoader/UniversalClassLoader.php';
}

$loader = new Symfony\Component\ClassLoader\UniversalClassLoader();
$loader->registerNamespaces(array(
    'Doctrine\\Common'              => __DIR__.'/vendor/doctrine/common/lib',
    'Doctrine\\DBAL'                => __DIR__.'/vendor/doctrine/dbal/lib',
    'Doctrine\\ORM'                 => __DIR__.'/vendor/doctrine/orm/lib',

    'DoctrineExtensions\\Taggable'  => __DIR__.'/lib',
));
$loader->register();
