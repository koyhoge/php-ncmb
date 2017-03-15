<?php

namespace Ncmb;

/**
 * Encoder utility class
 */
class Encoder
{
    /**
     * Encode value
     *
     * @param mixed $value             Value to encode
     * @throws \Ncmb\Exception
     * @return mixed Encoded results.
     */
    public static function encode($value)
    {
        if ($value instanceof \DateTime ||
            $value instanceof \DateTimeImmutable) {
            return [
                '__type' => 'Date', 'iso' => self::getProperDateFormat($value),
            ];
        }

        if ($value instanceof \stdClass) {
            return $value;
        }

        if ($value instanceof \Ncmb\Object) {
            return $value->toPointer();
        }

        if ($value instanceof Encodable) {
            return $value->encode();
        }

        if (is_array($value)) {
            return self::encodeArray($value);
        }

        if (!is_scalar($value) && $value !== null) {
            throw new Exception('Invalid type encountered.');
        }
        return $value;
    }

    /**
     * Encode arrays
     *
     * @param array $value             Array to encode.
     * @return array Encoded results.
     */
    public static function encodeArray($value)
    {
        $output = [];
        foreach ($value as $key => $item) {
            $output[$key] = self::encode($item);
        }
        return $output;
    }

    /**
     * Get a date value in the format stored on NCMB.
     * PHP provides 6 digits for the microseconds (u) so we have to chop 3 off.
     *
     * @param \DateTime $value DateTime value to format.
     * @return string
     */
    public static function getProperDateFormat(\DateTime $value)
    {
        // convert to UTC if needed
        if ($value->getTimezone()->getName() !== 'UTC') {
            $datetime = clone $value;
            $datetime->setTimezone(new \DateTimeZone('UTC'));
        } else {
            $datetime = $value;
        }

        $dateFormatString = 'Y-m-d\TH:i:s.u';
        $date = $datetime->format($dateFormatString);
        $date = substr($date, 0, -3).'Z';

        return $date;
    }
}
