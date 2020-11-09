<?php
use BBBondemand\Enums\InstancesApiRoute;
use BBBondemand\Enums\RegionsApiRoute;
use PHPUnit\Framework\TestCase;

/**
 *  Corresponding Class to test YourClass class
 *
 *  For each class in your library, there should be a corresponding Unit-Test for it
 *  Unit-Tests should be as much as possible independent from other test going on.
 *
 * @author yourname
 */
class BBBClassTests extends TestCase
{

    private $vm;
    private $baseUrl;
    private $customer_id;
    private $customer_api_token;
    private $start_instance_name;
    private $stop_instance_name;
    private $delete_instance_name;
    private $instance_name;
    private $urlBuilder;

    /**
     * Setup test class
     */
    public function setUp()
    {
        parent::setUp();

        foreach ([
                     'VM_CUSTOMER_ID',
                     'VM_CUSTOMER_API_TOKEN',
                     'VM_API_URL',
                     'VM_START_INSTANCE_NAME',
                     'VM_STOP_INSTANCE_NAME',
                     'VM_DELETE_INSTANCE_NAME',
                     'VM_INSTANCE_NAME'
                 ] as $k) {
            if (!getenv($k)) {
                $this->fail('$_SERVER[\'' . $k . '\'] not set in '
                    . 'phpunit.xml');
            }
        }
        $this->baseUrl              = getenv('VM_API_URL');
        $this->customer_id          = getenv('VM_CUSTOMER_ID');
        $this->customer_api_token   = getenv('VM_CUSTOMER_API_TOKEN');
        $this->start_instance_name  = getenv('VM_START_INSTANCE_NAME');
        $this->stop_instance_name   = getenv('VM_STOP_INSTANCE_NAME');
        $this->delete_instance_name = getenv('VM_DELETE_INSTANCE_NAME');
        $this->instance_name        = getenv('VM_INSTANCE_NAME');

        $this->urlBuilder = new BBBondemand\Util\UrlBuilder($this->customer_id, $this->baseUrl);
        $this->vm         = new BBBondemand\VM($this->customer_id, $this->customer_api_token);
    }

    /**
     * Just check if the YourClass has no syntax error
     *
     * This is just a simple check to make sure your library has no syntax error. This helps you troubleshoot
     * any typo before you even use this library in a real project.
     *
     */
    public function testIsThereAnySyntaxError(): void
    {
        $var = new BBBondemand\VM;
        $this->assertTrue(is_object($var));
        unset($var);
    }

    /**
     * Test url build
     */
    public function testUrlBuild(): void
    {
        $url = $this->urlBuilder->buildUrl(RegionsApiRoute::LIST);
        $this->assertContains($this->customer_id, $url);
        $this->assertContains($this->baseUrl, $url);
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
        $response = $this->vm->startInstanceByName($this->start_instance_name);
        $this->assertEquals('success', $response['status']);
        $this->assertTrue(true);
    }

    /**
     * Test start instance by name
     */
    public function testStopInstanceByName(): void
    {
        $response = $this->vm->stopInstanceByName($this->stop_instance_name);
        $this->assertEquals('success', $response['status']);
        $this->assertTrue(true);
    }

    /**
     * Test start instance by name
     */
    public function testDeleteInstanceByName(): void
    {
        $response = $this->vm->deleteInstanceByName($this->delete_instance_name);
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
        $responseInstanceByName = $this->vm->getInstanceByName($this->instance_name);
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
        $param['name'] = $this->delete_instance_name;
        $response      = $this->vm->executeApiCall($this->urlBuilder->buildUrl(InstancesApiRoute::DELETE, $param), 'DELETE');
        $this->assertEquals('success', $response['status']);
    }
}
