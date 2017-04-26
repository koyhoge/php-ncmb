<?php

namespace Ncmb;

/**
 * Relation - A class that is used to access all of the children of a
 * many-to-many relationship. Each instance.
 */
class Relation
{
    /**
     * The parent of this relation.
     *
     * @var \Ncmb\Object
     */
    private $parent;

    /**
     * The key of the relation in the parent object.
     *
     * @var string
     */
    private $key;

    /**
     * The className of the target objects.
     *
     * @var string
     */
    private $targetClassName;

    /**
     * Creates a new Relation for the given parent object,
     * key and class name of target objects.
     *
     * @param \Ncmb\Object $parent The parent of this relation.
     * @param string $key The key of the relation in the parent object.
     * @param string $targetClassName The className of the target objects.
     */
    public function __construct($parent, $key, $targetClassName = null)
    {
        $this->parent = $parent;
        $this->key = $key;
        $this->targetClassName = $targetClassName;
    }

    /**
     * Adds a Object or an array of Objects to the relation.
     *
     * @param mixed $objects The item or items to add.
     */
    public function add($objects)
    {
        if (!is_array($objects)) {
            $objects = [$objects];
        }

        $operation = new \Ncmb\Operation\Relation($objects, null);
        $this->targetClassName = $operation->getTargetClass();
        $this->parent->performOperation($this->key, $operation);
    }

    /**
     * Returns the target classname for the relation.
     *
     * @return string
     */
    public function getTargetClass()
    {
        return $this->targetClassName;
    }

    /**
     * Set the target classname for the relation.
     *
     * @param $className
     */
    public function setTargetClass($className)
    {
        $this->targetClassName = $className;
    }

    /**
     * Set the parent object for the relation.
     *
     * @param $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * Gets a query that can be used to query the objects in this relation.
     *
     * @return \Ncmb\Query That restricts the results to objects in this relations.
    */
    public function getQuery()
    {
        $query = new Query($this->targetClassName);
        $query->relatedTo('object', $this->parent->toPointer());
        $query->relatedTo('key', $this->key);

        return $query;
    }
}
