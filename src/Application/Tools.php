<?php
namespace Application;

use Symfony\Component\Console\Application;

/**
 * Tools Application
 * A "container" for many CLI commands
 */
class Tools extends Application {
    public function __construct() {
        parent::__construct('AppFlower Tools');
 
        $this->addCommands(array(
            new Command\PackageRelease()
        ));
    }
}
