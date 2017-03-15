<?php

namespace Ncmb\Operation;

use Ncmb\Encoder;

/**
 * Class Operation\Set - Operation to set a value for an object key.
 */
class Set implements FieldOperation
{
    /**
     * Value to set for this operation.
     *
     * @var mixed
     */
    private $value;

    /**
     * If the value should be forced as object.
     *
     * @var bool
     */
    private $isAssociativeArray;

    /**
     * Create a SetOperation with a value.
     *
     * @param mixed $value              Value to set for this operation.
     * @param bool  $isAssociativeArray If the value should be forced as object.
     */
    public function __construct($value, $isAssociativeArray = false)
    {
        $this->value = $value;
        $this->isAssociativeArray = $isAssociativeArray;
    }

    /**
     * Get the value for this operation.
     *
     * @return mixed Value.
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns an associative array encoding of the current operation.
     *
     * @return mixed
     */
    public function encode()
    {
        if ($this->isAssociativeArray) {
            $object = new \stdClass();
            foreach ($this->value as $key => $value) {
                $object->$key = Encoder::encode($value, true);
            }
            return Encoder::encode($object, true);
        }
        return Encoder::encode($this->value, true);
    }

    /**
     * Apply the current operation and return the result.
     *
     * @param mixed  $oldValue Value prior to this operation.
     * @param mixed  $object   Value for this operation.
     * @param string $key      Key to set this value on.
     * @return mixed
     */
    public function apply($oldValue, $object, $key)
    {
        return $this->value;
    }

    /**
     * Merge this operation with a previous operation and return the
     * resulting operation.
     *
     * @param FieldOperation $previous Previous operation.
     *
     * @return FieldOperation
     */
    public function mergeWithPrevious($previous)
    {
        return $this;
    }
}
