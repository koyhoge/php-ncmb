<?php

namespace Ncmb;

use Ncmb\Operation\FieldOperation;
use Ncmb\Relation;

/**
 * Object class
 */
class Object implements Encodable
{
    const RESERVED_KEYS = [
        'objectId',
        'createDate',
        'updateDate',
        'className',
    ];

    /**
     * Data as it exists on the server.
     *
     * @var array
     */
    protected $serverData = [];

    /**
     * Estimated value of applying operationSet to serverData.
     *
     * @var array
     */
    private $estimatedData = [];

    /**
     * Set of unsaved operations.
     *
     * @var array
     */
    private $operationSet = [];

    /**
     * Determine if data available for a given key or not.
     *
     * @var array
     */
    private $dataAvailability = [];

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
     * Timestamp when object created
     * @var \DateTime
     */
    private $createDate = null;

    /**
     * Timestamp when object updated
     * @var \DateTime
     */
    private $updateDate = null;

    /**
     * Api path prefix
     * @var string
     */
    private $pathPrefix = 'classes/';

    /**
     * Whether the object has been fully fetched from Parse.
     *
     * @var bool
     */
    private $hasBeenFetched;

    /**
     * Constructor
     * @param string $className class name of objects
     * @param string $objectId object id
     */
    public function __construct($className = null, $objectId = null,
                                $isPointer = false)
    {
        $this->className = $className;

        $this->hasBeenFetched = false;
        $this->objectId = $objectId;
        if (!$objectId || $isPointer) {
            $this->hasBeenFetched = true;
        }
    }

    public function __set($key, $value)
    {
        if (in_array($key, self::RESERVED_KEYS)) {
            throw new Exception('Protected field could not be set.');
        } else {
            $this->set($key, $value);
        }
    }

    public function __get($key)
    {
        return $this->get($key);
    }

    public function __isset($key)
    {
        return $this->has($key);
    }

    /**
     * Set key and value into the object
     * @param string $key
     * @param any $value
     * @throws \Ncmb\Exception
     */
    public function set($key, $value)
    {
        if (!$key) {
            throw new Exception('key may not be null.');
        }
        if (is_array($value)) {
            throw new Exception(
                'Must use setArray() or setAssociativeArray() for this value.'
            );
        }
        // $this->estimatedData[$key] = $value;
        // $this->dataAvailability[$key] = true;
        $this->performOperation($key, new Operation\Set($value));
    }

    /**
     * Set an array value for an object key.
     *
     * @param string $key   Key to set the value for on the object.
     * @param array  $value Value to set on the key.
     * @throws \Ncmb\Exception
     */
    public function setArray($key, $value)
    {
        if (!$key) {
            throw new Exception('key may not be null.');
        }
        if (!is_array($value)) {
            throw new Exception('Must use set() for non-array values.');
        }
        //        $this->estimatedData[$key] = $value;
        //        $this->dataAvailability[$key] = true;
        $this->performOperation($key, new Operation\Set(array_values($value)));
    }

    /**
     * Set an associative array value for an object key.
     *
     * @param string $key   Key to set the value for on the object.
     * @param array  $value Value to set on the key.
     * @throws \Ncmb\Exception
     */
    public function setAssociativeArray($key, $value)
    {
        if (!$key) {
            throw new Exception('key may not be null.');
        }
        if (!is_array($value)) {
            throw new Exception('Must use set() for non-array values.');
        }
        $this->performOperation($key, new Operation\Set($value, true));
    }

    /**
     * Get value with given key
     * @param string $key
     * @throws \Ncmb\Exception
     * @return any
     */
    public function get($key)
    {
        if (!$this->_isDataAvailable($key)) {
            throw new Exception(
                'Ncmb\\Object has no data for this key. '.
                'Call fetch() to get the data.'
            );
        }
        if (isset($this->estimatedData[$key])) {
            return $this->estimatedData[$key];
        }
        return;
    }

    /**
     * Check if the object has a given key.
     *
     * @param string $key Key to check
     * @return bool
     */
    public function has($key)
    {
        return isset($this->estimatedData[$key]);
    }

    /**
     * Check if the a value associated with a key has been
     * added/updated/removed and not saved yet.
     *
     * @param string $key
     *
     * @return bool
     */
    public function isKeyDirty($key)
    {
        return isset($this->operationSet[$key]);
    }

    /**
     * Returns true if the object has been fetched.
     *
     * @return bool
     */
    public function isDataAvailable()
    {
        return $this->hasBeenFetched;
    }

    private function _isDataAvailable($key)
    {
        return $this->isDataAvailable() ||
            isset($this->dataAvailability[$key]);
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
     * Set ACL for this object.
     *
     * @param \Ncmb\Acl $acl
     */
    public function setAcl($acl)
    {
        // TODO: rewrite to use Operation
        $this->set('acl', $acl);
    }

    /**
     * Get ACL assigned to the object.
     *
     * @return \Ncmb\Acl
     */
    public function getAcl()
    {
        return $this->get('acl');
    }

    /**
     * Handle merging of special fields for the object.
     *
     * @param array &$data Data received from server.
     */
    protected function mergeMagicFields(&$data)
    {
        $transmap = [
            // key     => type
            'objectId' => 'direct',
            'createDate' => 'datetime',
            'updateDate' => 'datetime',
            'acl' => 'acl',
        ];

        foreach ($transmap as $key => $type) {
            if (isset($data[$key])) {
                $val = $data[$key];
                switch ($type) {
                    case 'datetime':
                        $this->$key = new \DateTime($val);
                        break;
                    case 'acl':
                        $this->serverData['acl'] = Acl::createFromData($val);
                        break;
                    default:
                        $this->$key = $val;
                        break;
                }
                unset($data[$key]);
            }
        }
    }

    /**
     * Save the object
     * @return \Ncmb\Object Returns self
     */
    public function save()
    {
        $path = $this->getApiPath();
        $options = [
            // FIXME: support operations
            'json' => $this->getSaveData(),
        ];

        $data = ApiClient::post($path, $options);
        $this->mergeAfterFetch($data, false);
        return $this;
    }

    /**
     * Static method which returns a new NCMB Object for a given class
     * Optionally creates a pointer object if the objectId is provided.
     *
     * @param string $className Class Name for data on NCMB.
     * @param string $objectId  Unique identifier for existing object.
     * @param bool   $isPointer If the object is a pointer.
     *
     * @return \Ncmb\Object
     */
    public static function create($className, $objectId = null,
                                  $isPointer = false)
    {
        return new self($className, $objectId, $isPointer);
    }

    /**
     * Fetch the object
     * @return \Ncmb\Object Returns self
     */
    public function fetch()
    {
        $response = ApiClient::get(
            $this->getApiPath() . '/' . $this->objectId
        );
        $this->mergeAfterFetch($response);

        return $this;
    }

    /**
     * Find objects with gevin query
     * @param \Ncmb\Query|null $query Query object
     * @return array array of result objects
     */
    public function find($query = null)
    {
        $path = $this->getApiPath();

        if ($query) {
            $options = [
                'query' => $query->getQueryOptions(),
            ];
        } else {
            $options = [];
        }
        $result = ApiClient::get($path, $options);

        $output = [];
        foreach ($result['results'] as $row) {
            $obj = new static;
            $obj->mergeAfterFetch($row, true);
            $output[] = $obj;
        }
        return $output;
    }

    /**
     * Merges data received from the server.
     *
     * @param array $data Data retrieved from server
     * @param bool $completeData Fetch all data or not
     */
    protected function mergeFromServer($data, $completeData = true)
    {
        $this->hasBeenFetched =
            ($this->hasBeenFetched || $completeData)? true: false;

        $this->mergeMagicFields($data);
        foreach ($data as $key => $value) {
            /*
            if ($key === '__type' && $value === 'className') {
                continue;
            }
            */
            if ($this->isRelation($value)) {
                $class = $value['className'];
                $decodedValue = new Relation($this, $key, $class);
            } else {
                $decodedValue = static::decode($value);
            }
            $this->serverData[$key] = $decodedValue;
            $this->dataAvailability[$key] = true;
        }
    }

    protected function isRelation($data)
    {
        if (!isset($data) || !is_array($data)) {
            return false;
        }
        $type = (isset($data['__type']))? $data['__type']: null;
        $class = (isset($data['className']))? $data['className']: null;
        if ($type === 'Relation' && $class !== null) {
            return true;
        }
        return false;
    }

    /**
     * Merges data received from the server.
     *
     * @param array $result       Data retrieved from the server.
     * @param bool  $completeData Fetch all data or not.
     */
    public function mergeAfterFetch($data, $completeData = true)
    {
        $this->mergeFromServer($data, $completeData);
        $this->rebuildEstimatedData();
    }

    /**
     * Start from serverData and process operations to generate the current
     * value set for an object.
     */
    protected function rebuildEstimatedData()
    {
        $this->estimatedData = [];
        foreach ($this->serverData as $key => $value) {
            $this->estimatedData[$key] = $value;
        }
    }

    /**
     * Perform an operation on an object property.
     *
     * @param string         $key       Key to perform an operation upon.
     * @param FieldOperation $operation Operation to perform.
     */
    public function performOperation($key, FieldOperation $operation)
    {
        $oldValue = null;
        if (isset($this->estimatedData[$key])) {
            $oldValue = $this->estimatedData[$key];
        }
        $newValue = $operation->apply($oldValue, $this, $key);
        if ($newValue !== null) {
            $this->estimatedData[$key] = $newValue;
        } elseif (isset($this->estimatedData[$key])) {
            unset($this->estimatedData[$key]);
        }

        if (isset($this->operationSet[$key])) {
            $oldOperations = $this->operationSet[$key];
            $newOperations = $operation->mergeWithPrevious($oldOperations);
            $this->operationSet[$key] = $newOperations;
        } else {
            $this->operationSet[$key] = $operation;
        }
        $this->dataAvailability[$key] = true;
    }

    /**
     * Decode parametarized data to native
     *
     * @param mixed $data The value to decode
     * @return mixed
     */
    public static function decode($data)
    {
        if (!isset($data) && !is_array($data)) {
            return;
        }

        if (!is_array($data)) {
            // atomic value
            return $data;
        }

        $typeString = (isset($data['__type']))? $data['__type']: null;
        switch ($typeString) {
            case 'Date':
                return new \DateTime($data['iso']);
                break;
            case 'Pointer':
                return Object::create($data['className'], $data['objectId']);
                break;
            case 'Relation':
                throw new Exception('Relation not allowed here');
                break;
            case 'GeoPoint':
                return new GeoPoint($data['latitude'], $data['longitude']);
                break;
            default:
                $newDict = [];
                foreach ($data as $key => $value) {
                    $newDict[$key] = static::decode($value);
                }
                return $newDict;
        }
    }

    /**
     * Access or create a Relation value for a key.
     *
     * @param string $key       The key to access the relation for.
     * @param string $className The target class name.
     *
     * @return \Ncmb\Relation The \Ncmb\Relation object if the relation already
     *                       exists for the key or can be created for this key.
     */
    public function getRelation($key, $className = null)
    {
        $relation = new Relation($this, $key, $className);
        if (!$className && isset($this->estimatedData[$key])) {
            $object = $this->estimatedData[$key];
            if ($object instanceof Relation) {
                $relation->setTargetClass($object->getTargetClass());
            }
        }
        return $relation;
    }

    /**
     * Gets a Pointer referencing this Object.
     *
     * @throws \Ncmb\Exception
     * @return array
     */
    public function toPointer()
    {
        if (!$this->objectId) {
            throw new Exception("Can't serialize an unsaved Ncmb Object");
        }
        return [
            '__type'    => 'Pointer',
            'className' => $this->className,
            'objectId'  => $this->objectId,
        ];
    }

    /**
     * Get encoded data to save
     * @return array
     */
    protected function getSaveData()
    {
        $data = [];
        foreach ($this->operationSet as $key => $value) {
            $data[$key] = Encoder::encode($value);
        }
        return $data;
    }

    /**
     * Return a JSON encoded value of the object.
     *
     * @return string
     */
    public function encode()
    {
        $out = [];
        if ($this->objectId) {
            $out['objectId'] = $this->objectId;
        }
        if ($this->createDate) {
            $out['createDate'] = $this->createDate;
        }
        if ($this->updateDate) {
            $out['updateDate'] = $this->updateDate;
        }

        foreach ($this->serverData as $key => $value) {
            $out[$key] = $value;
        }
        foreach ($this->estimatedData as $key => $value) {
            if (is_object($value) && $value instanceof Encodable) {
                $out[$key] = $value->encode();
            } elseif (is_array($value)) {
                $out[$key] = [];
                foreach ($value as $item) {
                    if (is_object($item) && $item instanceof Encodable) {
                        $out[$key][] = $item->encode();
                    } else {
                        $out[$key][] = $item;
                    }
                }
            } else {
                $out[$key] = $value;
            }
        }

        // operations
        foreach ($this->operationSet as $key => $set) {
            if ($set instanceof FieldOperation) {
                $out[$key] = $set->encode();
            }
        }
        return json_encode($out);
    }
}
