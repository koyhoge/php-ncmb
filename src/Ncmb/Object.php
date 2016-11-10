<?php

namespace Ncmb;

/**
 * Object class
 */
class Object
{
    /**
     * Class name for data
     * @var string
     */
    private $className = null;

    /**
     * Unique identifier on NCMB
     * @var string
     */
    private $objectId = null;

    /**
     * Api path prefix
     * @var string
     */
    private $pathPrefix = 'classes/';

    /**
     * Object data
     * @var array
     */
    private $data = [];

    /**
     * Constructor
     * @param string $className class name of objects
     * @param string $objectId object id
     */
    public function __construct($className = null, $objectId = null)
    {
        $this->className = $className;
    }

    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Set key and value into the object
     * @param string $key
     * @param any $value
     */
    public function set($key, $value)
    {
        if (!$key) {
            throw new Exception('key may not be null.');
        }
        $this->data[$key] = $value;
    }

    /**
     * Get value with given key
     * @param string $key
     * @return any
     */
    public function get($key)
    {
        return $this->data[$key];
    }

    /**
     * Get class name of this object
     * @return string class name
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * Get path string of this object class
     * @return string path string
     */
    public function getApiPath()
    {
        return $this->pathPrefix . $this->getClassName();
    }

    /**
     * Get the objectId for the object, or null if unsaved.
     * @return string|null
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * Save object
     */
    public function save()
    {
        $path = $this->getApiPath();
        $options = [
            'json' => $this->data,
        ];

        $client = ApiClient::create();
        return $client->post($path, $options);
    }
}
