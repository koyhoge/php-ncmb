<?php
namespace Ncmb;

/**
 * Representation of a NCMB GeoPoint object
 */
class GeoPoint implements Encodable
{
    /** @var float latitude */
    protected $latitude;

    /** @var float longtitude */
    protected $longtitude;

    /**
     * Create a NCMB GeoPoint object.
     *
     * @param float $lat Latitude.
     * @param float $lng Longitude.
     */
    public function __construct($lat, $lng)
    {
        $this->setLatitude($lat);
        $this->setLongitude($lng);
    }

    /**
     * Returns the Latitude value for this GeoPoint.
     *
     * @return float
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set the Latitude value for this GeoPoint.
     *
     * @param $lat
     * @throws \Ncmb\Exception
     */
    public function setLatitude($lat)
    {
        if (is_numeric($lat) && !is_float($lat)) {
            $lat = (float)$lat;
        }
        if ($lat > 90.0 || $lat < -90.0) {
            throw new Exception('Latitude must be within range [-90.0, 90.0]');
        }
        $this->latitude = $lat;
    }

    /**
     * Returns the Longitude value for this GeoPoint.
     *
     * @return float
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Set the Longitude value for this GeoPoint.
     *
     * @param $lng
     * @throws \Ncmb\Exception
     */
    public function setLongitude($lng)
    {
        if (is_numeric($lng) && !is_float($lng)) {
            $lng = (float)$lng;
        }
        if ($lng > 180.0 || $lng < -180.0) {
            throw new Exception(
                'Longitude must be within range [-180.0, 180.0]');
        }
        $this->longitude = $lng;
    }

    /**
     * Encode to associative array representation.
     *
     * @return array
     */
    public function encode()
    {
        return [
            '__type'    => 'GeoPoint',
            'latitude'  => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
