<?php declare(strict_types=1);
/**
 *  BBB On Demand VM library for PHP
 *
 *  This allows customers to create and manage their own, dedicated virtual servers running BBB. So the '/bigbluebutton/api' end *  point is used
 *  to manage meetings using a standard BBB library or integration; the /vm endpoint is used to manage your own virtual machines - and you would
 *  then use a BBB library to interact with the actual BBB instance running on each machine.
 *
 * @author Richard Phillips
 */

namespace BBBondemand;

use BBBondemand\Enums\InstancesApiRoute;
use BBBondemand\Enums\MeetingsApiRoute;
use BBBondemand\Enums\RecordingsApiRoute;
use BBBondemand\Enums\RegionsApiRoute;
use BBBondemand\Util\UrlBuilder;
use GuzzleHttp\Client;

class VM
{

    protected $customerID;
    protected $customerApiToken;
    protected $apiServerBaseUrl;
    protected $urlBuilder;

    public function __construct($customer, $api)
    {
        $this->customerID       = $customer;
        $this->customerApiToken = $api;
        $this->apiServerBaseUrl = 'https://bbbondemand.com/api/v1';
        $this->urlBuilder       = new UrlBuilder($this->customerID, $this->apiServerBaseUrl);
    }

    public function getRegions()
    {
        return $this->executeApiCall($this->urlBuilder->buildUrl(RegionsApiRoute::LIST));
    }

    public function executeApiCall($url, $requestType = 'GET', array $paramJson, $getJson = true)
    {
        $client = new Client([
            'headers' => $this->getApiCallHeaders()
        ]);

        try {
            $responseJson = $client->request($requestType, $url, $this->getApiCallParams($paramJson))
                                   ->getBody()
                                   ->getContents();

            if ($getJson) {
                $responseJson = json_decode($responseJson, true);
            }
        } catch (\Exception $e) {
            $response     = $e->getResponse();
            $responseJson = json_decode($response->getBody()
                                                 ->getContents(), true);
        }

        return $responseJson;
    }

    public function getApiCallHeaders(): array
    {
        return [
            'APITOKEN' => $this->customerApiToken
        ];
    }

    public function getApiCallParams($params)
    {
        $returnParams['verify'] = false;

        if (!empty($params)) {
            $returnParams['json'] = $params;
        }

        return $returnParams;
    }

    public function getInstances($params = [])
    {
        return $this->executeApiCall($this->urlBuilder->buildUrl(InstancesApiRoute::LIST, [], http_build_query($params)));
    }

    public function createInstance($param = ["MachineSize" => "small"])
    {
        return $this->executeApiCall($this->urlBuilder->buildUrl(InstancesApiRoute::LIST), "POST", $param);
    }

    public function getInstanceByName($instanceName)
    {
        $param['name'] = $instanceName;

        return $this->executeApiCall($this->urlBuilder->buildUrl(InstancesApiRoute::GET, $param));
    }

    public function deleteInstanceByName($instanceName)
    {
        $this->instanceNameValidate($instanceName);
        $param['name'] = $instanceName;

        return $this->executeApiCall($this->urlBuilder->buildUrl(InstancesApiRoute::DELETE, $param), "DELETE");
    }

    public function instanceNameValidate($instanceName)
    {
        if ($instanceName == "") {
            throw new \RuntimeException("instance name can't be blank");
        }

        if (strlen($instanceName) < 19 || strlen($instanceName) > 22) {
            throw new \RuntimeException("invalid instance name: the length must be between 19 and 22");
        }

        return true;
    }

    public function startInstanceByName($instanceName)
    {
        $this->instanceNameValidate($instanceName);
        $param['name'] = $instanceName;

        return $this->executeApiCall($this->urlBuilder->buildUrl(InstancesApiRoute::START, $param));
    }

    public function stopInstanceByName($instanceName)
    {
        $this->instanceNameValidate($instanceName);
        $param['name'] = $instanceName;

        return $this->executeApiCall($this->urlBuilder->buildUrl(InstancesApiRoute::STOP, $param));
    }

    public function getMeetings()
    {
        return $this->executeApiCall($this->urlBuilder->buildUrl(MeetingsApiRoute::LIST));
    }

    public function getRecordings()
    {
        return $this->executeApiCall($this->urlBuilder->buildUrl(RecordingsApiRoute::LIST));
    }

    public function getRecordingById($recordingId)
    {
        $param['recordingID'] = $recordingId;

        return $this->executeApiCall($this->urlBuilder->buildUrl(RecordingsApiRoute::GET, $param));
    }

}