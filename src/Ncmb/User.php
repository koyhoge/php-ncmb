<?php

namespace Ncmb;

/**
 * User - Representation of a user object stored on NCMB
 */
class User extends Object
{
    public static $ncmbClassName = 'user';

    /**
     * The currently logged-in user
     * @var User
     */
    protected static $currentUser = null;

    /**
     * The sessionToken for an authenticate user
     * @var string
     */
    protected $sessionToken = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__consturuct(self::$ncmbClassName);
    }

    /**
     * Get path string
     * @return string path string
     */
    public function getApiPath()
    {
        return 'users';
    }

    /**
     * Returns the user name.
     * @return string|null
     */
    public function getUserName()
    {
        return $this->get('userName');
    }

    /**
     * Set the user name for the User
     * @param string $userName The user name
     */
    public function setUserName($userName)
    {
        $this->set('userName', $userName);
    }

    /**
     * Set the password for the User
     * @param string $password The password
     */
    public function setPassword($password)
    {
        $this->set('password', $password);
    }

    /**
     * Returns the email address
     * @return string|null
     */
    public function getMailAddress()
    {
        return $this->get('mailAddress');
    }

    /**
     * Set the email address for the User
     * @param string $mailAddress The mail address
     */
    public function setMailAddress($mailAddress)
    {
        $this->set('mailAddress', $mailAddress);
    }

    /**
     * Checks whether this user has been authenticated.
     */
    public function isAuthenticated()
    {
        return $this->sessionToken !== null;
    }
}
