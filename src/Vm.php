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
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use function json_decode;

class Vm
{
    public const SUCCESS_RESPONSE = 'success';
    public const FAIL_RESPONSE = 'fail'; // invalid format or validation check
    public const ERR_RESPONSE = 'error'; // internal error like exception

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

    private $response;

    private const UNKNOWN_ERR = 1;
    private const INVALID_RESPONSE_STATUS_ERR = 2;
    private const INTERNAL_ERR = 3;
    private const INVALID_REQUEST = 4;

    public function __construct(string $customerApiToken, UrlBuilder $urlBuilder)
    {
        $this->customerApiToken = $customerApiToken;
        $this->urlBuilder = $urlBuilder;
    }

    // ------------------------------------------------------------------------
    // Remote API:

    public function getRecordings(): array
    {
        $url = $this->urlBuilder->buildUrl(RecordingsApiRoute::LIST);
        $response = $this->execGet($url);
        if (array_key_exists('data', $response) && $response['data'] === null) { // normalize empty result for collections
            $response['data'] = $this->mkEmptyDataResult();
        }
        return $response;
    }

    /**
     * @param string $recordingId
     * @return array
     */
    public function getRecordingById(string $recordingId): array
    {
        $url = $this->urlBuilder->buildUrl(RecordingsApiRoute::GET, ['recordingID' => $recordingId]);
        return $this->execGet($url);
    }

    public function getRegions(): array
    {
        $url = $this->urlBuilder->buildUrl(RegionsApiRoute::LIST);
        return $this->execGet($url);
    }

    public function getMeetings(): array
    {
        $url = $this->urlBuilder->buildUrl(MeetingsApiRoute::LIST);
        return $this->execGet($url);
    }

    // ------------------------------------------------------------------------
    // Utility methods:

    /**
     * @param string $url
     * @param array|null $params
     * @return array
     */
    public function execGet(string $url, array $params = null): array
    {
        return $this->exec('GET', $url, $params);
    }

    /**
     * @param string $httpMethod
     * @param string $url
     * @param array|null $params
     * @return array
     */
    public function exec(string $httpMethod, string $url, array $params = null): array
    {
        $requestOptions = ['verify' => false];
        if ($params) {
            $requestOptions['json'] = (array)$params;
        }
        try {
            $httpClient = $this->getHttpClient();
            $response = $httpClient->request($httpMethod, $url, $requestOptions);
            $this->response = $response;
            return $this->checkResponse($response);
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $this->response = $response;
            return $this->checkResponse($response, $e);
        } /** @noinspection PhpUndefinedClassInspection */ catch (GuzzleException $e) {
            return $this->mkErrResult(self::INTERNAL_ERR, $e, self::UNKNOWN_ERR);
        }
    }

    /**
     * @return ResponseInterface|null
     */
    public function getResponse()
    {
        return $this->response;
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

    private function checkResponse($response, Exception $ex = null)
    {
        if ($response) {
            $contents = $response->getBody()->getContents();
            if (!$contents) {
                return $this->mkErrResult(self::UNKNOWN_ERR, 'Unknown error', self::ERR_RESPONSE);
            }
            $responsePayload = json_decode($contents, true);
            if (null === $responsePayload && $response->getStatusCode() === 403) {
                return $this->mkErrResult(self::INVALID_REQUEST, 'Forbidden', self::FAIL_RESPONSE);
            }
            if (!isset($responsePayload['status']) || ($responsePayload['status'] !== self::SUCCESS_RESPONSE && $responsePayload['status'] !== self::FAIL_RESPONSE && $responsePayload['status'] !== self::ERR_RESPONSE)) {
                return $this->mkErrResult(self::INVALID_RESPONSE_STATUS_ERR, "The 'status' field either empty or has invalid value", self::ERR_RESPONSE);
            }
            if (!$ex) {
                return $responsePayload; // it is a valid response, return it as is.
            }
            if ($responsePayload['status'] === self::ERR_RESPONSE || $responsePayload['status'] === self::FAIL_RESPONSE) {
                return $responsePayload;
            }
        }
        return $this->mkErrResult(self::UNKNOWN_ERR, 'Unknown error', self::ERR_RESPONSE);
    }

    private function mkErrResult(int $errCode, $message, $status): array
    {
        return [
            'data' => '[ERR:' . $errCode . '] ' . (string) $message,
            //'message' => ,
            'status' => $status,
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
        return $this->execApiCall('GET', $this->urlBuilder->buildUrl(InstancesApiRoute::LIST, [], http_build_query($params)));
    }

    public function createInstance($param = ["MachineSize" => "small"])
    {
        return $this->execApiCall($this->urlBuilder->buildUrl(InstancesApiRoute::LIST), "POST", $param);
    }

    public function getInstanceByName($instanceName)
    {
        $param['name'] = $instanceName;

        return $this->execApiCall($this->urlBuilder->buildUrl(InstancesApiRoute::GET, $param));
    }

    public function deleteInstanceByName($instanceName)
    {
        $this->instanceNameValidate($instanceName);
        $param['name'] = $instanceName;

        return $this->execApiCall($this->urlBuilder->buildUrl(InstancesApiRoute::DELETE, $param), "DELETE");
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

        return $this->execApiCall($this->urlBuilder->buildUrl(InstancesApiRoute::START, $param));
    }

    public function stopInstanceByName($instanceName)
    {
        $this->instanceNameValidate($instanceName);
        $param['name'] = $instanceName;

        return $this->execApiCall($this->urlBuilder->buildUrl(InstancesApiRoute::STOP, $param));
    }



}
