<?php
require('lib/Crowdmap.php');

$applicationPublicKey = null;
$applicationPrivateKey = null;

$requestMethod = null;
$requestResource = null;
$requestDataFormat = null;
$requestData = null;

$responseStatus = null;
$responseMessage = null;
$responseDataFormat = null;
$responseData = null;

$requestMethodValues = array('GET', 'POST', 'PUT', 'DELETE');
$requestDataFormatValues = array('JSON', 'Query String');
$responseDataFormatValues = array('JSON', 'PHP');

if (is_file('lib/spyc.php')) {
    $requestDataFormatValues[] = 'YAML';
    $responseDataFormatValues[] = 'YAML';
}

if (is_file('config.php')) {
    include('config.php');
}

if ($_POST) {

    $applicationPublicKey = $_POST['application']['public_key'];
    $applicationPrivateKey = $_POST['application']['private_key'];

    $requestMethod = $_POST['request']['method'];
    $requestResource = $_POST['request']['resource'];
    $requestDataFormat = $_POST['request']['data_format'];
    $requestData = $_POST['request']['data'];

    $responseDataFormat = $_POST['response']['data_format'];

    $Crowdmap = new Crowdmap($applicationPublicKey, $applicationPrivateKey);

    switch ($requestDataFormat) {

        case 'JSON':
            $requestDataParsed = json_decode($requestData, true);
        break;

        case 'YAML':
            if (!is_file('lib/spyc.php')) {
                throw new Exception('YAML format may not be used because Spyc parsing library is not present.');
            }
            require_once('lib/spyc.php');
            $requestDataParsed = Spyc::YAMLLoadString($requestData);
        break;

        case 'Query String':
            $requestDataParsed = array();
            parse_str($requestData, $requestDataParsed);
        break;

        default:
            throw new Exception(sprintf('Invalid request data format: "%s"', $requestDataFormat));
        break;

    }

    $response = null;
    try {
        $response = $Crowdmap->call($requestMethod, $requestResource, $requestDataParsed);
        $responseStatus = 'success';
        $responseMessage = 'Success';
    } catch (Exception $exception) {
        $responseStatus = 'error';
        $responseMessage = $exception->getMessage();
    }

    if ($response) {

        switch ($responseDataFormat) {

            case 'JSON':
                $responseData = json_encode($response);
            break;

            case 'PHP':
                $responseData = print_r($response, true);
            break;

            case 'YAML':
                if (!is_file('lib/spyc.php')) {
                    throw new Exception('YAML format may not be used because Spyc parsing library is not present.');
                }
                require_once('lib/spyc.php');
                $responseObject = json_encode($response);
                $responseArray = json_decode($responseObject, true);
                $responseData = Spyc::YAMLDump($responseArray, true);
            break;

            default:
                throw new Exception(sprintf('Invalid response data format: "%s"', $responseDataFormat));
            break;

        }

    }

}

include('index.html');