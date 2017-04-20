<?php

namespace Ncmb\Operation;

use Ncmb\Encoder;

/**
 * \Ncmb\Operation\Relation - A class that is used to manage
 * \Ncmb\Relation changes such as object add or remove.
 */
class Relation implements FieldOperation
{
    /**
     * The className of the target objects.
     *
     * @var string
     */
    private $targetClassName;

    /**
     * Array of objects to add to this relation.
     *
     * @var array
     */
    private $relationsToAdd = [];

    /**
     * Array of objects to remove from this relation.
     *
     * @var array
     */
    private $relationsToRemove = [];

    /**
     * Constructor
     * @param string $operationType
     * @param array $objects
     */
    public function __construct($objectsToAdd, $objectsToRemove)
    {
        $this->targetClassName = null;
        $this->relationsToAdd['null'] = [];
        $this->relationsToRemove['null'] = [];
        if ($objectsToAdd !== null) {
            $this->checkAndAssignClassName($objectsToAdd);
            $this->addObjects($objectsToAdd, $this->relationsToAdd);
        }
        if ($objectsToRemove !== null) {
            $this->checkAndAssignClassName($objectsToRemove);
            $this->addObjects($objectsToRemove, $this->relationsToRemove);
        }
        if ($this->targetClassName === null) {
            throw new \Ncmb\Exception(
                'Cannot create a \\Ncmb\\Operation\\Relation with no objects.'
            );
        }
    }

    /**
     * Get target class name
     *
     * @return string class name
     */
    public function getTargetClass()
    {
        return $this->targetClassName;
    }

    /**
     * Helper function to check that all passed ParseObjects have same
     * class name and assign targetClassName variable.
     *
     * @param array $objects Object array.
     * @throws \Ncmb\Exception
     */
    private function checkAndAssignClassName($objects)
    {
        foreach ($objects as $object) {
            if ($this->targetClassName === null) {
                $this->targetClassName = $object->getClassName();
            }
            if ($this->targetClassName != $object->getClassName()) {
                throw new \Ncmb\Exception(
                    'All objects in a relation must be of the same class.');
            }
        }
    }

    /**
     * Adds an object or array of objects to the array, replacing any
     * existing instance of the same object.
     *
     * @param array $objects   Array of \Ncmb\Objects to add.
     * @param array $container Array to contain new \Ncmb\Objects.
     */
    private function addObjects($objects, &$container)
    {
        if (!is_array($objects)) {
            $objects = [$objects];
        }
        foreach ($objects as $object) {
            if ($object->getObjectId() == null) {
                $container['null'][] = $object;
            } else {
                $container[$object->getObjectId()] = $object;
            }
        }
    }

    /**
     * Removes an object (and any duplicate instances of that object) from
     * the array.
     *
     * @param array $objects   Array of \Ncmb\Objects to remove.
     * @param array $container Array to remove from it \Ncmb\Objects.
     */
    private function removeObjects($objects, &$container)
    {
        if (!is_array($objects)) {
            $objects = [$objects];
        }
        $nullObjects = [];
        foreach ($objects as $object) {
            if ($object->getObjectId() == null) {
                $nullObjects[] = $object;
            } else {
                unset($container[$object->getObjectId()]);
            }
        }
        if (!empty($nullObjects)) {
            $container['null'] = array_diff($container['null'], $nullObjects);
        }
    }

    /**
     * Applies the current operation and returns the result.
     *
     * @param mixed  $oldValue Value prior to this operation.
     * @param mixed  $object   Value for this operation.
     * @param string $key      Key to perform this operation on.
     * @throws \Ncmb\Exception
     * @return mixed Result of the operation.
     */
    public function apply($oldValue, $object, $key)
    {
        if ($oldValue == null) {
            return new \Ncmb\Relation($object, $key, $this->targetClassName);
        } elseif ($oldValue instanceof \Ncmb\Relation) {
            if ($this->targetClassName != null
                && $oldValue->getTargetClass() !== $this->targetClassName)
            {
                throw new \Ncmb\Exception(
                    'Related object object must be of class '
                    . $this->targetClassName .', but '
                    . $oldValue->getTargetClass()
                    .' was passed in.'
                );
            }
            return $oldValue;
        } else {
            throw new \Ncmb\Exception(
                'Operation is invalid after previous operation.'
            );
        }
    }

    /**
     * Merge this operation with a previous operation and return the new
     * operation.
     *
     * @param FieldOperation $previous Previous operation.
     * @throws \Ncmb\Exception
     * @return FieldOperation Merged operation result.
     */
    public function mergeWithPrevious($previous)
    {
        if ($previous == null) {
            return $this;
        }
        if ($previous instanceof self) {
            if ($previous->targetClassName != null &&
                $previous->targetClassName != $this->targetClassName) {
                throw new Exception(
                    'Related object object must be of class '
                    . $this->targetClassName . ', but '
                    . $previous->targetClassName .' was passed in.'
                );
            }
            $newRelationToAdd = self::convertToOneDimensionalArray(
                $this->relationsToAdd);

            $newRelationToRemove = self::convertToOneDimensionalArray(
                $this->relationsToRemove);

            $previous->addObjects(
                $newRelationToAdd, $previous->relationsToAdd);
            $previous->removeObjects(
                $newRelationToAdd, $previous->relationsToRemove);

            $previous->removeObjects(
                $newRelationToRemove, $previous->relationsToAdd);
            $previous->addObjects(
                $newRelationToRemove, $previous->relationsToRemove);

            $newRelationToAdd = self::convertToOneDimensionalArray(
                $previous->relationsToAdd);
            $newRelationToRemove = self::convertToOneDimensionalArray(
                $previous->relationsToRemove);
        }

        return new self(
            $newRelationToAdd,
            $newRelationToRemove
        );
    }

    /**
     * Returns an associative array encoding of the current operation.
     *
     * @throws \Ncmb\Exception
     * @return mixed
     */
    public function encode()
    {
        $addRelation = [];
        $removeRelation = [];
        if (!empty($this->relationsToAdd)) {
            $addRelation = [
                '__op'    => 'AddRelation',
                'objects' => Encoder::encode(
                    self::convertToOneDimensionalArray($this->relationsToAdd)),
            ];
        }
        if (!empty($this->relationsToRemove)) {
            $removeRelation = [
                '__op'    => 'RemoveRelation',
                'objects' => Encoder::encode(
                    self::convertToOneDimensionalArray($this->relationsToRemove)),
            ];
        }
        if (!empty($addRelation['objects']) &&
            !empty($removeRelation['objects'])) {
            // BUGS: multi-operation aware? must check.
            return [$addRelation, $removeRelation];
        }
        return empty($addRelation['objects']) ? $removeRelation: $addRelation;
    }

    /**
     * Convert any array to one dimensional array.
     *
     * @param array $array
     * @return array
     */
    public static function convertToOneDimensionalArray($array)
    {
        $newArray = [];
        if (is_array($array)) {
            foreach ($array as $value) {
                $newArray = array_merge(
                    $newArray, self::convertToOneDimensionalArray($value));
            }
        } else {
            $newArray[] = $array;
        }
        return $newArray;
    }
}
