<?php declare(strict_types=1);
namespace BBBondemand;

use BBBondemand\Enums\InstancesApiRoute;
use BBBondemand\Enums\RecordingsApiRoute;
use BBBondemand\Enums\RegionsApiRoute;
use BBBondemand\Util\UrlBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class VmTest extends TestCase
{
    private $vm;

    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    public function setUp(): void
    {
        parent::setUp();
        $conf = Sut::vmConf();
        $baseApiUrl = $conf['baseApiUrl'];
        $customerId = $conf['customerId'];
        $this->urlBuilder = new UrlBuilder($customerId, $baseApiUrl);
        $this->vm = new Vm($conf['customerApiToken'], $this->urlBuilder);
        // todo Now we are testing real service, replace the HttpClient with the stub.
        // $this->vm->setHttpClient($this->mkHttpClientStub());
    }

    public function testExec_ReturnsErrorForInvalidUrl()
    {
        $baseApiUrl = Sut::vmConf('baseApiUrl');
        $result = $this->vm->exec('GET', $baseApiUrl . '/non-existing/url');
        $this->checkFailResult($result, 403);
        $this->assertSame('[ERR:' . Vm::INVALID_REQUEST . '] Forbidden', $result['data']);
    }

    public function testExec_SuccessResult()
    {
        $url = $this->urlBuilder->buildUrl(RegionsApiRoute::LIST);
        $result = $this->vm->exec('GET', $url);
        $this->checkSuccessResult($result);
    }

    public function testGetInstances()
    {
        $instances = $this->vm->getInstances();
        $this->checkSuccessResult($instances);
        $this->assertIsArray($instances['data']);
    }

    public function testGetRegions()
    {
        $this->markTestIncomplete();
    }

    public function testGetRecordings()
    {
        $result = $this->vm->getRecordings();
        $this->checkSuccessResult($result);
        $this->checkEmptyResult($result, true);
        $this->markTestIncomplete();
    }

    public function testGetRecordingById_NonExistingRecording()
    {
        $result = $this->vm->getRecordingById("testtesttesttesttesttesttesttesttesttesttesttesttestte");
        $this->checkFailResult($result, 400);
        $this->assertStringContainsString('unable to find recording', $result['data']);
    }

    public function data_testGetRecordingById_ClientSideChecks()
    {
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
     * @dataProvider data_testGetRecordingById_ClientSideChecks
     * @param string $expectedMessage
     * @param string $recordingId
     */
    public function testGetRecordingById_ClientSideChecks(string $expectedMessage, string $recordingId)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);
        $this->vm->getRecordingById($recordingId);
    }

    public function data_testGetRecordingById_ServerSideChecks()
    {
        /* todo
        yield [
            "recording ID can't be blank",
            '',
        ];
        */
        yield [
            "invalid recording ID: must be in lower case",
            'someIdOfRecording',
        ];
        yield [
            "invalid recording ID: the length must be exactly 54",
            'someidofrecording',
        ];
    }

    /**
     * @dataProvider data_testGetRecordingById_ServerSideChecks
     * @param string $expectedMessage
     * @param string $recordingId
     */
    public function testGetRecordingById_ServerSideChecks(string $expectedMessage, string $recordingId)
    {
        $url = $this->urlBuilder->buildUrl(RecordingsApiRoute::GET, ['recordingID' => $recordingId]);
        $result = $this->vm->execGet($url);
        $this->checkFailResult($result, 400);
        $this->assertSame($expectedMessage, $result['data']);
    }

    public function testGetRecordingById_ValidRecordingId()
    {
        $this->markTestIncomplete();
    }

    public function testGetMeetings()
    {
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

    public function data_testGetInstanceByName_ClientSideChecks()
    {
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
     * @param string $instanceName
     * @dataProvider data_testGetInstanceByName_ClientSideChecks
     */
    public function testGetInstanceByName_ClientSideChecks(string $expectedMessage, string $instanceName)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);
        $this->vm->getInstanceByName($instanceName);
    }

    public function data_testGetInstanceByName_ServerSideChecks()
    {
        /*
                todo
                yield [
                    "instance name can't be blank",
                    '',
                ];*/
        yield [
            'invalid instance name: must be in lower case',
            'fooBar',
        ];
        yield [
            'invalid instance name: the length must be between 19 and 22',
            'foobar',
        ];
        yield [
            'Unable to find this instance',
            'testtesttesttesttest',
        ];
    }

    /**
     * @param string $expectedMessage
     * @param string $instanceName
     * @dataProvider data_testGetInstanceByName_ServerSideChecks
     */
    public function testGetInstanceByName_ServerSideChecks(string $expectedMessage, string $instanceName)
    {
        $url = $this->urlBuilder->buildUrl(InstancesApiRoute::GET, ['name' => $instanceName]);
        $result = $this->vm->execGet($url);
        $this->checkFailResult($result, 400);
        $this->assertSame($expectedMessage, $result['data']);
    }

    /**
     * Makes common checks for the successful result
     * @param array $result
     * @return array
     */
    private function checkSuccessResult(array $result): array
    {
        $this->assertSame(200, $this->vm->getResponse()->getStatusCode());
        $this->assertCount(2, $result);
        $this->assertSame(Vm::SUCCESS_STATUS, $result['status']);
        return $result;
    }

    /**
     * Makes common checks for the error result
     * @param array $result
     * @param int $expectedStatusCode
     * @return array
     */
    private function checkFailResult(array $result, int $expectedStatusCode): array
    {
        $this->assertSame($expectedStatusCode, $this->vm->getResponse()->getStatusCode());
        $this->assertCount(2, $result);
        $this->assertSame(Vm::FAIL_STATUS, $result['status']);
        return $result;
    }

    private function checkEmptyResult($result, bool $dataIsCollection)
    {
        if ($dataIsCollection) {
            $this->assertIsArray($result['data']);
        } else {
            $this->assertNull($result['data']);
        }
    }
}
