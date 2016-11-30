<?php

namespace Ncmb;

/**
 * Acl class
 */
class Acl implements Encodable
{
    /** @var public ACL */
    const PUBLIC_KEY = '*';

    /** @var prefix of role */
    const ROLE_KEY = 'role:';

    /** @var array */
    private $permissionsById = [];

    /**
     * Create new Acl with read and write access for the given user.
     * @param User $user
     * @return Acl
     */
    public static function createWithUser($user)
    {
        $acl = new self();
        $acl->setUserReadAccess($user, true);
        $acl->setUserWriteAccess($user, true);
        return $acl;
    }
    /**
     * Create new Acl object from existing permissions.
     *
     * @param array $data represents permissions.
     * @throws \Ncmb\Exception
     * @return \Ncmb\Acl
     */
    public static function createFromData($data)
    {
        $acl = new self();
        foreach ($data as $id => $permissions) {
            if (!is_string($id)) {
                throw new Exception('Invalid user id to create an ACL');
            }
            foreach ($permissions as $accessType => $value) {
                if ($accessType !== 'read' && $accessType !== 'write') {
                    throw new Exception('Invalid permission type with ACL');
                }
                if (!is_bool($value)) {
                    throw new Exception('Invalid permission value with ACL');
                }
                $acl->setAccess($accessType, $id, $value);
            }
        }
        return $acl;
    }

    public function encode()
    {
        if (empty($this->permissionsById)) {
            return new \stdClass();
        }

        return $this->permissionsById;
    }

    /**
     * Set access permission with access name, user id and if
     * the user has permission for accessing or not.
     *
     * @param string $accessType Access name.
     * @param string $userId     User id.
     * @param bool   $allowed    If user allowed to access or not.
     */
    protected function setAccess($accessType, $userId, $allowed)
    {
        if ($userId instanceof User) {
            $userId = $userId->getObjectId();
        }
        if ($userId instanceof Role) {
            $userId = self::ROLE_KEY . $userId->getName();
        }
        if (!is_string($userId)) {
            throw new Exception('Invalid target for access control.');
        }
        if (!isset($this->permissionsById[$userId])) {
            if (!$allowed) {
                return;
            }
            $this->permissionsById[$userId] = [];
        }
        if ($allowed) {
            $this->permissionsById[$userId][$accessType] = true;
        } else {
            unset($this->permissionsById[$userId][$accessType]);
            if (empty($this->permissionsById[$userId])) {
                unset($this->permissionsById[$userId]);
            }
        }
    }

    /**
     * Get if the given userId has a permission for the given access type
     * or not.
     *
     * @param string $accessType Access name.
     * @param string $userId     User id.
     *
     * @return bool
     */
    protected function getAccess($accessType, $userId)
    {
        if (!isset($this->permissionsById[$userId])) {
            return false;
        }
        if (!isset($this->permissionsById[$userId][$accessType])) {
            return false;
        }
        return $this->permissionsById[$userId][$accessType];
    }

    /**
     * Set whether the given user id is allowed to read this object.
     * @param string $userId
     * @param bool   $allowd
     * @throws Exception
     */
    public function setReadAccess($userId, $allowed)
    {
        if (!$userId) {
            throw new Exception('cannot setReadAccess for null userId');
        }
        $this->setAccess('read', $userId, $allowed);
    }

    /**
     * Get whether the given user id is allowed to read this object
     * @param string $userId User id.
     * @throws \Exception
     * @return bool
     */
    public function getReadAccess($userId)
    {
        if (!$userId) {
            throw new Exception('cannot getReadAccess for null userId');
        }

        return $this->getAccess('read', $userId);
    }

    /**
     * Set whether the given user id is allowed to write this object.
     * @param string $userId
     * @param bool   $allowd
     * @throws Exception
     */
    public function setWriteAccess($userId, $allowed)
    {
        if (!$userId) {
            throw new Exception('cannot setWriteAccess for null userId');
        }
        $this->setAccess('write', $userId, $allowed);
    }

    /**
     * Set whether the public is allowed to read this object.
     *
     * @param bool $allowed
     */
    public function setPublicReadAccess($allowed)
    {
        $this->setReadAccess(self::PUBLIC_KEY, $allowed);
    }

    /**
     * Get whether the public is allowed to read this object.
     *
     * @return bool
     */
    public function getPublicReadAccess()
    {
        return $this->getReadAccess(self::PUBLIC_KEY);
    }

    /**
     * Set whether the public is allowed to write this object.
     *
     * @param bool $allowed
     */
    public function setPublicWriteAccess($allowed)
    {
        $this->setWriteAccess(self::PUBLIC_KEY, $allowed);
    }

    /**
     * Get whether the public is allowed to write this object.
     *
     * @return bool
     */
    public function getPublicWriteAccess()
    {
        return $this->getWriteAccess(self::PUBLIC_KEY);
    }

    /**
     * Get whether the given role name is allowed to read this object.
     * @param string $roleName The name of the role.
     * @return bool
     */
    public function getRoleReadAccess($roleName)
    {
        return $this->getReadAccess(self::ROLE_KEY . $roleName);
    }

    /**
     * Set whether the given role name is allowed to read this object.
     * @param string $roleName The name of the role.
     * @param bool   $allowed  Whether the given role can read this object.
     */
    public function setRoleReadAccessWithName($roleName, $allowed)
    {
        $this->setReadAccess(self::ROLE_KEY . $roleName, $allowed);
    }

    /**
     * Get whether the given role name is allowed to write this object.
     * @param string $roleName The name of the role.
     * @return bool
     */
    public function getRoleWriteAccess($roleName)
    {
        return $this->getWriteAccess(self::ROLE_KEY . $roleName);
    }

    /**
     * Set whether the given role name is allowed to write this object.
     * @param string $roleName The name of the role.
     * @param bool   $allowed  Whether the given role can read this object.
     */
    public function setRoleWriteAccessWithName($roleName, $allowed)
    {
        $this->setWriteAccess(self::ROLE_KEY . $roleName, $allowed);
    }
}
