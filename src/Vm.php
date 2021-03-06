<?php declare(strict_types=1);
/**
 * BBB On Demand PHP VM Library
 *
 * Copyright (c) BBB On Demand
 * All rights reserved.
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this
 * software and associated documentation files (the "Software"), to deal in the Software
 * without restriction, including without limitation the rights to use, copy, modify, merge,
 * publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons
 * to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or
 * substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED *AS IS*, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE
 * FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */
namespace BBBondemand;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use function json_decode;

class Vm {
    public const SUCCESS_STATUS = 'success';
    public const ERR_STATUS = 'error';

    public const UNKNOWN_ERR = 1;
    public const INVALID_RESPONSE_STATUS_ERR = 2;
    public const INTERNAL_ERR = 3;
    public const INVALID_REQUEST = 4;

    /**
     * @var string
     */
    protected $customerApiToken;

    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    /**
     * @var
     */
    private $httpClient;

    /**
     * @var ResponseInterface
     */
    private $response;

    public function __construct(string $customerApiToken, callable $urlBuilder) {
        $this->customerApiToken = $customerApiToken;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Creates Vm instance using provided configuration.
     * @param array $conf
     * @return Vm
     */
    public static function mk(array $conf) {
        $urlBuilder = new UrlBuilder($conf['customerId'], $conf['baseApiUrl']);
        return new static($conf['customerApiToken'], $urlBuilder);
    }

    // ------------------------------------------------------------------------
    // # Remote API:

    // ## Billing:

    public function getBillingSummary(): array {
        $url = ($this->urlBuilder)(Endpoint::BILLING_SUMMARY);
        $result = $this->sendGet($url);
        return $this->normalizeResult($result, false);
    }

    // ## Instances:

    public function getInstances(array $queryParams = null): array {
        $queryString = http_build_query((array)$queryParams);
        $url = ($this->urlBuilder)(Endpoint::LIST_INSTANCES, null, $queryString);
        $result = $this->sendGet($url);
        return $this->normalizeResult($result, true);
    }

    public function createInstance(array $params = null): array {
        $params = array_merge(["MachineSize" => MachineSize::SMALL], (array)$params);
        $params['MachineSize'] = strtolower($params['MachineSize']);
        $url = ($this->urlBuilder)(Endpoint::CREATE_INSTANCE);
        $result = $this->sendPost($url, $params);
        return $this->normalizeResult($result, false);
    }

    public function getInstance($instanceId): array {
        $this->checkInstanceId($instanceId);
        $url = ($this->urlBuilder)(Endpoint::GET_INSTANCE, ['instanceID' => $instanceId]);
        $result = $this->sendGet($url);
        return $this->normalizeResult($result, false);
    }

    public function stopInstance($instanceId): array {
        $this->checkInstanceId($instanceId);
        $url = ($this->urlBuilder)(Endpoint::STOP_INSTANCE);
        $result = $this->sendPost($url, ['instanceID' => $instanceId]);
        return $this->normalizeResult($result, false);
    }

    public function deleteInstance($instanceId): array {
        $this->checkInstanceId($instanceId);
        $url = ($this->urlBuilder)(Endpoint::DELETE_INSTANCE, ['instanceID' => $instanceId]);
        $result = $this->sendDelete($url);
        return $this->normalizeResult($result, false);
    }

    public function startInstance($instanceId): array {
        $this->checkInstanceId($instanceId);
        $url = ($this->urlBuilder)(Endpoint::START_INSTANCE);
        $result = $this->sendPost($url, ['instanceID' => $instanceId]);
        return $this->normalizeResult($result, false);
    }

    public function getInstanceHistory($instanceId) {
        $url = ($this->urlBuilder)(Endpoint::INSTANCE_HISTORY, ['instanceID' => $instanceId]);
        $result = $this->sendGet($url);
        return $this->normalizeResult($result, false);
    }

    // ## Meetings:

    public function getMeetings(array $queryParams = null): array {
        $queryString = http_build_query((array)$queryParams);
        $url = ($this->urlBuilder)(Endpoint::LIST_MEETINGS, null, $queryString);
        $result = $this->sendGet($url);
        return $this->normalizeResult($result, true);
    }

    public function getMeeting($meetingId): array {
        $url = ($this->urlBuilder)(Endpoint::GET_MEETING, ['meetingID' => $meetingId]);
        $result = $this->sendGet($url);
        return $this->normalizeResult($result, false);
    }

    // ## Recordings:

    public function getRecordings(): array {
        $url = ($this->urlBuilder)(Endpoint::LIST_RECORDINGS);
        $result = $this->sendGet($url);
        return $this->normalizeResult($result, true);
    }

    public function getRecording($recordingId): array {
        $this->checkRecordingId($recordingId);
        $url = ($this->urlBuilder)(Endpoint::GET_RECORDING, ['recordingID' => $recordingId]);
        $result = $this->sendGet($url);
        return $this->normalizeResult($result, false);
    }

    public function unpublishRecording($recordingId): array {
        $this->checkRecordingId($recordingId);
        $url = ($this->urlBuilder)(Endpoint::UNPUBLISH_RECORDING);
        $result = $this->sendPost($url, ['recordingID' => $recordingId]);
        return $this->normalizeResult($result, false);
    }

    public function deleteRecording($recordingId): array {
        $this->checkRecordingId($recordingId);
        $url = ($this->urlBuilder)(Endpoint::DELETE_RECORDING, ['recordingID' => $recordingId]);
        $result = $this->sendDelete($url);
        return $this->normalizeResult($result, false);
    }

    public function publishRecording($recordingId) {
        $this->checkRecordingId($recordingId);
        $url = ($this->urlBuilder)(Endpoint::PUBLISH_RECORDING);
        $result = $this->sendPost($url, ['recordingID' => $recordingId]);
        return $this->normalizeResult($result, false);
    }

    // ## Regions:

    public function getRegions(): array {
        $url = ($this->urlBuilder)(Endpoint::LIST_REGIONS);
        $result = $this->sendGet($url);
        return $this->normalizeResult($result, true);
    }

    // ------------------------------------------------------------------------
    // # Utility methods:

    public function setUrlBuilder(callable $urlBuilder) {
        $this->urlBuilder = $urlBuilder;
        return $this;
    }

    public function getUrlBuilder(): callable {
        return $this->urlBuilder;
    }

    /**
     * @param string $httpMethod
     * @param string $url
     * @param array|null $payloadData
     * @return array
     */
    public function send(string $httpMethod, string $url, array $payloadData = null): array {
        $requestOptions = ['verify' => false];
        if ($payloadData) {
            $requestOptions['json'] = (array)$payloadData;
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
            return $this->mkErrResult(self::INTERNAL_ERR, (string)$e);
        }
    }

    /**
     * @return ResponseInterface|null
     */
    public function getLastResponse() {
        return $this->response;
    }

    public function setHttpClient(ClientInterface $httpClient) {
        $this->httpClient = $httpClient;
        return $this;
    }

    public function getHttpClient(): ClientInterface {
        if (null === $this->httpClient) {
            $this->httpClient = $this->mkHttpClient();
        }
        return $this->httpClient;
    }

    protected function mkHttpClient(): ClientInterface {
        return new Client(['headers' => [
            'APITOKEN' => $this->customerApiToken
        ]]);
    }

    private function sendDelete(string $url): array {
        return $this->send('DELETE', $url);
    }

    private function sendGet(string $url): array {
        return $this->send('GET', $url);
    }

    private function sendPatch(string $url, array $payloadData): array {
        return $this->send('PATCH', $url, $payloadData);
    }

    private function sendPut(string $url, array $payloadData): array {
        return $this->send('PUT', $url, $payloadData);
    }

    private function sendPost(string $url, array $payloadData = null): array {
        return $this->send('POST', $url, $payloadData);
    }

    private function checkResponse($response, Exception $ex = null): array {
        if ($response) {
            $contents = $response->getBody()->getContents();
            if (!$contents) {
                return $this->mkErrResult(self::UNKNOWN_ERR, 'Unknown error');
            }
            $responsePayload = json_decode($contents, true);
            if (null === $responsePayload && $response->getStatusCode() === 403) {
                return $this->mkErrResult(self::INVALID_REQUEST, 'Forbidden');
            }
            if (!isset($responsePayload['status']) || ($responsePayload['status'] !== self::SUCCESS_STATUS && $responsePayload['status'] !== self::ERR_STATUS)) {
                return $this->mkErrResult(self::INVALID_RESPONSE_STATUS_ERR, "The 'status' field either empty or has invalid value");
            }
            if (!$ex) {
                return $responsePayload; // it is a valid response, return it as is.
            }
            if ($responsePayload['status'] === self::ERR_STATUS) {
                return $responsePayload;
            }
        }
        return $this->mkErrResult(self::UNKNOWN_ERR, 'Unknown error');
    }

    private function mkErrResult(int $errCode, string $message): array {
        return [
            'data' => null,
            'message' => '[ERR:' . $errCode . '] ' . (string)$message,
            'status' => self::ERR_STATUS,
        ];
    }

    private function normalizeResult(array $result, bool $dataIsCollection): array {
        if (array_key_exists('data', $result) && ($result['data'] === null || $result['data'] === '')) {
            $result['data'] = $dataIsCollection ? [] : null;
        }
        return $result;
    }

    /**
     * @param string $instanceId
     * @throws InvalidArgumentException
     */
    private function checkInstanceId(string $instanceId): void {
        if ('' === $instanceId) {
            throw new InvalidArgumentException("Invalid instance name: can't be blank");
        }
        if (preg_match('~[A-Z]~s', $instanceId)) {
            throw new InvalidArgumentException("Invalid instance name: must be in lower case");
        }
        if (strlen($instanceId) < 19 || strlen($instanceId) > 22) {
            throw new InvalidArgumentException("Invalid instance name: the length must be between 19 and 22");
        }
    }

    /**
     * @param string $recordingId
     * @throws InvalidArgumentException
     */
    private function checkRecordingId(string $recordingId) {
        if ('' === $recordingId) {
            throw new InvalidArgumentException("Invalid recording ID: can't be blank");
        }
        if (preg_match('~[A-Z]~s', $recordingId)) {
            throw new InvalidArgumentException("Invalid recording ID: must be in lower case");
        }
        if (strlen($recordingId) !== 54) {
            throw new InvalidArgumentException("Invalid recording ID: the length must be exactly 54");
        }
    }
}