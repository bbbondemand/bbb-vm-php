<?php declare(strict_types=1);
namespace BBBondemand\Test;

use BBBondemand\InstancesApiRoute;
use BBBondemand\RecordingsApiRoute;
use BBBondemand\RegionsApiRoute;
use BBBondemand\UrlBuilder;
use BBBondemand\Vm;
use Composer\Util\Url;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class VmTest extends TestCase {
    private $vm;

    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    public function setUp(): void {
        parent::setUp();
        $conf = Sut::vmConf();
        $baseApiUrl = $conf['baseApiUrl'];
        $customerId = $conf['customerId'];
        $this->urlBuilder = new UrlBuilder($customerId, $baseApiUrl);
        $this->vm = new Vm($conf['customerApiToken'], $this->urlBuilder);

        startServer();
        // todo Now we are testing real service, replace the HttpClient with the stub.
        // $this->vm->setHttpClient($this->mkHttpClientStub());
    }

    public function tearDown(): void {
        parent::tearDown();
        Server::stop();
    }

    public function testHttp_Send_ReturnsErrorForInvalidUrl() {
        $baseApiUrl = Sut::vmConf('baseApiUrl');
        $result = $this->vm->send('GET', $baseApiUrl . '/non-existing/url');
        $this->checkErrorResult($result, 403, '[ERR:' . Vm::INVALID_REQUEST . '] Forbidden');
    }

    public function testHttp_Send_SuccessResult() {
        $url = $this->urlBuilder->buildUrl(RegionsApiRoute::LIST);
        $result = $this->vm->send('GET', $url);
        $this->checkSuccessResult($result);
    }

    public function testInstances_GetInstances() {
        $instances = $this->vm->getInstances();
        $this->checkSuccessResult($instances);
        $this->assertIsArray($instances['data']);
    }

    public function dataInstances_GetInstance_ServerSideChecks() {
        /*
                todo
                yield [
                    "instance name can't be blank",
                    '',
                ];*/
        yield [
            'Invalid instance name: must be in lower case',
            'fooBar',
        ];
        yield [
            'Invalid instance name: the length must be between 19 and 22',
            'foobar',
        ];
        yield [
            'Unable to find this instance',
            'testtesttesttesttest',
        ];
    }
    
    /**
     * @param string $expectedMessage
     * @param string $instanceId
     * @dataProvider dataInstances_GetInstance_ServerSideChecks
     */
    public function testInstances_GetInstance_ServerSideChecks(string $expectedMessage, string $instanceId) {
        $url = $this->urlBuilder->buildUrl(InstancesApiRoute::GET, ['instanceID' => $instanceId]);
        $result = $this->vm->send('GET', $url);
        $this->checkErrorResult($result, 400, $expectedMessage);
    }

    public function testInstances_StartInstance_UsingStubServer() {
        $expectedResponseData = [
            'startInstanceData' => 'ok',
        ];
        $this->expectResponse(InstancesApiRoute::START, $expectedResponseData);

        $instanceId = 'testtesttesttesttest';
        $result = $this->vm->startInstance($instanceId);

        $this->checkSuccessResult($result);
        $this->assertSame($expectedResponseData, $result['data']);
        // todo: check http method
    }

    public function testInstances_StartInstance_UsingClientMock() {
        $expectedResponse = $this->mkSuccessResponse([
            'startInstanceData' => 'ok',
        ]);
        $expectedResponseJson = $this->toJson($expectedResponse);
        // Create a mock and queue two responses.
        $responseHandler = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $expectedResponseJson),
            new Response(202, ['Content-Length' => strlen($expectedResponseJson)]),
            //new RequestException('Error Communicating with Server', new Request('GET', 'test'))
        ]);
        $handlerStack = HandlerStack::create($responseHandler);
        $client = new Client(['handler' => $handlerStack]);
        $this->vm->setHttpClient($client);

        $instanceId = 'testtesttesttesttest';
        $result = $this->vm->startInstance($instanceId);

        $this->assertSame($expectedResponse, $result);
        $lastRequest = $responseHandler->getLastRequest();
        $this->assertSame('POST', $lastRequest->getMethod());
        $this->assertSame(  $this->vm->getUrlBuilder()->buildUrl(InstancesApiRoute::START), $lastRequest->getUri()->__toString());
    }

    public function testInstances_StopInstance_UsingStubServer() {
        $expectedResponseData = [
            'stopInstance' => 'ok',
        ];
        $this->expectResponse(InstancesApiRoute::STOP, $expectedResponseData);

        $instanceId = 'testtesttesttesttest';
        $result = $this->vm->stopInstance($instanceId);

        $this->checkSuccessResult($result);
        $this->assertSame($expectedResponseData, $result['data']);
        // todo: check http method
    }

    public function dataInstances_GetInstance_ClientSideChecks() {
        yield [
            "Invalid instance name: can't be blank",
            '',
        ];
        yield [
            'Invalid instance name: must be in lower case',
            'fooBar',
        ];
        yield [
            'Invalid instance name: the length must be between 19 and 22',
            'foobar',
        ];
    }

    /**
     * @param string $expectedMessage
     * @param string $instanceId
     * @dataProvider dataInstances_GetInstance_ClientSideChecks
     */
    public function testInstances_GetInstance_ClientSideChecks(string $expectedMessage, string $instanceId) {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);
        $this->vm->getInstance($instanceId);
    }

    public function testRegions_GetRegions() {
        $result = $this->vm->getRegions();
        $this->checkSuccessResult($result);
        $this->assertIsArray($result['data']);
        $assertNotEmptyString = function ($val) {
            $this->assertIsString($val);
            $this->assertNotEmpty($val);
        };
        foreach ($result['data'] as $key => $val) {
            $this->assertMatchesRegularExpression('~^[-0-9a-z]+$~si', $key);
            $this->assertCount(3, $val);
            $assertNotEmptyString($val['Name']);
            $assertNotEmptyString($val['Town']);
            $assertNotEmptyString($val['Continent']);
        }
    }

    public function testRecordings_GetRecordings() {
        $result = $this->vm->getRecordings();
        $this->checkSuccessResult($result);
        $this->checkEmptyResult($result, true);
        $this->markTestIncomplete();
    }

    public function testRecordings_GetRecording_NonExistingRecording() {
        $result = $this->vm->getRecording("testtesttesttesttesttesttesttesttesttesttesttesttestte");
        $this->checkErrorResult($result, 400, 'Recording not found');
    }

    public function dataRecordings_GetRecording_ClientSideChecks() {
        yield [
            "Invalid recording ID: can't be blank",
            '',
        ];
        yield [
            "Invalid recording ID: must be in lower case",
            'someIdOfRecording',
        ];
        yield [
            "Invalid recording ID: the length must be exactly 54",
            'someidofrecording',
        ];
    }

    /**
     *
     * @dataProvider dataRecordings_GetRecording_ClientSideChecks
     * @param string $expectedMessage
     * @param string $recordingId
     */
    public function testRecordings_GetRecording_ClientSideChecks(string $expectedMessage, string $recordingId) {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);
        $this->vm->getRecording($recordingId);
    }

    public function dataRecordings_GetRecording_ServerSideChecks() {
        /* todo
        yield [
            "recording ID can't be blank",
            '',
        ];
        */
        yield [
            "Invalid recording ID: must be in lower case",
            'someIdOfRecording',
        ];
        yield [
            "Invalid recording ID: the length must be exactly 54",
            'someidofrecording',
        ];
    }

    /**
     * @dataProvider dataRecordings_GetRecording_ServerSideChecks
     * @param string $expectedMessage
     * @param string $recordingId
     */
    public function testRecordings_GetRecording_ServerSideChecks(string $expectedMessage, string $recordingId) {
        $url = $this->urlBuilder->buildUrl(RecordingsApiRoute::GET, ['recordingID' => $recordingId]);
        $result = $this->vm->send('GET', $url);
        $this->checkErrorResult($result, 400, $expectedMessage);
    }

    public function testRecordings_GetRecording_ValidRecordingId() {
        $this->markTestIncomplete();
    }

    public function testMeetings_GetMeetings() {
        $result = $this->vm->getMeetings();
        $this->checkSuccessResult($result);
        /*
                if (isset($responseMeetings['data'][0])) {
                    $this->assertContains('ReturnCode', $responseMeetings['data'][0]);
                    $this->assertContains('MeetingName', $responseMeetings['data'][0]);
                    $this->assertContains('MeetingID', $responseMeetings['data'][0]);
                    $this->assertContains('InternalMeetingID', $responseMeetings['data'][0]);
                    $this->assertContains('CreateTime', $responseMeetings['data'][0]);
                    $this->assertContains('CreateDate', $responseMeetings['data'][0]);
                    $this->assertContains('VoiceBridge', $responseMeetings['data'][0]);
                    $this->assertContains('DialNumber', $responseMeetings['data'][0]);
                    $this->assertContains('AttendeePW', $responseMeetings['data'][0]);
                    $this->assertContains('ModeratorPW', $responseMeetings['data'][0]);
                    $this->assertContains('Recording', $responseMeetings['data'][0]);
                    $this->assertContains('StartTime', $responseMeetings['data'][0]);
                    $this->assertContains('MaxUsers', $responseMeetings['data'][0]);
                }
         */
        $this->markTestIncomplete();
    }
    
    /**
     * Makes common checks for the successful result
     * @param array $result
     * @return array
     */
    private function checkSuccessResult(array $result): array {
        $this->assertSame(200, $this->vm->getLastResponse()->getStatusCode());
        $this->assertCount(2, $result);
        $this->assertSame(Vm::SUCCESS_STATUS, $result['status']);
        $this->assertIsArray($result['data']);
        $this->assertNotEmpty($result['data']);
        return $result;
    }

    /**
     * E.g. of the error result:
     * array(3) {
     *     ["status"]=> string(5) "error"
     *     ["data"]=> NULL
     *     ["message"]=> string(19) "Recording not found"
     * }
     * @param array $result
     * @param int $expectedStatusCode
     * @return array
     */
    private function checkErrorResult(array $result, int $expectedStatusCode, string $expectedMessage): array {
        $this->assertSame($expectedStatusCode, $this->vm->getLastResponse()->getStatusCode());
        $this->assertCount(3, $result);
        $this->assertNull($result['data']);
        $this->assertSame(Vm::ERR_STATUS, $result['status']);
        $this->assertSame($expectedMessage, $result['message']);
        return $result;
    }

    private function checkEmptyResult($result, bool $dataIsCollection) {
        if ($dataIsCollection) {
            $this->assertIsArray($result['data']);
        } else {
            $this->assertNull($result['data']);
        }
    }

    private function expectResponse(string $endpoint, array $expectedResponseData) {
        $expectedResponse = $this->mkSuccessResponse($expectedResponseData);
        Server::enqueueResponse($this->toJson($expectedResponse));
        $urlBuilder = $this->createStub(UrlBuilder::class);
        $urlBuilder->method('buildUrl')
            ->willReturn(Server::$url . $endpoint);
        $this->vm->setUrlBuilder($urlBuilder);
    }

    private function mkSuccessResponse($responseData): array {
        return [
            'status' => Vm::SUCCESS_STATUS,
            'data' => $responseData,
        ];
    }

    private function toJson($val): string {
        return json_encode($val, JSON_UNESCAPED_SLASHES);
    }
}