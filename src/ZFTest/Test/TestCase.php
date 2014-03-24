<?php
namespace ZFTest\Test;

use Doctrine\ORM\Tools\SchemaTool;
use Zend\ServiceManager\ServiceManager;
use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\Mvc\MvcEvent;
use Doctrine\ORM\EntityManager;
use RuntimeException;

chdir(__DIR__.'/../../../../../../');

/**
 * Class TestCase
 * @package ZFTest\Test
 */
class TestCase extends \PHPUnit_Framework_TestCase {

    /**
     * @var Zend\ServiceManager\ServiceManager
     */
    protected $serviceManager;

    /**
     * @var EntityManager
     */
    protected $em;
    protected $modules;

    /**
     * setup
     */
    public function setup() {

        parent::setup();

        $pathDir = getcwd()."/";
        $config = include $pathDir.'config/application.config.php';

        $this->serviceManager = new ServiceManager(new ServiceManagerConfig(
            isset($config['service_manager']) ? $config['service_manager'] : array()
        ));
        $this->serviceManager->setService('ApplicationConfig', $config);
        $this->serviceManager->setFactory('ServiceListener', 'Zend\Mvc\Service\ServiceListenerFactory');

        $moduleManager = $this->serviceManager->get('ModuleManager');
        $moduleManager->loadModules();
        $this->routes = array();
        $this->modules = $moduleManager->getModules();
        foreach ($this->filterModules()  as $m) {
            $moduleConfig = include $pathDir.'module/' . ucfirst($m) . '/config/module.config.php';
            if (isset($moduleConfig['router'])) {
                foreach ($moduleConfig['router']['routes'] as $key => $name) {
                    $this->routes[$key] = $name;
                }
            }
        }
        $this->serviceManager->setAllowOverride(true);

        $this->application = $this->serviceManager->get('Application');
        $this->event = new MvcEvent();
        $this->event->setTarget($this->application);
        $this->event->setApplication($this->application)
            ->setRequest($this->application->getRequest())
            ->setResponse($this->application->getResponse())
            ->setRouter($this->serviceManager->get('Router'));

        $this->em = $this->serviceManager->get('Doctrine\ORM\EntityManager');

        foreach($this->filterModules() as $m)
            $this->createDatabase($m);

    }

    /**
     * filterModules
     * @return array
     */
    private function filterModules()
    {
        $pathDir = getcwd()."/";
        $config = include $pathDir.'config/test.config.php';

        $array = array();
        foreach($this->modules as $m) {
            if (! in_array($m, array_merge($config['exclude_modules'], array('DoctrineModule','DoctrineORMModule', 'ZFTest'))))
                $array[] = $m;
        }

        return $array;
    }

    /**
     * createDatabase
     * @param $module
     * @throws \InvalidArgumentException
     */
    public function createDatabase($module) {

        try{
            $this->tearDown();
            if (file_exists(getcwd().'/module/' . $module . '/config/module.config.php')) {

                $config = require getcwd().'/module/' . $module . '/config/module.config.php';

                $dh = $config['doctrine']['driver'][$module.'_driver']['paths'][0];

                if (is_dir($dh)){

                    $dir = opendir($dh);

                    $tool = new SchemaTool($this->getEm());

                    $class = array();
                    while (false !== ($filename = readdir($dir))) {
                        if (substr($filename,-4) == ".php") {

                            $class[] = $this->getEm()->getClassMetadata($module.'\\Entity\\'.str_replace('.php', '',$filename));
                        }
                    }

                    $tool->createSchema($class);

                }
            }else{
                throw new \InvalidArgumentException('Nenhum modulo adicionado');
            }

        }catch (RuntimeException $e){
            $this->tearDown();
            echo $e->getTraceAsString() ."\n\n".$e->getMessage();
            die;
        }


    }

    /**
     * tearDown
     */
    public function tearDown() {
        parent::tearDown();

        try{

            $module = $this->filterModules();

            foreach($module as $m){

                if (file_exists(getcwd().'/module/' . $m . '/config/module.config.php')) {

                    $config = require getcwd().'/module/' . $m . '/config/module.config.php';

                    $dh = $config['doctrine']['driver'][$m.'_driver']['paths'][0];

                    if (is_dir($dh)){

                        $dir = opendir($dh);

                        while (false !== ($filename = readdir($dir))) {
                            if (substr($filename,-4) == ".php") {

                                $tool = new SchemaTool($this->getEm());
                                $class = array(
                                    $this->getEm()->getClassMetadata($m.'\\Entity\\'.str_replace('.php', '',$filename))
                                );
                                $tool->dropSchema($class);
                            }
                        }

                    }
                }

            }


        }catch (\RuntimeException $e){
            echo $e->getTraceAsString() ."\n\n".$e->getMessage();
            die;
        }
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEm() {
        return $this->em = $this->serviceManager->get('Doctrine\ORM\EntityManager');
    }
}