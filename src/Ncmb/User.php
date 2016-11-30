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
     * Retrieves the currently logged in User with a valid session,
     * either from memory or the storage provider, if necessary.
     *
     * @return \Ncmb\User|null
     */
    public static function getCurrentUser()
    {
        if (static::$currentUser instanceof self) {
            return static::$currentUser;
        }
        $storage = ApiClient::getStorage();
        $userData = $storage->get('user');
        if ($userData instanceof self) {
            static::$currentUser = $userData;

            return $userData;
        }
        return null;
    }

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

    /**
     * Signs up the current user, or throw if invalid.
     */
    public function signUp()
    {
        if (!$this->get('username')) {
            throw new Exception('Cannot sign up user with an empty name');
        }
        if (!$this->get('password')) {
            throw new Exception('Cannot sign up user with an empty password.');
        }
        if ($this->getObjectId()) {
            throw new Exception('Cannot sign up an already existing user.');
        }

        parent::save();
    }

    /**
     * Logs in and returns a valid User, or throws if invalid.
     *
     * @param string $username
     * @param string $password
     *
     * @throws Ncmb\Exception
     *
     * @return Ncmb\User
     */
    public static function login($username, $password)
    {
        if (!$username) {
            throw new Exception('Cannot log in user with an empty name');
        }
        if (!$password) {
            throw new Exception('Cannot log in user with an empty password.');
        }

        $data = ['userName' => $username, 'password' => $password];
        $result = ApiClient::request('GET', 'login', null, $data);

        $user = new static();
        $user->mergeAfterFetch($result);
        $user->handleSaveResult(true);

        return $user;
    }

    /**
     * After a save, perform User object specific logic.
     *
     * @param bool $makeCurrent Whether to set the current user.
     */
    protected function handleSaveResult($makeCurrent = false)
    {
        if (isset($this->serverData['password'])) {
            unset($this->serverData['password']);
        }
        if (isset($this->serverData['sessionToken'])) {
            $this->sessionToken = $this->serverData['sessionToken'];
            unset($this->serverData['sessionToken']);
        }
        if ($makeCurrent) {
            static::$currentUser = $this;
            static::saveCurrentUser();
        }
        $this->rebuildEstimatedData();
    }

    /**
     * Persists the current user to the storage provider.
     */
    protected static function saveCurrentUser()
    {
        $storage = ApiClient::getStorage();
        $storage->set('user', static::getCurrentUser());
    }
}
