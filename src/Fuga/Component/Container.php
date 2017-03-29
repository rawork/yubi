<?php

namespace Fuga\Component;


use Fuga\Component\Exception\NotFoundHttpException;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Router;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Loader\YamlFileLoader;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader as ServiceYamlFileLoader;

class Container 
{
	protected $controllers = [];
	protected $services = [];
	protected $managers = [];
	protected $loader;
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

		$this->container = new ContainerBuilder();

		$this->container->setParameter('global.prj_dir', PRJ_DIR);
		$this->container->setParameter('global.locale', PRJ_LOCALE);
		$this->container->setParameter('global.config_path', array($this->getBaseDir() . 'app/config'));
		$this->container->setParameter('log.error_path', PRJ_DIR.'app/logs/error.log');
		$this->container->setParameter('log.level', PRJ_ENV == 'dev' ? \Monolog\Logger::DEBUG : \Monolog\Logger::ERROR);

		$this->locator = new FileLocator(array($this->getBaseDir() . 'app/config'));
		$loader = new ServiceYamlFileLoader($this->container, $this->locator);
		$loader->load('services.yml');

//		try {
//			var_dump($this->container->get('log'));
//		} catch (\Exception $e) {
//			var_dump($e->getMessage());
//		}

	}

	public function getItemsRaw($sql)
	{
		$ret = array();
		if (!preg_match('/(delete|truncate|update|insert|drop|alter)+/i', $sql)) {
			$stmt = $this->get('connection')->prepare($sql);
			$stmt->execute();
			$items = $stmt->fetchAll();
			foreach ($items as $item) {
				if (isset($item['id'])) {
					$ret[$item['id']] = $item;
				} else {
					$ret[] = $item;
				}
			}
		}
		return $ret;
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
		//$action .= 'Action';
		if (!$obj->hasMethod($action)) {
			$this->get('log')->addError('Не найден обработчик ссылки '.$path);
			throw new NotFoundHttpException(PRJ_ENV == 'dev'
				? 'Не найден обработчик ссылки '.$path.'.'
				: 'Несуществующая страница.'
			);
		}
		$params = array();
		foreach ($obj->getMethod($action)->getParameters() as $parameter) {
			$params[$parameter->getName()] = isset($options[$parameter->getName()])
				? $options[$parameter->getName()]
				: null;
			if (null === $params[$parameter->getName()] && !$parameter->allowsNull()) {
				throw new NotFoundHttpException(PRJ_ENV == 'dev'
					? 'Не задан обязательный параметр '.$parameter->getName().' обработчика.'
					: 'Несуществующая страница.'
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

		return $service;
	}

	public function get($name)
	{
		if (!isset($this->services[$name])) {
			switch ($name) {
				case 'log':
					$this->services[$name] = $this->container->get('log');
					break;
				case 'util':
					$this->services[$name] = $this->container->get('util'); //new Util($this->get('session')->get('locale', PRJ_LOCALE));
					break;
				case 'templating':
					$twigLoader = new \Twig_Loader_Filesystem(PRJ_DIR.TWIG_PATH);
					$twigLoader->addPath(PRJ_DIR.'src/Fuga/AdminBundle/Resources/views', 'Admin');
					$engine = new \Twig_Environment($twigLoader, array(
						'cache' => PRJ_DIR.TWIG_CACHE_PATH,
						'auto_reload' => true,
						'autoescape' => false,
						'debug' => PRJ_ENV == 'dev',
					));
					$engine->addExtension(new \Twig_Extension_StringLoader());
					$engine->addExtension(new \Twig_Extension_Debug());
					$engine->addExtension(new Twig\FugaExtension());
					$engine->addGlobal('prj_ref', PRJ_REF);
					$engine->addGlobal('theme_ref', THEME_REF);
					$engine->addGlobal('prj_name', defined('PRJ_NAME') ? PRJ_NAME : '');
					$engine->addGlobal('prj_zone', defined('PRJ_ZONE') ? PRJ_ZONE : '');
					$this->services[$name] = new Templating\TwigTemplating($engine, $this);
					break;
				case 'em':
					$isDevMode = 'dev' == PRJ_ENV;
					$config = \Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration(array(__DIR__."/../src/Fuga/GameBundle/Entity"), $isDevMode);
					$conn = array(
						'dbname'	=> DB_BASE,
						'user'		=> DB_USER,
						'password'	=> DB_PASS,
						'host'		=> defined('DB_HOST') ? DB_HOST : 'localhost',
						'driver'	=> defined('DB_TYPE') ? DB_TYPE : 'pdo_mysql',
						'charset'	=> 'utf8',
						'collate'   => 'utf8_unicode_ci',
						'driverOptions' => array(
							1002 => 'SET NAMES utf8'
						)
					);
					
					$this->services[$name] = \Doctrine\ORM\EntityManager::create($conn, $config);
					break;
				case 'connection':
					\Doctrine\DBAL\Types\Type::addType('money', 'Fuga\Component\DBAL\Types\MoneyType');
					$config = new \Doctrine\DBAL\Configuration();
					$conn = array(
						'dbname'	=> DB_BASE,
						'user'		=> DB_USER,
						'password'	=> DB_PASS,
						'host'		=> defined('DB_HOST') ? DB_HOST : 'localhost',
						'driver'	=> defined('DB_TYPE') ? DB_TYPE : 'pdo_mysql',
						'charset'	=> 'utf8',
						'collate'   => 'utf8_unicode_ci',
						'driverOptions' => array(
							1002=>'SET NAMES utf8'
						)
					);
					$this->services[$name] = \Doctrine\DBAL\DriverManager::getConnection($conn, $config);
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
				case 'mongo':
					$mongo = new \MongoClient(sprintf("mongodb://%s:%s@%s/%s", MONGO_USER, MONGO_PASS, MONGO_HOST, MONGO_BASE));
					$this->services[$name] = $mongo->selectDB(MONGO_BASE);
					break;
				case 'filestorage':
					$this->services[$name] = new Storage\FileStorage(UPLOAD_REF, UPLOAD_DIR);
					break;
				case 'imagestorage':
					$this->services[$name] = new Storage\ImageStorageDecorator($this->get('filestorage'));
					break;
				case 'translator':
					$this->services[$name] = new Translator($this->get('session')->get('locale'));
					break;
				case 'paginator':
					$this->services[$name] = new Paginator($this->get('templating'), $this);
					break;
				case 'mailer':
					$this->services[$name] = new Mailer\Mailer();
					break;
				case 'scheduler':
					$this->services[$name] = new Scheduler\Scheduler($this);
					break;
				case 'search':
					$this->services[$name] = new Search\SearchEngine($this);
					break;
				case 'routing':
					$requestContext = new RequestContext();
					$requestContext->fromRequest($this->get('request'));

					$router = new Router(
						new YamlFileLoader($this->locator),
						'routes.yml',
						array('cache_dir' => PRJ_ENV == 'dev' ? null :$this->getBaseDir().'/app/cache', 'debug' => PRJ_ENV == 'dev'),
						$requestContext
					);
					//$router->getRouteCollection()->addPrefix(PRJ_REF);
					$this->services[$name] = $router;
					break;
				case 'vk':
					$vk = new Social\VK\Auth($this, VK_APP_ID, VK_APP_SHARED_SECRET, VK_APP_REDIRECT_URI, VK_OAUTH_URL);
					$this->services[$name] = $vk;
					break;
				case 'fb':
					$fb = new Social\FB\Auth($this, FB_APP_ID, FB_APP_SHARED_SECRET, FB_APP_REDIRECT_URI, FB_OAUTH_URL);
					$this->services[$name] = $fb;
					break;
				case 'security':
					$this->services[$name] = $this->getManager('Fuga:Common:User');
					break;
				case 'fs':
					$this->services[$name] = new Filesystem();
					break;
				case 'cache':
					if (!defined('CACHE_DRIVER')) {
						throw new Exception('Не настроены параметры кеширующего сервера.');
					}
					$driver = CACHE_DRIVER;
					switch ($driver) {
						case 'memcached':
							$memcached = new \Memcached();
							$memcached->addServer(defined('CACHE_HOST') ? CACHE_HOST : 'localhost', defined('CACHE_PORT') ? CACHE_PORT : 11211);
							$cacheDriver = new \Doctrine\Common\Cache\MemcachedCache();
							$cacheDriver->setMemcached($memcached);

							break;
						case 'redis':
							$redis = new \Redis();
							$redis->connect(defined('CACHE_HOST') ? CACHE_HOST : 'localhost', defined('CACHE_PORT') ? CACHE_PORT : 6379);

							$cacheDriver = new \Doctrine\Common\Cache\RedisCache();
							$cacheDriver->setRedis($redis);
							break;
						case 'file':
						default:
							$cacheDriver = new \Doctrine\Common\Cache\FilesystemCache($this->getBaseDir().'/app/cache/fuga/', '.cmscache.data');
					}

					$cacheDriver->setNamespace((defined('PRJ_NAME') ? PRJ_NAME : 'fuga').'_');

					$this->services[$name] = $cacheDriver;
					break;
				case 'event':
					$dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();

					$this->services[$name] = $dispatcher;
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
