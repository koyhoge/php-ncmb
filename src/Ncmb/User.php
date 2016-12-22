<?php

namespace Ncmb;

/**
 * User - Representation of a user object stored on NCMB
 */
class User extends Object
{
    public static $ncmbClassName = 'user';
    public static $apiPath = 'users';

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
     * @param string|null $objectId objectId of this object
     */
    public function __construct($objectId = null)
    {
        parent::__construct(self::$ncmbClassName, $objectId);
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
     * Get session token
     */
    public function getSessionToken()
    {
        return $this->sessionToken;
    }

    /**
     * Signs up the current user, or throw if invalid.
     */
    public function signUp()
    {
        if (!$this->get('userName')) {
            throw new Exception('Cannot sign up user with an empty name');
        }
        if (!$this->get('password')) {
            throw new Exception('Cannot sign up user with an empty password.');
        }
        if ($this->getObjectId()) {
            throw new Exception('Cannot sign up an already existing user.');
        }

        parent::save();
        $this->handleSaveResult(true);
        return $this;
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

        $data = [
            'query' => [
                'userName' => $username,
                'password' => $password,
            ],
        ];
        $result = ApiClient::get('login', $data);

        $user = new static();
        $user->mergeAfterFetch($result);
        $user->handleSaveResult(true);

        return $user;
    }

    /**
     * Login with Facebook
     * @param string $id Facebook user id
     * @param string $accessToken the access token for this session
     * @param \DateTime $expirationDate expiration date
     */
    public static function loginWithFacebook($id, $accessToken,
                                             $expirationDate = null)
    {
        if (!$id) {
            throw new Exception('Cannot log in Facebook user without an id.');
        }
        if (!$expirationDate) {
            $expirationDate = new \DateTime();
            // default 60 days
            $expirationDate->setTimestamp(time() * 86400 * 60);
        }
        $data = [
            'facebook' => [
                'id' => $id,
                'access_token' => $accessToken,
                'expiration_date' => Encoder::getProperDateFormat($expirationDate),
            ],
        ];

        $this->set('authData', $data);
        $this->handleSaveResult(true);
        return $this;
    }

    /**
     * Login with Twitter
     * @param string $id Twitter user id
     * @param string $screenName the screen name of twitter user
     * @param string $oauthConsumerKey consumer key
     * @param string $consumerSecret
     * @param string $oauthToken
     * @param string $oauthTokenSecret
     */
    public static function loginWithTwitter(
        $id,
        $screenName,
        $oauthConsumerKey,
        $consumerSecret,
        $oauthToken,
        $oauthTokenSecret
    ) {
        if (!$id) {
            throw new Exception('Cannot log in Twitter user without an id.');
        }
        $data = [
            'twitter' => [
                'id' => $id,
                'screen_name' => $screenName,
                'oauth_consumer_key' => $oauthConsumerKey,
                'consumer_secret' => $consumerSecret,
                'oauth_token' => $oauthToken,
                'oauth_token_secret' => $oauthTokenSecret,
            ],
        ];

        $this->set('authData', $data);
        $this->handleSaveResult(true);
        return $this;
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
