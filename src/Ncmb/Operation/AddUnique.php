<?php

namespace Ncmb\Operation;

use Ncmb\Encoder;

/**
 * Class Operation\Add - Operation to add unique objects to an array key.
 */
class AddUnique implements FieldOperation
{
    /**
     * Array with objects to add.
     *
     * @var array
     */
    private $objects;

    /**
     * Creates an Add Operation with the provided objects.
     *
     * @param array $objects Objects to add.
     *
     * @throws \Ncmb\Exception
     */
    public function __construct($objects)
    {
        if (!is_array($objects)) {
            throw new \Ncmb\Exception('AddOperation requires an array.');
        }
        $this->objects = $objects;
    }

    /**
     * Gets the objects for this operation.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->objects;
    }

    /**
     * Returns associative array representing encoded operation.
     *
     * @return array
     */
    public function encode()
    {
        return [
            '__op' => 'AddUnique',
            'objects'  => Encoder::encode($this->objects),
        ];
    }

    /**
     * Applies current operation, returns resulting value.
     *
     * @param mixed  $oldValue Value prior to this operation.
     * @param mixed  $obj      Value being applied.
     * @param string $key      Key this operation affects.
     *
     * @return array
     */
    public function apply($oldValue, $obj, $key)
    {
        if (!$oldValue) {
            return $this->objects;
        }
        if (!is_array($oldValue)) {
            $oldValue = (array) $oldValue;
        }

        foreach ($this->objects as $object) {
            if ($object instanceof \Ncmb\Object && $object->getObjectId()) {
                if (!$this->isNcmbObjectInArray($object, $oldValue)) {
                    $oldValue[] = $object;
                }
            } else {
                if (!in_array($object, $oldValue, true)) {
                    $oldValue[] = $object;
                }
            }
        }
        return $oldValue;
    }

    private function isNcmbObjectInArray($target, $oldValue)
    {
        foreach ($oldValue as $object) {
            if ($object instanceof \Ncmb\Object &&
                $object->getObjectId() != null) {
                if ($object->getObjectId() == $target->getObjectId()) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Merge this operation with the previous operation and return the result.
     *
     * @param FieldOperation $previous Previous Operation.
     * @throws \Ncmb\Exception
     * @return FieldOperation Merged Operation.
     */
    public function mergeWithPrevious($previous)
    {
        if (!$previous) {
            return $this;
        }
        if ($previous instanceof Delete) {
            return new Set($this->objects);
        }
        if ($previous instanceof Set) {
            $oldValue = $previous->getValue();
            $result = $this->apply($oldValue, null, null);

            return new Set($result);
        }
        if ($previous instanceof self) {
            $oldList = $previous->getValue();
            $result = $this->apply($oldList, null, null);

            return new self($result);
        }
        throw new \Ncmb\Exception(
            'Operation is invalid after previous operation.'
        );
    }
}
