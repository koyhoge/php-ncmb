<?php

namespace Ncmb;

/**
 * Wrapper for \Exception class.
 */
class Exception extends \Exception
{
    /**
     * Constructs a Ncmb\Exception.
     *
     * @param string     $message  Message for the Exception.
     * @param int        $code     Error code.
     * @param \Exception $previous Previous Exception.
     */
    public function __construct($message, $code = 0,
                                \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
