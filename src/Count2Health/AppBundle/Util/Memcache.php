<?php

namespace Count2Health\AppBundle\Util;

use JMS\DiExtraBundle\Annotation as DI;
use Count2Health\UserBundle\Entity\User;

/**
 * @DI\Service("memcache")
 */
class Memcache
{

private $cache;
private $prefix;

public function __construct()
{
$this->prefix = 'c2h:';
$this->cache = new \Memcache;
$this->cache->connect('127.0.0.1');
}

public function __destruct()
{
$this->cache->close();
}

public function add($key, $value, $flags = MEMCACHE_COMPRESSED, $expiration = 3600)
{
if (false !== $flags) {
$compress = is_bool($value) || is_int($value) || is_float($value) ? false : MEMCACHE_COMPRESSED;
}
else {
$compress = false;
}

return $this->cache->add($this->prefix . $key, $value, $compress, $expiration);
}

public function set($key, $value, $flags = MEMCACHE_COMPRESSED, $expiration = 3600)
{
if (false !== $flags) {
$compress = is_bool($value) || is_int($value) || is_float($value) ? false : MEMCACHE_COMPRESSED;
}
else {
$compress = false;
}

return $this->cache->set($this->prefix . $key, $value, $compress, $expiration);
}

public function get($key)
{
return $this->cache->get($this->prefix . $key);
}

public function increment($key, $value = 1)
{
return $this->cache->increment($this->prefix . $key, $value);
}

public function decrement($key, $value = 1)
{
return $this->cache->decrement($this->prefix . $key, $value);
}

public function flush()
{
return $this->cache->flush();
}

public function getNamespace($subNamespace, User $user = null)
{
if (null == $user) {
$id = 'GLOBAL';
}
elseif (null == $user->getAuthToken()) {
$id = $user->getId();
}
else {
$id = $user->getAuthToken();
}

$key = 'namespace:' . $id . ':' . $subNamespace;
$namespace = $this->get($key);
if (!$namespace) {
$namespace = uniqid("$id:$subNamespace:");
if (!$this->add($key, $namespace, MEMCACHE_COMPRESSED, 0)) {
// Already been added
$namespace = $this->get($key);
}
}

return $namespace . ':';
}

public function invalidateNamespace($subNamespace, User $user = null)
{
if (null == $user) {
$id = 'GLOBAL';
}
elseif (null == $user->getAuthToken()) {
$id = $user->getId();
}
else {
$id = $user->getAuthToken();
}

$key = 'namespace:' . $id . ':' . $subNamespace;
$this->set($key, uniqid("$id:$subNamespace:"), MEMCACHE_COMPRESSED, 0);
}

}
