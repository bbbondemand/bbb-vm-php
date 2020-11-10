<?php declare(strict_types=1);
namespace BBBondemand;

use BBBondemand\Enums\InstancesApiRoute;
use BBBondemand\Enums\RegionsApiRoute;
use BBBondemand\Util\UrlBuilder;
use PHPUnit\Framework\TestCase;

class VmTest extends TestCase
{
    private $vm;
    /*
    private $startInstanceName;
    private $stopInstanceName;
    private $deleteInstanceName;
    private $instanceName;
    private $urlBuilder;
    */

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
        $this->vm = new Vm($conf['customerApiToken'], new UrlBuilder($customerId, $baseApiUrl));
    }

    public function testExec_ReturnsErrorForInvalidUrl()
    {
        $baseApiUrl = Sut::vmConf('baseApiUrl');
        $result = $this->vm->exec('GET', $baseApiUrl . '/non-existing/url');
        $this->checkFailResult($result, 403);
        $this->assertSame('[ERR:4] Forbidden', $result['data']);
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
    }

    public function testGetRecordings()
    {
        $result = $this->vm->getRecordings();
        $this->checkSuccessResult($result);
        $this->assertIsArray($result['data']);
        $this->markTestIncomplete();
    }

    public function testGetRecordingById_InvalidIdFormat()
    {
        $result = $this->vm->getRecordingById('someIdOfMissingRecording');
        $this->checkFailResult($result, 400);
        $this->assertSame("invalid recording ID: must be in lower case", $result['data']);
    }

    public function testGetRecordingById_InvalidIdLength()
    {
        $result = $this->vm->getRecordingById('someidofmissingrecording');
        $this->checkFailResult($result, 400);
        $this->assertSame("invalid recording ID: the length must be exactly 54", $result['data']);
    }

    public function testGetRecordingById_IdOfNonExistingRecording()
    {
        $result = $this->vm->getRecordingById("testtesttesttesttesttesttesttesttesttesttesttesttestte");
        $this->checkFailResult($result, 400);
        $this->assertStringContainsString('unable to find recording', $result['data']);
    }

    public function testGetMeetings()
    {
        $result = $this->vm->getMeetings();
        $this->checkSuccessResult($result);
        $this->markTestIncomplete();
    }
/*
    public function testCreateInstance(): void
    {
        $response = $this->vm->createInstance();
        $this->assertEquals('success', $response['status']);
    }

    public function testStartInstanceByName(): void
    {
        $response = $this->vm->startInstanceByName($this->startInstanceName);
        $this->assertEquals('success', $response['status']);
    }

    public function testStopInstanceByName(): void
    {
        $response = $this->vm->stopInstanceByName($this->stopInstanceName);
        $this->assertEquals('success', $response['status']);
    }

    public function testDeleteInstanceByName(): void
    {
        $response = $this->vm->deleteInstanceByName($this->deleteInstanceName);
        $this->assertEquals('success', $response['status']);
    }

    public function testMatchInstancesListArrayStructure(): void
    {
        $responseInstancesList = $this->vm->getInstances();

        if (isset($responseInstancesList['data'][0])) {
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
        }

        $this->assertEquals('success', $responseInstancesList['status']);
    }

    public function testMatchInstanceByNameArrayStructure(): void
    {
        $responseInstanceByName = $this->vm->getInstanceByName($this->instanceName);
        // print_r($responseInstanceByName);

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

        $this->assertEquals('success', $responseInstanceByName['status']);
    }

    public function testMatchRegionsArrayStructure(): void
    {
        $responseRegions = $this->vm->getRegions();

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

        $this->assertEquals('success', $responseRegions['status']);
    }

    public function testMatchMeetingsArrayStructure(): void
    {
        $responseMeetings = $this->vm->getMeetings();

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

        $this->assertEquals('success', $responseMeetings['status']);
    }

    public function testExecGetApiCall(): void
    {
        $response = $this->vm->execApiCall($this->urlBuilder->buildUrl(RegionsApiRoute::LIST));
        $this->assertEquals('success', $response['status']);
    }

    public function testExecDeleteApiCall(): void
    {
        $param['name'] = $this->deleteInstanceName;
        $response = $this->vm->execApiCall($this->urlBuilder->buildUrl(InstancesApiRoute::DELETE, $param), 'DELETE');
        $this->assertEquals('success', $response['status']);
    }
*/
    /**
     * Makes common checks for the successful result
     * @param array $result
     * @return array
     */
    private function checkSuccessResult(array $result)
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
    private function checkFailResult(array $result, int $expectedStatusCode)
    {
        $this->assertSame($expectedStatusCode, $this->vm->getResponse()->getStatusCode());
        $this->assertCount(2, $result);
        $this->assertSame(Vm::FAIL_RESPONSE, $result['status']);
        return $result;
    }
}
