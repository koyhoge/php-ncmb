<?php

namespace Ncmb\Storage;

/**
 * Persistant storage implements with PHP session
 */
class Session implements \Ncmb\StorageInterface
{
    private $storageKey = 'NcmbData';

    /**
     * Constructor
     * @param string $key indetify key of storage
     */
    public function __construct($key = null)
    {
        if (!empty($key)) {
            $ths->storageKey = $this->storageKey . '_' . $key;
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new Ncmb\Exception(
                'PHP session_start() must be called first.');
        }
        if (!isset($_SESSION[$this->storageKey])) {
            $_SESSION[$this->storageKey] = [];
        }
    }

    public function set($key, $value)
    {
        $_SESSION[$this->storageKey][$key] = $value;
    }

    public function remove($key)
    {
        unset($_SESSION[$this->storageKey][$key]);
    }

    public function get($key)
    {
        if (isset($_SESSION[$this->storageKey][$key])) {
            return $_SESSION[$this->storageKey][$key];
        }
        return;
    }

    public function clear()
    {
        $_SESSION[$this->storageKey] = [];
    }

    public function save()
    {
        // No action required.
        return;
    }

    public function getKeys()
    {
        return array_keys($_SESSION[$this->storageKey]);
    }

    public function getAll()
    {
        return $_SESSION[$this->storageKey];
    }
}
