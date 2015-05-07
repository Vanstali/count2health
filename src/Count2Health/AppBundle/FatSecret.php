<?php

namespace Count2Health\AppBundle;

use JMS\DiExtraBundle\Annotation as DI;
use Count2Health\AppBundle\FatSecret\OAuthBase;
use Count2Health\AppBundle\FatSecret\FatSecretException;
use Count2Health\UserBundle\Entity\User;
use Count2Health\AppBundle\Util\Memcache;

/**
 * @DI\Service("fatsecret")
 */
class FatSecret
{

    private $key;
    private $secret;
    private $base;
    private $cache;

    /**
     * @DI\InjectParams({
     *     "key" = @DI\Inject("%fatsecret_key%"),
     *     "secret" = @DI\Inject("%fatsecret_secret%"),
     *     "cache" = @DI\Inject("memcache")
     * })
     */
    public function __construct($key, $secret, Memcache $cache)
    {
        $this->key = $key;
        $this->secret = $secret;
        $this->base = 'http://platform.fatsecret.com/rest/server.api?';
        $this->cache = $cache;
    }

    public function doApiCall($method, array $arguments = array(), $category = 'DEFAULT', User $user = null)
    {
        $arguments['method'] = $method;

        // Does it exist in cache?
$namespace = $this->cache->getNamespace($category, $user);
$key = $namespace . http_build_query($arguments);

$shouldCache = false;

        if (!$response = $this->cache->get($key)) {
        $oauth_token = null;
        $oauth_secret = null;
        if (null !== $user) {
            $oauth_token = $user->getAuthToken();
            $oauth_secret = $user->getAuthSecret();
        }

        $url = $this->base .
            http_build_query($arguments);

$oauth = new OAuthBase;

$signature = $oauth->generateSignature($url, $this->key, $this->secret,
        $oauth_token, $oauth_secret, $normalizedUrl, $normalizedRequestParameters);

$response = $this->doRequest($normalizedUrl, $normalizedRequestParameters,
        $signature);

$shouldCache = true;
        }
        else {
if ($this->cache->get('cached-calls')) {
$this->cache->increment('cached-calls', 1);
}
else {
$this->cache->set('cached-calls', 1, 0, 0);
}
        }

$doc = new \SimpleXmlElement($response);

$this->checkError($doc);

if (true == $shouldCache) {
$this->cache->set($key, $response, MEMCACHE_COMPRESSED, 3600);
if ($this->cache->get('uncached-calls')) {
$this->cache->increment('uncached-calls', 1);
}
else {
$this->cache->set('uncached-calls', 1, 0, 0);
}
}

return $doc;
    }

    public function getRequestToken($callback)
{
    $arguments = array();
    $arguments['oauth_callback'] = $callback;

        $url = 'http://www.fatsecret.com/oauth/request_token?' .
            http_build_query($arguments);

$oauth = new OAuthBase;

$signature = $oauth->generateSignature($url, $this->key, $this->secret,
        null, null, $normalizedUrl, $normalizedRequestParameters);

$response = $this->doRequest($normalizedUrl, $normalizedRequestParameters,
        $signature);

parse_str($response, $tokens);

return $tokens;
}

    public function getAccessToken(User $user, $verifier)
{
    $arguments = array(
            'oauth_verifier' => $verifier,
            );

        $url = 'http://www.fatsecret.com/oauth/access_token?' .
            http_build_query($arguments);

$oauth = new OAuthBase;

$signature = $oauth->generateSignature($url, $this->key, $this->secret,
        $user->getRequestToken(), $user->getRequestSecret(), $normalizedUrl, $normalizedRequestParameters);

$response = $this->doRequest($normalizedUrl, $normalizedRequestParameters,
        $signature);

parse_str($response, $tokens);

return $tokens;
}

public function dateIntToDateTime($dateint, User $user)
{
    $ts = $dateint * 60 * 60 * 24;
    $d = new \DateTime();

    $timeZone = new \DateTimeZone($user->getSetting()->getTimeZone());
    $offset = $timeZone->getOffset($d);

        $ts -= $offset;

    $d->setTimestamp($ts);
    $d->setTimeZone($timeZone);

    return $d;
}

public function dateTimeToDateInt(\DateTime $date)
{
    $dt = clone $date;
    $offset = $dt->getTimeZone()->getOffset($dt);
    if ($offset < 0) {
        $dt->sub(new \DateInterval('PT'.abs($offset).'S'));
    }
    else {
        $dt->add(new \DateInterval('PT'.$offset.'S'));
    }

    $ts = $dt->getTimestamp();
    return floor($ts / 60 / 60 / 24);
}

private function doRequest($normalizedUrl, $normalizedRequestParameters, $signature)
{
 $ch = curl_init();
 curl_setopt($ch, CURLOPT_URL, $normalizedUrl);
 curl_setopt($ch, CURLOPT_HEADER, false);
 curl_setopt($ch, CURLOPT_POST, true);
 curl_setopt($ch, CURLOPT_POSTFIELDS, 
         $normalizedRequestParameters . '&' . OAuthBase::$OAUTH_SIGNATURE .
         '=' . urlencode($signature));
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         $response = curl_exec($ch);
         curl_close($ch);

return $response;
}

private function checkError(\SimpleXmlElement $doc)
{
if ($doc->getName() == 'error') {
    throw new FatSecretException((int)$doc->code, $doc->message);
}
}

}
