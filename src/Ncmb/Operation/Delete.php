<?php

namespace Ncmb\Operation;

/**
 * Class Operation\Delete - Operation to remove a key from an object.
 */
class Delete implements FieldOperation
{
    /**
     * Returns an associative array encoding of the current operation.
     *
     * @return array Associative array encoding the operation.
     */
    public function _encode()
    {
        return ['__op' => 'Delete'];
    }

    /**
     * Applies the current operation and returns the result.
     *
     * @param mixed  $oldValue Value prior to this operation.
     * @param mixed  $object   Unused for this operation type.
     * @param string $key      Key to remove from the target object.
     *
     * @return null
     */
    public function apply($oldValue, $object, $key)
    {
        // do nothing
    }

    /**
     * Merge this operation with a previous operation and return the result.
     *
     * @param FieldOperation $previous Previous operation.
     * @return FieldOperation Always returns the current operation.
     */
    public function mergeWithPrevious($previous)
    {
        return $this;
    }
}
