<?php
/**
 * Bootstrap file that initiates Autoloader
 */
$vendorPath = realpath(__DIR__.'/../vendor');
require_once $vendorPath.'/Symfony/Component/ClassLoader/UniversalClassLoader.php';

use Symfony\Component\ClassLoader\UniversalClassLoader;

// Register autoloader
$loader = new UniversalClassLoader();
$loader->registerNamespaces(array(
	'Symfony' => $vendorPath,
	'Application' => __DIR__
));
$loader->register();
?>