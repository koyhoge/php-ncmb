<?php

namespace Ncmb;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

/**
 * REST API client class
 */
class ApiClient
{
    /** @var http client */
    private $client;

    /**
     * The object for managing persistence
     * @var Ncmb\StorageInterface
     */
    private static $storage;

    /** @var current ApiClient */
    protected static $apiClient = null;

    /** @var array config */
    private $config = array(
        'apiUrl' => 'https://mb.api.cloud.nifty.com/2013-09-01/',
        'applicationKey' => '',
        'clientKey' => '',
    );

    /**
     * Create new ApiClient instance
     * @param $type string API type (script or not)
     */
    public static function create($type = null)
    {
        $config = NCMB::config();
        $config['apiUrl'] = NCMB::getApiUrl($type);
        $config['applicationKey'] = NCMB::applicationKey();
        $config['clientKey'] = NCMB::clientKey();

        return new self($config);
    }

    /**
     * Send with HTTP GET method
     * @param string $path API path
     * @param array $options options
     */
    public static function get($path, array $options = [],
                               $returnResponse = false)
    {
        return self::request('GET', $path, $options,
                             null, null, null, $returnResponse);
    }

    /**
     * Send with HTTP POST method
     * @param string $path API path
     * @param array $options options
     */
    public static function post($path, array $options = [],
                                $returnResponse = false)
    {
        return self::request('POST', $path, $options,
                             null, null, null, $returnResponse);
    }

    /**
     * Send with HTTP PUT method
     * @param string $path path of url
     * @param array $options options
     */
    public static function put($path, array $options = [],
                               $returnResponse = false)
    {
        return self::request('PUT', $path, $options,
                             null, null, null, $returnResponse);
    }

    /**
     * Send with HTTP DELETE method
     * @param string $path path of url
     * @param array $options options
     */
    public static function delete($path, array $options = [],
                                  $returnResponse = false)
    {
        return self::request('DELETE', $path, $options,
                             null, null, null, $returnResponse);
    }

    /**
     * Convinience shortcut to send request to the server
     *
     * @param string $method HTTP Method
     * @param string $relativeUrl REST API Path
     * @param array $data Data to provide with the request.
     * @param string $sessionToken Session token
     * @param string $apiType type of APIi
     * @param ApiClient $apiClient ApiClient object
     * @param bool $returnResponse return response object
     * @return mixed Result from API call
     */
    public static function request(
        $method,
        $relativeUrl,
        $data = null,
        $sessionToken = null,
        $apiType = null,
        $apiClient = null,
        $returnResponse = false
    ) {
        if ($apiClient === null) {
            if (static::$apiClient !== null) {
                $apiClient = self::$apiClient;
            } else {
                $apiClient = static::create($apiType);
                self::$apiClient = $apiClient;
            }
        }
        if ($data === null) {
            $data = [];
        }

        // if $sessionToken is not set, use of current user
        if ($sessionToken === null) {
            $curUser = User::getCurrentUser();
            if ($curUser) {
                $sessionToken = $curUser->getSessionToken();
            }
        }

        // Send request to API
        $response = $apiClient->send($method, $relativeUrl, $data,
                                     $sessionToken);
        $statusCode = $response->getStatusCode();
        if ($statusCode != 200 && $statusCode != 201) {
            throw new Exception('API returns bad status code');
        }

        if ($returnResponse) {
            return $response;
        } else {
            // Check Content-Type of response
            $headers = $response->getHeaders();
            $validContent = 'application/json';
            if (strncmp($headers['Content-Type'][0], $validContent,
                        strlen($validContent)) !== 0) {
                throw new Exception('API returns invalid content-type');
            }

            $body = $response->getBody();
            $decoded = json_decode((string)$body, true);
            return $decoded;
        }
    }

    /**
     * Constructor
     */
    public function __construct(array $config,
                                GuzzleHttp\ClientInterface $client = null)
    {
        $this->config = array_merge($this->config, $config);
        $this->client = $client ? $client : new Client();

        if (session_status() === PHP_SESSION_ACTIVE) {
            static::setStorage(new \Ncmb\Storage\Session());
        } else {
            static::setStorage(new \Ncmb\Storage\Memory());
        }
    }

    /**
     * Update the storage object used for persistence.
     *
     * @param Ncmb\storageInterface $storageObject
     */
    public static function setStorage(StorageInterface $storageObject)
    {
        self::$storage = $storageObject;
    }

    /**
     * Get the storage object used for persistence.
     *
     * @return Ncmb\StorageInterface
     */
    public static function getStorage()
    {
        return self::$storage;
    }

    /**
     * Send HTTP request
     * @param string $method HTTP method
     * @param string $path API path
     * @param array $options options
     * @returns ResopnseInterface
     */
    public function send($method, $path,
                         array $options = [], $sessionToken = null)
    {
        $url = $this->createAbsURL($path);
        $headers = $this->createDefaultHeaders($method, $url,
                                               $options, $sessionToken);
        $request = new Request($method, $url, $headers);
        return $this->client->send($request, $options);
    }

    /**
     * Create absolute path
     * @param string $path API path
     * @returns string absolute path
     */
    protected function createAbsURL($path)
    {
        return sprintf('%s%s',
                       $this->config['apiUrl'],
                       $path);
    }

    /**
     * Create NCMB related headers
     * @param string $method HTTP method
     * @param string $url URL
     * @param array $options options
     * @returns array generated headers to use API authentication
     */
    protected function createDefaultHeaders($method, $url,
                                            array $options = [],
                                            $sessionToken = null)
    {
        $applicationKey = $this->config['applicationKey'];
        $timestamp = $this->timestamp();
        $query = isset($options['query']) ? $options['query'] : [];

        $sign = $this->sign($method, $url, $query, $timestamp);

        $headers = [
            'X-NCMB-Application-Key' => $applicationKey,
            'X-NCMB-Signature' => $sign,
            'X-NCMB-Timestamp' => $timestamp,
            'User-Agent' => NCMB::SDK_VERSION,
        ];

        if ($sessionToken !== null) {
            $headers['X-NCMB-Apps-Session-Token'] = $sessionToken;
        }

        return $headers;
    }

    /**
     * Get current timestamp
     * @returns string timestamp
     */
    protected function timestamp()
    {
        $mtimestr = microtime();
        preg_match('/^0.(\d{3})/', $mtimestr, $match);
        $msec = $match[1];

        $dt = new \DateTime(null, new \DateTimeZone('UTC'));

        $timestamp = $dt->format('Y-m-d\TH:i:s') .
            '.' . $msec . 'Z';

        return $timestamp;
    }

    /**
     * Generate signature
     * @param $method string HTTP method
     * @param $url string URL
     * @param $params array qury params
     * @param $timestamp string timestamp string
     * @return string signature
     */
    protected function sign($method, $url, $params, $timestamp)
    {
        $application_key = $this->config['applicationKey'];
        $client_key = $this->config['clientKey'];

        $params['SignatureMethod'] = $this->config['signatureMethod'];
        $params['SignatureVersion'] = $this->config['signatureVersion'];
        $params['X-NCMB-Application-Key'] = $application_key;
        $params['X-NCMB-Timestamp'] = $timestamp;

        uksort($params, 'strnatcmp');

        $encoded_params = '';
        foreach ($params as $k => $v) {
            if ($k === 'X-NCMB-Timestamp') {
                $encoded_params[] = $k . '=' . $v;
            } else {
                $encoded_params[] = $k . '=' . urlencode($v);
            }
        }

        $params_string = implode('&', $encoded_params);

        $url_parts = parse_url($url);

        $keys = [
            $method,
            $url_parts['host'],
            $url_parts['path'],
            $params_string,
        ];

        $sign_string = implode("\n", $keys);
        $sign = $this->internalHash($sign_string, $client_key);

        return $sign;
    }

    /**
     * Generate hashed signature with setting method
     * @param $data string
     * @param $key string private key
     * @return string hashed string
     */
    protected function internalHash($data, $key)
    {
        if (isset($this->config['signatureMethod'])) {
            $hashMethod = $this->config['signatureMethod'];
        } else {
            $hashMethod = 'HmacSHA256';  // default
        }

        switch ($hashMethod) {
            case 'HmacSHA256':
                $hash = hash_hmac('sha256', $data, $key, true);
                break;
            default:
                throw new \UnexpectedValueException('signatureMethod invalid');
                break;
        }
        return base64_encode($hash);
    }
}
