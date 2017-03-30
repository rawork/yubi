<?php

namespace Fuga\Component;


use Fuga\CommonBundle\Controller\Controller;
use Fuga\CommonBundle\Manager\ModelManager;
use Fuga\Component\Exception\NotFoundHttpException;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader as ServiceYamlFileLoader;

class Container 
{
	/**
	 * @var Controller[]
	 */
	protected $controllers = [];

	protected $services = [];

	/**
	 * @var ModelManager[]
	 */
	protected $managers = [];

	protected $loader;

	/**
	 * @var ServiceYamlFileLoader
	 */
	protected $locator;

	/**
	 * @var ContainerBuilder|null
	 */
	protected $container;
	
	public function __construct($loader)
	{
		$this->loader = $loader;
	}
	
	public function initialize()
	{
		$stmt = $this->get('connection')->query('SELECT name, value FROM variable');
		$stmt->execute();

		while ($var = $stmt->fetch()) {
			define($var['name'], $var['value']);
		}

		$this->getManager('Fuga:Common:Module')->getAll();
		$this->getManager('Fuga:Common:Table')->getAll();
	}

	public function backupDB($filename)
	{
		system('mysqldump -u '.DB_USER.' -p'.DB_PASS.' -h '.DB_HOST.' '.DB_BASE.' > '.$filename);

		return true;
	}
	
	public function getControllerClass($path)
	{
		if (count(explode(':', $path)) == 5) {
			list($vendor, $bundle, $subdir, $name) = explode(':', $path);

			return $vendor.'\\'.$bundle.'Bundle\\Controller\\'.$subdir.'\\'.ucfirst($name).'Controller';
		}

		list($vendor, $bundle, $name) = explode(':', $path);

		return $vendor.'\\'.$bundle.'Bundle\\Controller\\'.ucfirst($name).'Controller';
	}
	
	public function createController($path)
	{
		if (!isset($this->controllers[$path])) {
			$className = $this->getControllerClass($path);
			$this->controllers[$path] = new $className();
			$this->controllers[$path]->setContainer($this);
		}
		return $this->controllers[$path];
	}

	public function callAction($path, $options = array())
	{
		if (count(explode(':', $path)) == 5) {
			list($vendor, $bundle, $subdir, $name, $action) = explode(':', $path);
		} else {
			list($vendor, $bundle, $name, $action) = explode(':', $path);
		}

		$obj = new \ReflectionClass($this->getControllerClass($path));


		if (!$obj->hasMethod($action)) {
			$this->get('log')->addError('Не найден обработчик ссылки '.$path);
			throw new NotFoundHttpException(PRJ_ENV == 'dev'
				? 'Не найден обработчик ссылки '.$path.'.'
				: 'Несуществующая страница.'
			);
		}
		$params = [];
		$options['request'] = $this->get('request');
		foreach ($obj->getMethod($action)->getParameters() as $parameter) {
			$params[$parameter->getName()] = isset($options[$parameter->getName()])
				? $options[$parameter->getName()]
				: null;
			if (null === $params[$parameter->getName()] && !$parameter->allowsNull()) {
				throw new NotFoundHttpException(PRJ_ENV == 'prod'
					? 'Несуществующая страница.'
					: 'Не задан обязательный параметр '.$parameter->getName().' обработчика.'
				);
			}
			if ($parameter->getName() == 'options') {
				$params[$parameter->getName()] = $options;
			}
		}

		return $obj->getMethod($action)->invokeArgs($this->createController($path), $params);
	}

	public function register($name, $service)
	{
		$this->services[$name] = $service;

		$this->container->set($name, $service);

		return $service;
	}

	public function get($name)
	{
		if (!$this->container) {
			$this->container = new ContainerBuilder();
			$this->container->setParameter('global.prj_dir', PRJ_DIR);
			$this->container->setParameter('global.prj_ref', PRJ_REF);
			$this->container->setParameter('global.theme_ref', THEME_REF);
			$this->container->setParameter('global.upload_dir', UPLOAD_DIR);
			$this->container->setParameter('global.upload_ref', UPLOAD_REF);
			$this->container->setParameter('global.locale', PRJ_LOCALE);

			$this->container->setParameter('global.config_path', [$this->getBaseDir() . 'app/config']);

			$this->container->setParameter('log.error_path', PRJ_DIR.'app/logs/error.log');
			$this->container->setParameter('log.level', PRJ_ENV == 'dev' ? \Monolog\Logger::DEBUG : \Monolog\Logger::ERROR);

			$this->container->setParameter('router.options', [
				'cache_dir' => PRJ_ENV == 'dev' ? null : $this->getBaseDir().'/app/cache',
				'debug' => PRJ_ENV == 'dev'
			]);

			$this->container->setParameter('cache.driver', CACHE_DRIVER);
			$this->container->setParameter('cache.options', ['path' => CACHE_STORAGE, 'host' => CACHE_HOST, 'port' => CACHE_PORT]);

			$this->container->setParameter('mongo.user', MONGO_USER);
			$this->container->setParameter('mongo.pass', MONGO_PASS);
			$this->container->setParameter('mongo.host', MONGO_HOST);
			$this->container->setParameter('mongo.base', MONGO_BASE);

//			$appVariableReflection = new \ReflectionClass('\Symfony\Bridge\Twig\AppVariable');
//			$vendorTwigBridgeDir = dirname($appVariableReflection->getFileName());

			$this->container->setParameter('twig.paths', [
				PRJ_DIR.TWIG_PATH,
//				$vendorTwigBridgeDir.'/Resources/views/Form'
			]);
			$this->container->setParameter('twig.admin.path', PRJ_DIR.'src/Fuga/AdminBundle/Resources/views');
			$this->container->setParameter('twig.options', [
				'cache' => PRJ_DIR.TWIG_CACHE_PATH,
				'auto_reload' => true,
				'autoescape' => false,
				'debug' => PRJ_ENV == 'dev',
			]);

			$this->container->setParameter('em.dev_mode', 'dev' == PRJ_ENV);
			$this->container->setParameter('em.entities', [PRJ_DIR."src/Fuga/PublicBundle/Entity"]);
			$this->container->setParameter('database.options', [
				'dbname'	=> DB_BASE,
				'user'		=> DB_USER,
				'password'	=> DB_PASS,
				'host'		=> DB_HOST,
				'driver'	=> DB_TYPE,
				'charset'	=> 'utf8',
				'collate'   => 'utf8_unicode_ci',
				'driverOptions' => [ 1002 => 'SET NAMES utf8']
			]);

			$this->locator = new FileLocator(array($this->getBaseDir() . 'app/config'));
			$loader = new ServiceYamlFileLoader($this->container, $this->locator);
			$loader->load('services.yml');
		}

		if (!isset($this->services[$name])) {
			switch ($name) {
				case 'connection':
					\Doctrine\DBAL\Types\Type::addType('money', 'Fuga\Component\DBAL\Types\MoneyType');

					$this->services[$name] = $this->container->get($name);
					$this->services[$name]->getDatabasePlatform()->registerDoctrineTypeMapping('DECIMAL(14,2)', 'money');
					break;
				case 'odm':
					$connection = new \Doctrine\MongoDB\Connection(sprintf("mongodb://%s:%s@%s:%s/%s", MONGO_USER, MONGO_PASS, MONGO_HOST, MONGO_PORT, MONGO_BASE));
					$config = new \Doctrine\ODM\MongoDB\Configuration();
					
					$config->setProxyDir(__DIR__ . '/../../../app/cache/proxies');
					$config->setProxyNamespace('Proxies');
					$config->setHydratorDir(__DIR__ . '/../../../app/cache/hydrators');
					$config->setHydratorNamespace('Hydrators');
					$config->setAutoGenerateProxyClasses('dev' == PRJ_ENV);
					$config->setAutoGenerateHydratorClasses('dev' == PRJ_ENV);
					$config->setDefaultDB(MONGO_BASE);
					
					$config->setMetadataDriverImpl(\Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver::create(__DIR__ . '/../GameBundle/Document'));

					\Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver::registerAnnotationClasses();
					
					$this->services[$name] = \Doctrine\ODM\MongoDB\DocumentManager::create($connection, $config);
					break;
				case 'translator_local':
					$this->services[$name] = new Translator($this->get('session')->get('locale'));
					break;
				case 'search':
					$this->services[$name] = new Search\SearchEngine($this);
					break;
				case 'security':
					$this->services[$name] = $this->getManager('Fuga:Common:User');
					break;
				default:
					$this->container->setParameter('global.prj_name', defined('PRJ_NAME') ? PRJ_NAME : '');
					$this->container->setParameter('global.prj_zone', defined('PRJ_ZONE') ? PRJ_ZONE : '');

					$this->services[$name] = $this->container->get($name);
					break;
			}	
		}

		if (!isset($this->services[$name])) {
			throw new \Exception('Cлужба "'.$name.'" не найдена');
		}
		
		return $this->services[$name];
	}
	
	public function getManager($path)
	{
		if (!isset($this->managers[$path])) {
			list($vendor, $bundle, $name) = explode(':', $path);
			$className = '\\'.$vendor.'\\'.$bundle.'Bundle\\Manager\\'.ucfirst($name).'Manager';

			if (class_exists($className)) {
				$this->managers[$path] = new $className();
				$this->managers[$path]->setContainer($this);
			} else {
				throw new Exception('Менеджер "'.$className.'" не найден');
			}
		}

		return $this->managers[$path];
	}

	public function getBaseDir()
	{
		return PRJ_DIR;
	}
}
