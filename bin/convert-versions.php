<?php
require_once __DIR__ ."/../vendor/autoload.php";

use HelmManipulator\Console\HelmChartsManipulationCommand;
use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new HelmChartsManipulationCommand());

$application->run();
