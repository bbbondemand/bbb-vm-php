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
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
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
    /**
     * @var
     */
    private $httpClient;

    private const UNKNOWN_ERR = 1;
    private const INVALID_RESPONSE_STATUS_ERR = 2;
    private const INTERNAL_ERR = 3;

    public const SUCCESS_RESPONSE = 'success';
    public const FAIL_RESPONSE = 'fail';

    public function __construct(string $customerApiToken, UrlBuilder $urlBuilder)
    {
        $this->customerApiToken = $customerApiToken;
        $this->urlBuilder = $urlBuilder;
    }

    // ------------------------------------------------------------------------
    // Remote API:

    public function getRecordings()
    {
        $url = $this->urlBuilder->buildUrl(RecordingsApiRoute::LIST);
        $response = $this->executeGetApiCall($url);
        if (array_key_exists('data', $response) && $response['data'] === null) { // normalize empty result for collections
            $response['data'] = $this->mkEmptyDataResult();
        }
        return $response;
    }

    public function getRecordingById($recordingId)
    {
        $param['recordingID'] = $recordingId;

        return $this->executeApiCall($this->urlBuilder->buildUrl(RecordingsApiRoute::GET, $param));
    }

    public function getRegions(): array
    {
        $url = $this->urlBuilder->buildUrl(RegionsApiRoute::LIST);
        return $this->executeGetApiCall($url);
    }

    // ------------------------------------------------------------------------
    // Utility methods:

    /**
     * @param string $url
     * @param array|null $params
     * @return array
     */
    public function executeGetApiCall(string $url, array $params = null): array
    {
        return $this->executeApiCall('GET', $url, $params);
    }

    /**
     * @param string $httpMethod
     * @param string $url
     * @param array|null $params
     * @return array
     */
    public function executeApiCall(string $httpMethod, string $url, array $params = null): array
    {
        $requestOptions = ['verify' => false];
        if ($params) {
            $requestOptions['json'] = (array)$params;
        }
        try {
            $httpClient = $this->getHttpClient();
            $response = $httpClient->request($httpMethod, $url, $requestOptions);
            return $this->checkResponse($response, false);
        } catch (RequestException $e) {
            $response = $e->getResponse();
            return $this->checkResponse($response, true);
        } /** @noinspection PhpUndefinedClassInspection */ catch (GuzzleException $e) {
            return $this->mkErrResult(self::INTERNAL_ERR, 'Internal error has occurred');
        }
    }

    public function setHttpClient(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        return $this;
    }

    public function getHttpClient(): ClientInterface
    {
        if (null === $this->httpClient) {
            $this->httpClient = $this->mkHttpClient();
        }
        return $this->httpClient;
    }

    protected function mkHttpClient(): ClientInterface
    {
        return new Client(['headers' => [
            'APITOKEN' => $this->customerApiToken
        ]]);
    }

    private function checkResponse($response, bool $isErr)
    {
        if ($response) {
            $responsePayload = json_decode($response->getBody()->getContents(), true);
            if (!isset($responsePayload['status']) || ($responsePayload['status'] !== self::SUCCESS_RESPONSE && $responsePayload['status'] !== self::FAIL_RESPONSE)) {
                if (isset($responsePayload['message'])) {
                    return $this->mkErrResult(self::INVALID_RESPONSE_STATUS_ERR, $responsePayload['message']);
                }
                return $this->mkErrResult(self::INVALID_RESPONSE_STATUS_ERR, "The 'status' field either empty or has invalid value");
            }
            if (!$isErr) {
                return $responsePayload; // it is a valid response, return it as is.
            }
        }
        return $this->mkErrResult(self::UNKNOWN_ERR, 'Unknown error');
    }

    private function mkErrResult(int $errCode, $message): array
    {
        return [
            'data' => $this->mkEmptyDataResult(),
            'message' => '[ERR:' . $errCode . '] ' . (string) $message,
            'status' => 'fail',
        ];
    }

    /**
     * @return mixed
     */
    private function mkEmptyDataResult()
    {
        return [];
    }

    // ------------------------------------------------------------------------
    // todo:





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


}
