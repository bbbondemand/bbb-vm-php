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
use GuzzleHttp\Exception\RequestException;
use RuntimeException;
use function json_decode;

class Vm
{
    /**
     * @var string
     */
    protected $customerApiToken;

    /**
     * @var UrlBuilder
     */
    protected $urlBuilder;

    public function __construct(string $customerApiToken, UrlBuilder $urlBuilder)
    {
        $this->customerApiToken = $customerApiToken;
        $this->urlBuilder = $urlBuilder;
    }

    public function getRegions()
    {
        $url = $this->urlBuilder->buildUrl(RegionsApiRoute::LIST);
        return $this->getApiCall($url);
    }

    // ------------------------------------------------------------------------
    // Utility methods:

    /**
     * @param string $url
     * @param array|null $params
     * @return mixed
     */
    public function getApiCall(string $url, array $params = null)
    {
        return $this->executeApiCall('GET', $url, $params);
    }

    /**
     * @param string $httpMethod
     * @param string $url
     * @param array|null $params
     * @return mixed
     */
    public function executeApiCall(string $httpMethod, string $url, array $params = null)
    {
        $client = new Client(['headers' => $this->getApiCallHeaders()]);

        $requestOptions = ['verify' => false];
        if ($params) {
            $requestOptions['json'] = (array) $params;
        }
        try {
            $response = $client->request($httpMethod, $url, $requestOptions);
            $responsePayload = $response->getBody()->getContents();
            $result = json_decode($responsePayload,true);
        } catch (RequestException $e) {
            $response = $e->getResponse();
            if ($response) {
                $result = json_decode($response->getBody()->getContents(), true);
            } else {
                $result = [
                    'data' => [],
                    'message' => $e->getMessage(),
                    'status' => 'fail',
                ];
            }
        }
        return $result;
    }

    // ------------------------------------------------------------------------
    // API:

    public function getApiCallHeaders(): array
    {
        return [
            'APITOKEN' => $this->customerApiToken
        ];
    }

    public function getInstances($params = [])
    {
        return $this->executeApiCall('GET', $this->urlBuilder->buildUrl(InstancesApiRoute::LIST, [], http_build_query($params)));
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
            throw new RuntimeException("instance name can't be blank");
        }

        if (strlen($instanceName) < 19 || strlen($instanceName) > 22) {
            throw new RuntimeException("invalid instance name: the length must be between 19 and 22");
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
