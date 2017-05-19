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
     */
    public static function execute($scriptName, $data = null)
    {
        if (empty($scriptName)) {
            throw new Exception('ScriptName is required');
        }

        $apiPath = self::PATH_PREFIX . '/' . $scriptName;

        $returnResponse = true;
        $response = ApiClient::request('GET',
                                       $apiPath,
                                       $data,
                                       null,
                                       NCMB::T_API_SCRIPT,
                                       null,
                                       $returnResponse);
        $body = $response->getBody();
        return (string)$body;
    }
}
