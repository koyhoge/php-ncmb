<?php

namespace Ncmb;

/**
 * Script - Run NCMB Script
 */
class Script
{
    const PATH_PREFIX = 'script';

    /**
     * Execute registered script in NCMB
     * @param string $scriptName
     * @param array $options Guzzle Request Options
     * @param string $method HTTP method
     * @return string response body as string
     */
    public static function execute($scriptName,
                                   $options = null, $method = 'GET')
    {
        if (empty($scriptName)) {
            throw new Exception('ScriptName is required');
        }

        $apiPath = self::PATH_PREFIX . '/' . $scriptName;

        $returnResponse = true;
        $response = ApiClient::request($method,
                                       $apiPath,
                                       $options,
                                       null,
                                       NCMB::T_API_SCRIPT,
                                       null,
                                       $returnResponse);
        $body = $response->getBody();
        return (string)$body;
    }
}
