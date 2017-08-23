<?php

namespace Ncmb\Operation;

use Ncmb\Encoder;

/**
 * Class Operation\Add - FieldOperation for adding object(s) to array fields.
 */
class Add implements FieldOperation
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
            '__op' => 'Add',
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
        return array_merge((array)$oldValue, (array)$this->objects);
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
            return new Set($this->objects);
        }
        if ($previous instanceof Set) {
            $oldList = $previous->getValue();

            return new Set(
                array_merge((array)$oldList, (array)$this->objects)
            );
        }
        if ($previous instanceof self) {
            $oldList = $previous->getValue();

            return new Set(
                array_merge((array)$oldList, (array)$this->objects)
            );
        }
        throw new \Ncmb\Exception(
            'Operation is invalid after previous operation.'
        );
    }
}
