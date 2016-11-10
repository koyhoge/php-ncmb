<?php

namespace Ncmb;

/**
 * Encodable Interface - Interface for NCMBe Classes which provide an encode
 * method.
 *
 */
interface Encodable
{
    /**
     * Returns an associate array encoding of the implementing class.
     *
     * @return mixed
     */
    public function encode();
}
