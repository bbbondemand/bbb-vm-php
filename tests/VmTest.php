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
    /*
    private $startInstanceName;
    private $stopInstanceName;
    private $deleteInstanceName;
    private $instanceName;
    */
    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    public function setUp(): void
    {
        parent::setUp();
        $conf = Sut::vmConf();
        /*
        $this->startInstanceName = $conf['startInstanceName'];
        $this->stopInstanceName = $conf['stopInstanceName'];
        $this->deleteInstanceName = $conf['deleteInstanceName'];
        $this->instanceName = $conf['instanceName'];
        */
        $baseApiUrl = $conf['baseApiUrl'];
        $customerId = $conf['customerId'];
        $this->urlBuilder = new UrlBuilder($customerId, $baseApiUrl);
        $this->vm = new Vm($conf['customerApiToken'], $this->urlBuilder);
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

    public function testGetRegions()
    {
        $result = $this->vm->getRegions();
        $this->assertIsArray($result);
        $this->checkSuccessResult($result);
        $this->assertSame([
            'Name' => 'europe-west3',
            'Town' => 'Germany, Frankfurt',
            'Continent' => 'Europe',
        ], $result['data']['europe-west3']);
        /*
                $firstItem = reset($responseRegions['data']);
                if (isset($firstItem)) {
                    $this->assertContains('Name', $firstItem);
                    $this->assertContains('Town', $firstItem);
                    $this->assertContains('Continent', $firstItem);
                    $this->assertContains('Zones', $firstItem);
                    $this->assertContains('Capability', $firstItem);
                    $this->assertContains('Active', $firstItem);
                    $this->assertContains('Proximate', $firstItem);
                }
         */
    }

    public function testGetRecordings()
    {
        $result = $this->vm->getRecordings();
        $this->checkSuccessResult($result);
        $this->assertIsArray($result['data']);
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

    public function testInstanceApi()
    {
        $result = $this->vm->getInstances();

        $checkGetInstancesResult = function ($result) {
            $this->checkSuccessResult($result);
            $this->assertIsArray($result['data']);
            /*if (isset($responseInstancesList['data'][0])) {
            $this->assertContains('Name', $responseInstancesList['data'][0]);
            $this->assertContains('Status', $responseInstancesList['data'][0]);
            $this->assertContains('Started', $responseInstancesList['data'][0]);
            $this->assertContains('Finished', $responseInstancesList['data'][0]);
            $this->assertContains('Seconds', $responseInstancesList['data'][0]);
            $this->assertContains('Hostname', $responseInstancesList['data'][0]);
            $this->assertContains('Secret', $responseInstancesList['data'][0]);
            $this->assertContains('Region', $responseInstancesList['data'][0]);
            $this->assertContains('MachineSize', $responseInstancesList['data'][0]);
            $this->assertContains('Turn', $responseInstancesList['data'][0]);
            }*/
            /*
            Returns summary of the instances previously created. If no parameters are passed, the response will include
            all instances - including those which have been stopped and deleted.

            Use optional parameters to filter the results - pass as json in the body of the request

                ManageRecordings - boolean - if true, includes only instances created with managedRecordings=true
                Region - string - restrict results to this region - must be valid region name from /regions
                Status - string - restrict results to instances marked as: : "STARTING", "AVAILABLE", "STOPPING", "STOPPED", "DELETED".

            The following is a description of the data returned for each instance:

                Description - As supplied by you when the instance was created.
                Finished - When was the instance deleted - Unix timestamp.
                Hostname - Fully qualified domain name of the instance.
                MachineSize - Size of the machine (small, standard, large, xlarge).
                ManagedRecordings - bool, whether BBB On Demand will process / store recordings off machine
                Name - Unique name allocated to the machine by the server (21 character alphanumeric string)
                Region - Region as supplied at meeting creation. (Valid google compute region - see GET /regions)
                Seconds - Seconds between Started and Finished (stoppages not currently accounted for).
                Secret - Secret to be used to interact with BBB on this instance.
                Started - When was the meeting started - Unix timestamp.
                Status - The 'status' value is one of: "STARTING", "AVAILABLE", "STOPPING", "STOPPED", "DELETED".
                Tags - As supplied when the instance was created.
                Turn - List of stun / turn server names used for this instance.
             */
        };

        $checkGetInstancesResult($result);

        //$this->vm->createInstance([])

        /*
                $response = $this->vm->createInstance();
                $this->assertEquals('success', $response['status']);

                // todo
                $this->vm->createInstance();

                // todo
                $this->vm->getInstanceByName($instanceName);
        /*
                $this->assertEquals('success', $responseInstanceByName['status']);
                if (isset($responseInstanceByName['data'])) {
                    $this->assertContains('Name', $responseInstanceByName['data']);
                    $this->assertContains('Status', $responseInstanceByName['data']);
                    $this->assertContains('Started', $responseInstanceByName['data']);
                    $this->assertContains('Finished', $responseInstanceByName['data']);
                    $this->assertContains('Seconds', $responseInstanceByName['data']);
                    $this->assertContains('Hostname', $responseInstanceByName['data']);
                    $this->assertContains('Secret', $responseInstanceByName['data']);
                    $this->assertContains('Region', $responseInstanceByName['data']);
                    $this->assertContains('MachineSize', $responseInstanceByName['data']);
                    $this->assertContains('Turn', $responseInstanceByName['data']);
                }
         */

        // todo
        //$this->vm->deleteInstanceByName();
        /*
         *         $response = $this->vm->deleteInstanceByName($this->deleteInstanceName);
                $this->assertEquals('success', $response['status']);
         */

        // todo
        //$this->vm->startInstanceByName();
        /*
        $response = $this->vm->startInstanceByName($this->startInstanceName);
        $this->assertEquals('success', $response['status']);
        */

        // todo
        //$this->vm->stopInstanceByName();
        /*
        $response = $this->vm->stopInstanceByName($this->stopInstanceName);
        $this->assertEquals('success', $response['status']);
        */
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
        $this->assertSame(Vm::SUCCESS_RESPONSE, $result['status']);
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
        $this->assertSame(Vm::FAIL_RESPONSE, $result['status']);
        return $result;
    }
}
