<?php
class Crowdmap
{
    const API_SCHEME = 'https';
    const API_HOST = 'api.crowdmap.com';
    const API_PATH = '/v1';

    protected $_publicKey;

    protected $_privateKey;

    protected $_session;

    public function __construct($publicKey, $privateKey)
    {
        $this->_publicKey = $publicKey;
        $this->_privateKey = $privateKey;
    }

    protected function _generateSignature($method, $resource)
    {
        $date = time();
        return 'A' . $this->_publicKey . hash_hmac('sha1', "{$method}\n{$date}\n{$resource}\n", $this->_privateKey);
    }

    protected function _request($method, $resource, $data = array())
    {
        $url = self::API_SCHEME . '://' . self::API_HOST . self::API_PATH . $resource;
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0 Crowdmap Client';
        $headers = array('User-Agent: ' . $userAgent);

        switch ($method)
        {
            case 'GET':
            case 'DELETE':
                $options = array(
                    'method'  => $method,
                    'header'  => implode("\r\n", $headers),
                    'ignore_errors' => true,
                );
                $url .= '?' . http_build_query($data);
            break;

            case 'POST':
            case 'PUT':
                $headers[] = 'Content-type: application/x-www-form-urlencoded';
                $options = array(
                    'method'  => $method,
                    'header'  => implode("\r\n", $headers),
                    'ignore_errors' => true,
                    'content' => http_build_query($data),
                );
            break;

            default:
                throw new Exception(sprintf('Invalid method: "%s"', $method));
            break;
        }

        
        $context = stream_context_create(array('http' => $options));
        $result = @file_get_contents($url, false, $context);

        if (!$result) {
            throw new Exception(sprintf('Request Error: %s', $http_response_header[0]));
        }

        $data = @json_decode($result);

        if (!$data) {
            throw new Exception(sprintf('API Error: "%s"', $result));
        }

        return $data;
    }

    public function call($method, $resource, $data = array())
    {
        $apikey = $this->_generateSignature($method, $resource);
        $data['apikey'] = $apikey;

        if ($this->_session) {
            $data['session'] = $this->_session;
        }

        $response = $this->_request($method, $resource, $data);

        return $response;
    }

    public function login($username, $password)
    {
        $result = $this->call('POST', '/session/login/', array('username' => $username, 'password' => $password));

        if (!$result->success) {
            throw new Exception(sprintf('Failed logging in: "%s"', $result->error));
        }

        $this->_session = $result->session;

        return true;
    }

}