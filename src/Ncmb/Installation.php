<?php

namespace Ncmb;

/**
 * Installation class
 */
class Installation extends Object
{
    /**
     * Constructor
     * @param string $className class name of objects
     * @param string $objectId object id
     */
    public function __construct($objectId = null)
    {
        parent::__construct(null, $objectId);
    }

    /**
     * Get path string of this object class
     * @return string path string
     */
    public function getApiPath()
    {
        return 'installations';
    }

}
