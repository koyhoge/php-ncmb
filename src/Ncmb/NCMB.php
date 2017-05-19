<?php

namespace Ncmb;

/**
 * NCMB class
 */
class NCMB
{
    /**
     * Constant for version string
     * @var string
     */
    const SDK_VERSION = 'ncmb-php-sdk-0.1.0';

    /**
     * API type
     * @var string
     */
    const T_API_COMMON = 'common';
    const T_API_SCRIPT = 'script';

    /**
     * @var default settings
     */
    private static $config = [
        'apiVersion' => '2013-09-01',
        'scriptApiVersion' => '2015-09-01',
        'apiHost' => 'mb.api.cloud.nifty.com',
        'scriptApiHost' => 'script.mb.api.cloud.nifty.com',
        'port' => 443,
        'protocol' => 'https',
        'signatureMethod' => 'HmacSHA256',
        'signatureVersion' => 2,
    ];

    /**
     * @var runtime context
     */
    private static $context = [
        'applicationKey' => '',
        'clientKey' => '',
    ];

    /**
     * Initialize NCMB environment
     *
     * @param string $appKey application key
     * @param string $clientKey client key
     * @param array $config config setting to override defaults
     */
    public static function initialize($appKey, $clientKey, array $config = [])
    {
        self::$config = array_merge(self::$config, $config);
        self::$context['applicationKey'] = $appKey;
        self::$context['clientKey'] = $clientKey;
    }

    /**
     * Get config value
     *
     * @param string $key key of config
     */
    public static function config($key = null)
    {
        if ($key === null) {
            return self::$config;
        }
        return self::$config[$key];
    }

    /**
     * Set config values
     * @param array $vals key/value of config
     */
    public static function setConfig(array $vals)
    {
        self::$config = array_merge(self::$config, $vals);
    }

    /**
     * Get application key
     * @return string application key
     */
    public static function applicationKey()
    {
        return self::$context['applicationKey'];
    }

    /**
     * Get client key
     * @return string client key
     */
    public static function clientKey()
    {
        return self::$context['clientKey'];
    }

    /**
     * Get URL of NCMB API from config
     * @param string service type 'script' or null
     * @return string URL string
     */
    public static function getApiUrl($type = null)
    {
        $protocol = self::config('protocol');
        $port = self::config('port');

        switch ($type) {
            case self::T_API_SCRIPT:
                $hostKey = 'scriptApiHost';
                $versionKey = 'scriptApiVersion';
                break;
            case self::T_API_COMMON:
            default:
                $hostKey = 'apiHost';
                $versionKey = 'apiVersion';
                break;
        }
        $apiHost = self::config($hostKey);
        $apiVersion = self::config($versionKey);

        if (($protocol == 'http' && $port == 80) ||
            ($protocol == 'https' && $port == 443)) {
            $port = null;
        }

        $url = $protocol . '://' . $apiHost;
        if ($port !== null) {
            $url .= ':' . $port;
        }
        $url .= '/' . $apiVersion . '/';
        return $url;
    }
}
