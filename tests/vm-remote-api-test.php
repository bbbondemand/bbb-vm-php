<?php declare(strict_types=1);
namespace BBBondemand;
// These are integration tests of the remote VM REST API.

use Closure;
use PHPUnit\TextUI\XmlConfiguration\Loader;
use RuntimeException;
use UnexpectedValueException;
use function ini_set;

require __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');
if (ini_get('zend.assertions') !== '1') {
    throw new UnexpectedValueException("The zend.assertions must be set to '1' for testing");
}
ini_set('assert.active', '1');
ini_set('assert.exception', '1');

function d(...$vars): void
{
    var_dump(...$vars);
    exit;
}

function checkSuccessResult(array $result): array
{
    assert(Vm::SUCCESS_STATUS === $result['status']);
    assert(count($result) === 2);
    return $result;
}

function checkFailResult(array $result): array
{
    assert(Vm::FAIL_STATUS === $result['status']);
    assert(count($result) === 2);
    return $result;
}

function checkInstanceDetails(array $instanceDetails): void
{
    assert(count($instanceDetails) >= 8, "Instance details size");
    assert((bool)preg_match('~^[a-z0-9]{21}$~', $instanceDetails['Name']));
    assert(in_array($instanceDetails['Status'], ['STARTING', 'AVAILABLE', 'STOPPING', 'STOPPED', 'DELETED'], true));
    assert(strlen($instanceDetails['Hostname']) > 0);
    assert(in_array(strtoupper($instanceDetails['MachineSize']), ['SMALL', 'STANDARD', 'LARGE', 'XLARGE'])); // todo fix case, remove strtoupper()
    assert(strlen($instanceDetails['Region']) > 0);
    assert($instanceDetails['Started'] > (time() - (365 * 2 * 24 * 60 * 60)));
    if ($instanceDetails['Status'] === 'DELETED') {
        assert($instanceDetails['Finished'] > 0);
        assert($instanceDetails['Seconds'] > 0);
    }
    assert(strlen($instanceDetails['Secret']) > 0);
    assert(count($instanceDetails['Turn']) > 0);
}

/**
 * @param string $test
 * @param Closure $fn
 * @param mixed $args
 * @param int $indent
 * @return mixed
 */
function test(string $test, Closure $fn, $args = null, int $indent = 0)
{
    writeLnIndent("Testing $test...", $indent);
    $res = $fn($args);
    writeLnIndent("OK", $indent);
    return $res;
}

function deleteAllInstances(Vm $vm): int
{
    $i = 0;
    foreach ($vm->getInstances()['data'] as $instanceDetails) {
        if ($instanceDetails['Status'] !== 'DELETED') {
            $result = $vm->deleteInstanceByName($instanceDetails['Name']);
            checkSuccessResult($result);
            $i++;
        }
    }
    foreach ($vm->getInstances()['data'] as $instanceDetails) {
        if ($instanceDetails['Status'] !== 'DELETED') {
            throw new UnexpectedValueException(print_r($instanceDetails, true));
        }
    }
    return $i;
}

function mkVm(): Vm
{
    $envVars = [];
    // See vendor/phpunit/phpunit/src/TextUI/XmlConfiguration/PHP/PhpHandler.php
    foreach ((new Loader)->load(__DIR__ . '/../phpunit.xml')->php()->envVariables() as $envVar) {
        $envVars[$envVar->name()] = $envVar->value();
    }
    return Vm::mk([
        'customerId' => $envVars['VM_CUSTOMER_ID'],
        'customerApiToken' => $envVars['VM_CUSTOMER_API_TOKEN'],
        'baseApiUrl' => $envVars['VM_BASE_API_URL'],
    ]);
}

function writeLnIndent(string $text, int $indent): void
{
    echo str_repeat('    ', $indent) . $text . "\n";
}

function waitInstanceStatus(Vm $vm, string $instanceName, string $expectedStatus, int $indent = 0): void
{
    $sleepSecs = 10;
    for ($i = 0; $i < 60 * 5; $i++) {
        $curStatus = $vm->getInstanceByName($instanceName)['data']['Status'];
        if ($curStatus === $expectedStatus) {
            writeLnIndent("The instance '$instanceName' now has the expected '$curStatus' status", $indent);
            return;
        }
        writeLnIndent("Waiting until instance '$instanceName' will have the '$expectedStatus' status, current status: '$curStatus', waiting $sleepSecs seconds...", $indent);
        sleep($sleepSecs);
    }
    throw new RuntimeException("Instance '$instanceName' can't get the expected status '$expectedStatus', waited: " . ($i * $sleepSecs) . ' seconds');
}

function checkInstanceStatus(Vm $vm, string $instanceName, string $expectedStatus): void
{
    assert($vm->getInstanceByName($instanceName)['data']['Status'] === $expectedStatus, "Precondition: instance '$instanceName' has '$expectedStatus' Status");
}

function checkCreationResult(array $creationResult): array
{
    checkSuccessResult($creationResult);
    $creationData = $creationResult['data'];
    assert(4, $creationData);
    assert(strlen($creationData['Name']) > 0);
    assert(strlen($creationData['Secret']) > 0);
    assert(strlen($creationData['Host']) > 0);
    assert(strlen($creationData['TestURL']) > 0);
    return $creationData;
}

const AVAILABLE_STATUS = 'AVAILABLE';
const STOPPED_STATUS = 'STOPPED';
const DELETED_STATUS = 'DELETED';

function main(): void
{
    $vm = mkVm();

    register_shutdown_function(function () use ($vm) {
        writeLnIndent("Deleted leftover instances: " . deleteAllInstances($vm), 0);
    });

    writeLnIndent("Total instances: " . array_reduce($vm->getInstances()['data'], function ($acc) {
            $acc += 1;
            return $acc;
        }, 0), 0);
    writeLnIndent("Deleted leftover instances: " . deleteAllInstances($vm), 0);

    test("Vm::getMeetings(), Vm::getRecordings(), Vm::getRecordingById(), Vm::createInstance() with ManagedRecordings and Tags", function () use ($vm) {
        // todo: always empty result
        //d($vm->getMeetings(), $vm->getRecordings());

        // On Demand cloud meetings
        $creationResult = $vm->createInstance([
            'ManageRecordings' => true,
            'Tags' => 'foo,bar',
            /*
                Host - string - fully qualified domain of the instance
                Name - string - unique name for the instance, used by other API methods
                Secret - string - unique random secret to interact with the running BBB instance
                TestUrl - string - a link to an API testing website which makes it easy for developers to check and test the BBB instance. Ignore if not useful to you.

             */
        ]);
        checkCreationResult($creationResult);

        waitInstanceStatus($vm, $creationResult['data']['Name'], AVAILABLE_STATUS);
    });

    test("Vm::getRegions()", function () use ($vm) {
        $result = $vm->getRegions();
        checkSuccessResult($result);
        assert([
                'Name' => 'europe-west3',
                'Town' => 'Germany, Frankfurt',
                'Continent' => 'Europe',
            ] == $result['data']['europe-west3']);
        /* todo
        $this->assertContains('Name', $firstItem);
        $this->assertContains('Town', $firstItem);
        $this->assertContains('Continent', $firstItem);
        $this->assertContains('Zones', $firstItem);
        $this->assertContains('Capability', $firstItem);
        $this->assertContains('Active', $firstItem);
        $this->assertContains('Proximate', $firstItem);
        */
    });

    test("Vm::getInstances(), Vm::createInstance() without ManagedRecordings and without Tags, Vm::getInstanceByName(), Vm::deleteInstanceByName(), Vm::startInstanceByName(), Vm::stopInstanceByName()", function () use ($vm) {
        $indent = 1;

        test("Vm::deleteInstanceByName(): delete " . STOPPED_STATUS . " instance", function () use ($indent, $vm) {
            $instanceName = $vm->createInstance()['data']['Name'];
            waitInstanceStatus($vm, $instanceName, AVAILABLE_STATUS, $indent + 1);
            $vm->stopInstanceByName($instanceName);
            waitInstanceStatus($vm, $instanceName, STOPPED_STATUS, $indent + 1);
            $result = $vm->deleteInstanceByName($instanceName);
            checkSuccessResult($result);
            assert(null === $result['data']);
            waitInstanceStatus($vm, $instanceName, DELETED_STATUS, $indent + 1);
        }, null, $indent);

        $instanceName = test("Vm::createInstance() without ManagedRecordings and without Tags", function () use ($indent, $vm) {
            $result = $vm->createInstance();
            checkCreationResult($result);
            $instanceName = $result['data']['Name'];
            waitInstanceStatus($vm, $instanceName, AVAILABLE_STATUS, $indent + 1);
            return $instanceName;
        }, null, $indent);

        test("Vm::getInstanceByName()", function () use ($vm, $instanceName) {
            checkInstanceStatus($vm, $instanceName, AVAILABLE_STATUS);
            $result = $vm->getInstanceByName($instanceName);
            checkSuccessResult($result);
            checkInstanceDetails($result['data']);
        }, null, $indent);

        test("Vm::getInstances()", function () use ($vm) {
            $result = $vm->getInstances();
            checkSuccessResult($result);
            assert(count($result['data']) > 0);
            foreach ($result['data'] as $instanceDetails) {
                checkInstanceDetails($instanceDetails);
            }
        }, null, $indent);

        test("Vm::stopInstanceByName(): stop " . AVAILABLE_STATUS . " instance", function () use ($indent, $vm, $instanceName) {
            checkInstanceStatus($vm, $instanceName, AVAILABLE_STATUS);
            $result = $vm->stopInstanceByName($instanceName);
            checkSuccessResult($result);
            assert(null === $result['data']);
            waitInstanceStatus($vm, $instanceName, STOPPED_STATUS, $indent + 1);
        }, null, $indent);

        test("Vm::stopInstanceByName(): stop " . STOPPED_STATUS . " instance", function () use ($vm, $instanceName) {
            checkInstanceStatus($vm, $instanceName, STOPPED_STATUS);
            $result = $vm->stopInstanceByName($instanceName);
            checkFailResult($result);
            assert('this instance was found to be already stopped' === $result['data']);
            checkInstanceStatus($vm, $instanceName, STOPPED_STATUS); // should be not changed
        }, null, $indent);

        test("Vm::startInstanceByName(): start " . STOPPED_STATUS . " instance", function () use ($indent, $vm, $instanceName) {
            checkInstanceStatus($vm, $instanceName, STOPPED_STATUS);
            $result = $vm->startInstanceByName($instanceName);
            checkSuccessResult($result);
            assert(null === $result['data']);
            waitInstanceStatus($vm, $instanceName, AVAILABLE_STATUS, $indent + 1);
        }, null, $indent);

        test("Vm::startInstanceByName(): start " . AVAILABLE_STATUS . " instance", function () use ($indent, $vm, $instanceName) {
            waitInstanceStatus($vm, $instanceName, AVAILABLE_STATUS, $indent + 1);
            $result = $vm->startInstanceByName($instanceName);
            // todo: fix message "unable to start the stopped instance"
            checkFailResult($result);
            checkInstanceStatus($vm, $instanceName, AVAILABLE_STATUS); // should be not changed
        }, null, $indent);

        test("Vm::deleteInstanceByName(): delete " . AVAILABLE_STATUS . " instance", function () use ($indent, $instanceName, $vm) {
            checkInstanceStatus($vm, $instanceName, AVAILABLE_STATUS);
            $result = $vm->deleteInstanceByName($instanceName);
            checkSuccessResult($result);
            assert(null === $result['data']);
            waitInstanceStatus($vm, $instanceName, DELETED_STATUS, $indent + 1);
        }, null, $indent);
    });
}

main();