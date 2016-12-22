<?php

namespace Ncmb;

/**
 * Role - Representation of an access Role.
 */
class Role extends Object
{
    public static $ncmbClassName = 'role';
    public static $apiPath = 'roles';

    /**
     * Create a Role object with a given name and ACL.
     *
     * @param string $name
     * @param \Ncmb\Acl|null $acl
     *
     * @return \Ncmb\Role
     */
    public static function createRole($name, $acl = null)
    {
        $role = new self(self::$ncmbClassName);
        $role->setName($name);
        if ($acl) {
            $role->setAcl($acl);
        }

        return $role;
    }

    /**
     * Get path string
     * @return string path string
     */
    public function getApiPath()
    {
        return self::$apiPath;
    }

    /**
     * Returns the role name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->get('roleName');
    }

    /**
     * Sets the role name.
     *
     * @param string $name The role name
     * @throws \Ncmb\Exception
     */
    public function setName($name)
    {
        if ($this->getObjectId()) {
            throw new Exception(
                "A role's name can only be set before it has been saved.");
        }
        if (!is_string($name)) {
            throw new Exception("A role's name must be a string.");
        }
        return $this->set('roleName', $name);
    }
}
