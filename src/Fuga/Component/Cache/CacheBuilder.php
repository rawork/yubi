<?php

namespace Fuga\Component\Cache;

use Doctrine\Common\Cache\MemcachedCache;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\Common\Cache\FilesystemCache;

class CacheBuilder
{
	static public function hasOption($options, $name, $default = null) {
		if (array_key_exists($name, $options)
			&& !empty($options[$name])) {
			return $options[$name];
		} elseif ($default) {
			return $default;
		}

		return false;
	}

	static public function createCacheProvider($driver, $options)
	{
		switch ($driver) {
			case 'memcached':
				$memcached = new \Memcached();
				$memcached->addServer(self::hasOption($options, 'host', 'localhost'), self::hasOption($options, 'port', 11211));
				$cacheProvider = new MemcachedCache();
				$cacheProvider->setMemcached($memcached);
				break;
			case 'redis':
				$redis = new \Redis();
				$redis->connect(self::hasOption($options, 'host', 'localhost'), self::hasOption($options,'port', 6379));
				$cacheProvider = new RedisCache();
				$cacheProvider->setRedis($redis);
				break;
			case 'file':
			default:
				$cacheProvider = new FilesystemCache(self::hasOption($options, 'path'), '.cmscache.data');
		}

		$cacheProvider->setNamespace((defined('PRJ_NAME') ? PRJ_NAME : 'fuga').'_');

		return $cacheProvider;
	}
}