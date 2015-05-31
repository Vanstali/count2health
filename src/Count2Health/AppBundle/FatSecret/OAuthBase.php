<?php

namespace Count2Health\AppBundle\FatSecret;

class OAuthBase
{
    /* OAuth Parameters */
    public static $OAUTH_VERSION_NUMBER = '1.0';
    public static $OAUTH_PARAMETER_PREFIX = 'oauth_';
    public static $XOAUTH_PARAMETER_PREFIX = 'xoauth_';
    public static $PEN_SOCIAL_PARAMETER_PREFIX = 'opensocial_';

    public static $OAUTH_CONSUMER_KEY = 'oauth_consumer_key';
    public static $OAUTH_CALLBACK = 'oauth_callback';
    public static $OAUTH_VERSION = 'oauth_version';
    public static $OAUTH_SIGNATURE_METHOD = 'oauth_signature_method';
    public static $OAUTH_SIGNATURE = 'oauth_signature';
    public static $OAUTH_TIMESTAMP = 'oauth_timestamp';
    public static $OAUTH_NONCE = 'oauth_nonce';
    public static $OAUTH_TOKEN = 'oauth_token';
    public static $OAUTH_TOKEN_SECRET = 'oauth_token_secret';

    protected $unreservedChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_.~';

    public function GenerateSignature($url, $consumerKey, $consumerSecret, $token, $tokenSecret, &$normalizedUrl, &$normalizedRequestParameters)
    {
        $signatureBase = $this->GenerateSignatureBase($url, $consumerKey, $token, 'POST', $this->GenerateTimeStamp(), $this->GenerateNonce(), 'HMAC-SHA1', $normalizedUrl, $normalizedRequestParameters);
        $secretKey = $this->UrlEncode($consumerSecret).'&'.$this->UrlEncode($tokenSecret);

        return base64_encode(hash_hmac('sha1', $signatureBase, $secretKey, true));
    }

    private function GenerateSignatureBase($url, $consumerKey, $token, $httpMethod, $timeStamp, $nonce, $signatureType, &$normalizedUrl, &$normalizedRequestParameters)
    {
        $parameters = array();

        $elements = explode('?', $url);
        $parameters = $this->GetQueryParameters($elements[1]);

        $parameters[self::$OAUTH_VERSION] = self::$OAUTH_VERSION_NUMBER;
        $parameters[self::$OAUTH_NONCE] = $nonce;
        $parameters[self::$OAUTH_TIMESTAMP] = $timeStamp;
        $parameters[self::$OAUTH_SIGNATURE_METHOD] = $signatureType;
        $parameters[self::$OAUTH_CONSUMER_KEY] = $consumerKey;

        if (!empty($token)) {
            $parameters[ self::$OAUTH_TOKEN] = $token;
        }

        $normalizedUrl = $elements[0];
        $normalizedRequestParameters = $this->NormalizeRequestParameters($parameters);

        return $httpMethod.'&'.UrlEncode($normalizedUrl).'&'.UrlEncode($normalizedRequestParameters);
    }

    private function GetQueryParameters($paramString)
    {
        $elements = split('&', $paramString);
        $result = array();
        foreach ($elements as $element) {
            list($key, $token) = split('=', $element);
            if ($token) {
                $token = urldecode($token);
            }
            if (!empty($result[$key])) {
                if (!is_array($result[$key])) {
                    $result[$key] = array($result[$key],$token);
                } else {
                    array_push($result[$key], $token);
                }
            } else {
                $result[$key] = $token;
            }
        }

        return $result;
    }

    private function NormalizeRequestParameters($parameters)
    {
        $elements = array();
        ksort($parameters);

        foreach ($parameters as $paramName => $paramValue) {
            array_push($elements, $this->UrlEncode($paramName).'='.$this->UrlEncode($paramValue));
        }

        return implode('&', $elements);
    }

    private function UrlEncode($string)
    {
        $string = urlencode($string);
        $string = str_replace('+', '%20', $string);
        $string = str_replace('!', '%21', $string);
        $string = str_replace('*', '%2A', $string);
        $string = str_replace('\'', '%27', $string);
        $string = str_replace('(', '%28', $string);
        $string = str_replace(')', '%29', $string);

        return $string;
    }

    private function GenerateTimeStamp()
    {
        return time();
    }

    private function GenerateNonce()
    {
        return md5(uniqid());
    }
}
