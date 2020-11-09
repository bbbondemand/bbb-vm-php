<?php declare(strict_types=1);

namespace BBBondemand;

use BBBondemand\Enums\InstancesApiRoute;
use BBBondemand\Enums\RegionsApiRoute;
use BBBondemand\Util\UrlBuilder;
use PHPUnit\Framework\TestCase;

/**
 *  Corresponding Class to test YourClass class
 *
 *  For each class in your library, there should be a corresponding Unit-Test for it
 *  Unit-Tests should be as much as possible independent from other test going on.
 *
 * @author yourname
 */
class VmTest extends TestCase
{
    private $vm;
    private $startInstanceName;
    private $stopInstanceName;
    private $deleteInstanceName;
    private $instanceName;
    private $urlBuilder;

    /**
     * Setup test class
     */
    public function setUp(): void
    {
        parent::setUp();
        $conf = Sut::vmConf();
        $baseUrl = $conf['apiUrl'];
        $customerId = $conf['customerId'];
        $this->startInstanceName = $conf['startInstanceName'];
        $this->stopInstanceName = $conf['stopInstanceName'];
        $this->deleteInstanceName = $conf['deleteInstanceName'];
        $this->instanceName = $conf['instanceName'];
        $this->urlBuilder = new UrlBuilder($customerId, $baseUrl);
        $this->vm = new Vm($conf['customerApiToken'], $this->urlBuilder);
    }

    public function testGetRegions()
    {
        $regions = $this->vm->getRegions();
        $this->markTestIncomplete();
    }

    /**
     * Test create instance
     */
    public function testCreateInstance(): void
    {
        $response = $this->vm->createInstance();
        $this->assertEquals('success', $response['status']);
        $this->assertTrue(true);
    }

    /**
     * Test start instance by name
     */
    public function testStartInstanceByName(): void
    {
        $response = $this->vm->startInstanceByName($this->startInstanceName);
        $this->assertEquals('success', $response['status']);
        $this->assertTrue(true);
    }

    /**
     * Test start instance by name
     */
    public function testStopInstanceByName(): void
    {
        $response = $this->vm->stopInstanceByName($this->stopInstanceName);
        $this->assertEquals('success', $response['status']);
        $this->assertTrue(true);
    }

    /**
     * Test start instance by name
     */
    public function testDeleteInstanceByName(): void
    {
        $response = $this->vm->deleteInstanceByName($this->deleteInstanceName);
        $this->assertEquals('success', $response['status']);
        $this->assertTrue(true);
    }

    /**
     * Test execute match instances list response array structure
     */
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

    /**
     * Test execute match instance by name response array structure
     */
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

    /**
     * Test execute match regions response array structure
     */
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

    /**
     * Test execute match meetings response array structure
     */
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

    /**
     * Test Execute Get Api Call
     */
    public function testExecuteGetApiCall(): void
    {
        $response = $this->vm->executeApiCall($this->urlBuilder->buildUrl(RegionsApiRoute::LIST));
        $this->assertEquals('success', $response['status']);
    }

    /**
     * Test Execute Delete Api Call
     */
    public function testExecuteDeleteApiCall(): void
    {
        $param['name'] = $this->deleteInstanceName;
        $response = $this->vm->executeApiCall($this->urlBuilder->buildUrl(InstancesApiRoute::DELETE, $param), 'DELETE');
        $this->assertEquals('success', $response['status']);
    }
}
