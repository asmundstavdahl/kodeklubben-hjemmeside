<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Composer\Autoload\ClassLoader;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * @var ClassLoader $loader
 */
$loader = require __DIR__.'/../vendor/autoload.php';

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

$kernel = new AppKernel('test', true); // create a "test" kernel
$kernel->boot();

$application = new Application($kernel);
$application->setAutoExit(false);

TestDataManager::deleteDatabase();
TestDataManager::executeCommand($application, "doctrine:schema:create");
TestDataManager::executeCommand($application, "doctrine:fixtures:load");
TestDataManager::backupDatabase();

TestDataManager::$kernel = $kernel;

class TestDataManager
{
    public static $kernel;

    /**
     * TestDataManager constructor.
     */
    private function __construct()
    {
    }

    public static function executeCommand(Application $application, $command, Array $options = array()) {
        $options["--env"] = "test";
        $options["--quiet"] = true;
        $options = array_merge($options, array('command' => $command));
        $arrayInput = new ArrayInput($options);
        $arrayInput->setInteractive(false);
        $application->run($arrayInput);
    }

    public static function deleteDatabase() {
        $folder = __DIR__ . '/cache/test/';
        foreach(array('test.db','test.db.bk') AS $file){
            if(file_exists($folder . $file)){
                unlink($folder . $file);
            }
        }
    }

    public static function backupDatabase() {
        copy(__DIR__ . '/cache/test/test.db', __DIR__ . '/cache/test/test.db.bk');
    }

    public static function restoreDatabase() {
        copy(__DIR__ . '/cache/test/test.db.bk', __DIR__ . '/cache/test/test.db');
    }
}
