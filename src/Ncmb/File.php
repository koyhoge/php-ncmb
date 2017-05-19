<?php
namespace Ncmb;

/**
 * File  - Handle NCMB File Object
 */
class File
{
    const MIME_TYPE = 'multipart/form-data';

    const PATH_PREFIX = 'files';

    /**
     * The filename.
     *
     * @var string
     */
    private $name = null;

    /**
     * The URL of file data stored on NCMB.
     * @var string
     */
    private $url = null;

    /**
     * ACL Object for this file.
     * @var \Ncmb\Acl
     */
    private $acl = null;

    /**
     * The data.
     * @var string
     */
    private $data = null;

    /**
     * Return the data for the file, downloading it if not already present.
     *
     * @throws \Ncmb\Exception
     * @return mixed
     */
    public function getData()
    {
        if ($this->data) {
            return $this->data;
        }
        if (!$this->name) {
            throw new Exception('Cannot retrieve data from nonamed file.');
        }
        $this->data = $this->download();
        return $this->data;
    }

    /**
     * Return the URL for the file, if saved.
     *
     * @return string|null
     */
    public function getURL()
    {
        return $this->url;
    }

    /**
     * Return the name for the file
     * Upon saving to NCMB, the name will change to a unique identifier.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set ACL to File object
     * @param \Ncmb\Acl $acl
     */
    public function setAcl($acl)
    {
        $this->acl = $acl;
    }

    /**
     * Returns ACL of the File object
     * @return \Ncmb\Acl
     */
    public function getAcl()
    {
        return $this->acl;
    }

    /**
     * Send a REST request to delete the File.
     *
     * @throws \Ncmb\Exception
     */
    public function delete()
    {
        if (empty($this->name)) {
            throw new Exception('Cannot delete nonexitent file.');
        }
        $apiPath = self::PATH_PREFIX . '/' . $this->name;
        ApiClient::delete($apiPath);
    }

    /**
     * Create a File from data
     *
     * @param mixed  $contents The file contents
     * @param string $name     The file name on NCMB
     * @param \Ncmb\Acl $acl   ACL object.
     *
     * @return \Ncmb\File
     */
    public static function createFromData($contents, $name, $acl = null)
    {
        $file = new self();
        $file->name = $name;
        $file->data = $contents;
        if (!empty($acl)) {
            $file->acl = acl;
        }
        return $file;
    }

    /**
     * Create a File from the contents of a local file.
     *
     * @param string $path Path to local file
     * @param string $name Filename to use on NCMB
     * @param \Ncmb\Acl $acl ACL object
     * @return \Ncmb\File
     */
    public static function createFromFile($path, $name, $acl = null)
    {
        $contents = file_get_contents($path, 'rb');

        return static::createFromData($contents, $name, $acl);
    }

    /**
     * Instantiate File objet from file name on NCMB.
     * @param string $name filename on NCMB
     * @return \Ncmb\File
     */
    public static function createFromServer($name)
    {
        $file = new self();
        $file->name = $name;
        return $file;
    }

    /**
     * Uploads the file contents to Parse, if not saved.
     *
     * @return bool
     */
    public function save()
    {
        if (!$this->url) {
            $response = $this->upload();
            $this->url = $response['url'];
            $this->name = $response['name'];
        }
        return true;
    }

    private function upload()
    {
        $options = [
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => $this->data,
                ]
            ],
        ];

        if ($this->acl) {
            $options['multipart'][] = [
                'name' => 'acl',
                'contents' => $this->acl->encode(),
            ];
        }
        $apiPath = self::PATH_PREFIX . '/' . $this->name;

        $returnResponse = true;
        $response = ApiClient::post($apiPath, $options, $returnResponse);

        $body = $response->getBody();
        $data = json_decode((string)$body, true);

        $result = [
            'name' => $data['fileName'],
            'url' => $response->getHeader('Location'),
        ];
        return $result;
    }

    private function download()
    {
        $apiPath = self::PATH_PREFIX . '/' . $this->name;

        $returnResponse = true;
        $response = ApiClient::get($apiPath, [], $returnResponse);

        $body = $response->getBody();
        return (string)$body;
    }

    /**
     * Return Query object to search pushes
     * @return \Ncmb\Query
     */
    public static function getQuery()
    {
        $query = new Query();
        $query->setApiPath('files');
        return $query;
    }
}
