<?php

namespace Ncmb\Operation;

use Ncmb\Encoder;

/**
 * Class Operation\Remove - FieldOperation for removing object(s) from array
 * fields.
 */
class Remove implements FieldOperation
{
    /**
     * Array with objects to remove.
     *
     * @var array
     */
    private $objects;

    /**
     * Creates an Operation\Remove with the provided objects.
     *
     * @param array $objects Objects to remove.
     * @throws \Ncmb\Exception
     */
    public function __construct($objects)
    {
        if (!is_array($objects)) {
            throw new \Ncmb\Exception('RemoveOperation requires an array.');
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
            '__op'    => 'Remove',
            'objects' => Encoder::encode($this->objects, true),
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
        if (empty($oldValue)) {
            return [];
        }
        $newValue = [];
        foreach ($oldValue as $oldObject) {
            foreach ($this->objects as $newObject) {
                if ($oldObject instanceof \Ncmb\Object) {
                    if ($newObject instanceof \Ncmb\Object
                        && !$oldObject->isDirty()
                        && $oldObject->getObjectId() == $newObject->getObjectId()
                    ) {
                        // found the object, won't add it.
                    } else {
                        $newValue[] = $oldObject;
                    }
                } else {
                    if ($oldObject !== $newObject) {
                        $newValue[] = $oldObject;
                    }
                }
            }
        }
        return $newValue;
    }

    /**
     * Takes a previous operation and returns a merged operation to replace it.
     *
     * @param FieldOperation $previous Previous operation.
     * @throws \Ncmb\Exception
     * @return FieldOperation Merged operation.
     */
    public function mergeWithPrevious($previous)
    {
        if (!$previous) {
            return $this;
        }
        if ($previous instanceof Delete) {
            return $previous;
        }
        if ($previous instanceof Set) {
            return new Set(
                $this->apply($previous->getValue(), $this->objects, null)
            );
        }
        if ($previous instanceof self) {
            $oldList = $previous->getValue();

            return new self(
                array_merge((array)$oldList, (array)$this->objects)
            );
        }
        throw new \Ncmb\Exception(
            'Operation is invalid after previous operation.'
        );
    }
}
