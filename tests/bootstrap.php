<?php
/**
 * This file is part of the BEAR.AclResourceModule package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
use Doctrine\Common\Annotations\AnnotationRegistry;

$loader = require dirname(__DIR__) . '/vendor/autoload.php';
AnnotationRegistry::registerLoader('class_exists');

$rm = function ($dir) use (&$rm) {
    foreach (glob($dir . '/*') as $file) {
        is_dir($file) ? $rm($file) : unlink($file);
        @rmdir($file);
    }
};
$rm(__DIR__ . '/tmp');
