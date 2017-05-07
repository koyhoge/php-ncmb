<?php
namespace Ncmb;

/**
 * Push  - Handles sending push notifications with NCMB.
 */
class Push
{
    const VALID_OPTION_KEY = [
        'deliveryTime',
        'immediateDeliveryFlag',
        'target',
        'searchCondition',
        'message',
        'userSettingValue',
        'deliveryExpirationDate',
        'deliveryExpirationTime',
        'action',
        'title',
        'dialog',
        'badgeIncrementFlag',
        'badgeSetting',
        'sound',
        'contentAvailable',
        'richUrl',
        'category',
        'acl',
    ];

    static private $pathPrefix = 'push';

    /**
     * Send push notification
     * @param array $options
     * @return string objectId of the registration for push
     */
    public static function send($options)
    {
        $data = static::encodeOptions($options);

        $apiPath = static::$pathPrefix;
        $apiOptions = [
            'json' => $data,
        ];
        $response = ApiClient::post($apiPath, $apiOptions);

        return $response['objectId'];
    }

    /**
     * Update registered push notification
     * @param string $id push id
     * @param array $options
     */
    public static function update($id, $options)
    {
        $data = static::encodeOptions($options);
        $apiPath = static::$pathPrefix . '/' . $id;
        $apiOptions = [
            'json' => $data,
        ];
        $apiClient::put($apiPath, $apiOptions);
    }

    /**
     * Delete registered push notification
     * @param string $id push id
     */
    public static function delete($id)
    {
        $apiPath = static::$pathPrefix . '/' . $id;
        $apiClient::delete($apiPath);
    }

    protected static function encodeOptions($options)
    {
        $deliveryTime = $immediateDeliveryFlag = null;
        $data = [];

        foreach ($options as $key => $val) {
            if (!in_array($key, self::VALID_OPTION_KEY)) {
                throw new Exception('Invalid option with Push::send');
            }
            if ($key === 'deliveryTime') {
                $deliveryTime = $val;
            }
            if ($key === 'immediateDeliveryFlag') {
                $immediateDeliveryFlag = $val;
            }

            if (is_object($val) && $val instanceof Encodable) {
                $data[$key] = $val->encode();
            } else {
                $data[$key] = $val;
            }
        }
        if ($deliveryTime === null && $immediateDeliveryFlag === null) {
            throw new Exception('deliveryTime or immediateDeliveryFlag is required');
        }
        return $data;
    }
}
