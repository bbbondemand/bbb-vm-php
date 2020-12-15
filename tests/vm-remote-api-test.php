<?php declare(strict_types=1);
namespace BBBondemand\Test;
// These are integration tests for the remote VM REST API.

use BBBondemand\InstanceStatus;
use BBBondemand\MachineSize;
use BBBondemand\RecordingState;
use BBBondemand\Vm;
use Closure;
use PHPUnit\Framework\IncompleteTestError;
use PHPUnit\TextUI\XmlConfiguration\Loader;
use RuntimeException;
use UnexpectedValueException;
use function ini_set;

require __DIR__ . '/bootstrap.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');
if (ini_get('zend.assertions') !== '1') {
    throw new UnexpectedValueException("The zend.assertions must be set to '1' for testing");
}
ini_set('assert.active', '1');
ini_set('assert.exception', '1');

function checkSuccessResult(Vm $vm, array $result, bool $dataIsNull = false): array {
    assert(count($result) === 2, print_r($result, true));
    assert(200 === $vm->getLastResponse()->getStatusCode());
    assert(Vm::SUCCESS_STATUS === $result['status']);
    if ($dataIsNull) {
        assert($result['data'] === null, print_r($result['data'], true));
    } else {
        assert(is_array($result['data']) && count($result['data']) > 0, print_r($result['data'], true));
    }
    return $result;
}

function checkErrorResult(Vm $vm, array $result, string $expectedStatusCode, string $expectedMessage): array {
    assert(count($result) === 3);
    assert(null === $result['data']);
    assert(Vm::ERR_STATUS === $result['status']);
    assert($expectedMessage === $result['message']);
    return $result;
}

function checkArrHasNotEmptyItems(array $expectedKeys, array $arr) {
    foreach ($expectedKeys as $expectedKey) {
        assert(isset($arr[$expectedKey]), 'Checking the key: ' . $expectedKey);
    }
}

/**
 * @param string $test
 * @param Closure $fn
 * @param mixed $args
 * @param int $indent
 * @return mixed
 */
function test(string $test, Closure $fn, $args = null, int $indent = 0) {
    $test = preg_replace("~\s+~si", ' ', $test);
    writeLnIndent("Running: " . $test . '...', $indent);
    try {
        $res = $fn($args);
        writeLnIndent("Done: $test\n", $indent);
        return $res;
    } catch (IncompleteTestError $e) {
        if (PHP_SAPI === 'cli') {
            $redColor = "\033[0;31m";
            $noColor = "\033[0m";
        } else {
            $redColor = $noColor = '';
        }
        writeLnIndent("{$redColor}Skipped incomplete test: $test$noColor\n", $indent);
    }
    return null;
}

function deleteAllInstances(Vm $vm): int {
    $numOfDeletedInstances = 0;
    foreach ($vm->getInstances()['data'] as $instanceData) {
        if ($instanceData['Status'] !== InstanceStatus::DELETED) {
            checkSuccessResult($vm, $vm->deleteInstance($instanceData['ID']), true);
            $numOfDeletedInstances++;
        }
    }
    foreach ($vm->getInstances()['data'] as $instanceData) {
        if ($instanceData['Status'] !== InstanceStatus::DELETED) {
            throw new UnexpectedValueException(print_r($instanceData, true));
        }
    }
    return $numOfDeletedInstances;
}

function mkVm(): Vm {
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

function writeLnIndent(string $text, int $indent): void {
    echo str_repeat('    ', $indent) . $text . "\n";
}

function waitInstanceStatus(Vm $vm, string $instanceId, string $expectedStatus, int $indent = 0): void {
    $sleepSecs = 10;
    $waitMins = 5;
    for ($i = 0; $i < 60 * $waitMins / $sleepSecs; $i++) {
        $curStatus = $vm->getInstance($instanceId)['data']['Status'];
        if ($curStatus === $expectedStatus) {
            writeLnIndent("The instance '$instanceId' now has the expected '$curStatus' status", $indent);
            return;
        }
        writeLnIndent("Waiting until instance '$instanceId' will have the '$expectedStatus' status, current status: '$curStatus', waiting $sleepSecs seconds...", $indent);
        sleep($sleepSecs);
    }
    throw new RuntimeException("Instance '$instanceId' can't get the expected status '$expectedStatus', waited: " . ($i * $sleepSecs) . ' seconds');
}

function checkInstanceStatus(Vm $vm, string $instanceId, string $expectedStatus): void {
    assert($vm->getInstance($instanceId)['data']['Status'] === $expectedStatus, "Precondition: instance '$instanceId' has '$expectedStatus' Status");
}

function checkCreateInstanceResult(Vm $vm, array $creationResult): array {
    checkSuccessResult($vm, $creationResult);
    $creationData = $creationResult['data'];
    assert(4, $creationData);
    assert(strlen($creationData['ID']) > 0);
    assert(strlen($creationData['Secret']) > 0);
    assert(strlen($creationData['Host']) > 0);
    assert(strlen($creationData['TestUrl']) > 0);
    return $creationData;
}

function checkInstanceData(array $instanceData): void {
    assert(count($instanceData) >= 8, "Instance details size");
    assert((bool)preg_match('~^[a-z0-9]{21}$~', $instanceData['ID']));
    assert(in_array($instanceData['Status'], InstanceStatus::all(), true));
    assert($instanceData['Started'] > (time() - (365 * 2 * 24 * 60 * 60)));
    assert(strlen($instanceData['Hostname']) > 0);
    assert(strlen($instanceData['Secret']) > 0);
    assert(strlen($instanceData['Region']) > 0);
    assert(isset($instanceData['Tags']));
    assert(in_array($instanceData['MachineSize'], MachineSize::all(), true));
    assert(is_bool($instanceData['ManagedRecordings']));
}

function checkMeetingData(array $meetingData): void {
    $meetingKeys = [
        'ReturnCode',
        'MeetingName',
        'MeetingID',
        'InternalMeetingID',
        'CreateTime',
        'CreateDate',
        'VoiceBridge',
        'DialNumber',
        'AttendeePW',
        'ModeratorPW',
        'Duration',
        'HasUserJoined',
        //'Recording': can be missing
        'StartTime',
        'EndTime',
        'ParticipantCount',
        //'ListenerCount': can be missing
        //'VideoCount': can be missing
        //'MaxUsers': can be missing
        'ModeratorCount',
        'Attendees',
    ];
    $attendeeKeys = [
        "UserID",
        "FullName",
        "Role",
        "IsPresenter",
        //"HasVideo": can be missing
        "ClientType",
    ];
    checkArrHasNotEmptyItems($meetingKeys, $meetingData);
    foreach ($meetingData['Attendees'] as $key => $attendeeData) {
        // todo: weird result, why to use 3-dim array?
        if ($key === 'Attendee') {
            foreach ($attendeeData as $attendee1) {
                checkArrHasNotEmptyItems($attendeeKeys, $attendee1);
            }
        } else {
            throw new UnexpectedValueException("Unknown key: " . $key);
        }
    }
}

function checkRecordingData(array $recordingData) {
    $recordingKeys = [
        "RecordID",
        "MeetingID",
        "Name",
        //"Published": can be missing
        "State",
        "StartTime",
        "EndTime",
        "Participants",
        "MetaData",
        "Playback",
    ];
    checkArrHasNotEmptyItems($recordingKeys, $recordingData);
    foreach ($recordingData['Playback'] as $key => $playbackData) {
        // todo: weird result, why to use 3-dim array?
        if ($key === 'Format') {
            // todo: Add $checkPlaybackFormat() to check $playbackFormat
        } else {
            throw new UnexpectedValueException();
        }
    }
}

function markTestIncomplete(string $test, int $lineNum) {
    throw new IncompleteTestError($test . ', line: ' . $lineNum);
}

function main(): void {
    $vm = mkVm();

    register_shutdown_function(function () use ($vm) {
        // todo: remove comment
        //writeLnIndent("Deleted leftover instances: " . deleteAllInstances($vm), 0);
    });

    writeLnIndent("Tests started at " . date('Y-m-d H:i:s', 'UTC') . ' (UTC)', 0);

    writeLnIndent("Total instances: " . array_reduce($vm->getInstances()['data'], function ($acc) {
            $acc += 1;
            return $acc;
        }, 0), 0);
    writeLnIndent("Deleted leftover instances: " . deleteAllInstances($vm), 0);

    // Billing
    test("Vm::getBillingSummary()", function () use ($vm) {
        $result = checkSuccessResult($vm, $vm->getBillingSummary());
        foreach ($result['data'] as $billingActivityData) {
            checkArrHasNotEmptyItems(['ID', 'CustomerID', 'Year', 'Week', 'Updated'], $billingActivityData);
        }
    });

    // Instances
    test("Vm::getInstances(),
        Vm::createInstance(),
        Vm::getInstance(),
        Vm::stopInstance(),
        Vm::deleteInstance(),
        Vm::startInstance(),
        Vm::getInstanceHistory()", function () use ($vm) {
        $indent = 1;

        test("Vm::getInstances()", function () use ($vm) {
            $result = checkSuccessResult($vm, $vm->getInstances());
            foreach ($result['data'] as $instanceData) {
                checkInstanceData($instanceData);
            }
        }, null, $indent);

        test("Vm::createInstance() with ManagedRecordings and with Tags,
            getInstance(),
            Vm::startInstance(): start " . InstanceStatus::AVAILABLE . " instance,
            Vm::stopInstance(): stop " . InstanceStatus::AVAILABLE . " instance,
            Vm::stopInstance(): stop " . InstanceStatus::STOPPED . " instance,
            Vm::startInstance(): start " . InstanceStatus::STOPPED . " instance,
            Vm::getInstanceHistory(),
            Vm::deleteInstance(): delete " . InstanceStatus::STOPPED . " instance", function () use ($indent, $vm) {
            $createInstanceResult = $vm->createInstance([
                'ManageRecordings' => true,
                'Tags' => 'foo,bar',
            ]);
            checkCreateInstanceResult($vm, $createInstanceResult);
            $instanceId = $createInstanceResult['data']['ID'];
            waitInstanceStatus($vm, $instanceId, InstanceStatus::AVAILABLE, $indent + 1);

            test("Vm::getInstance()", function () use ($instanceId, $vm) {
                $result = checkSuccessResult($vm, $vm->getInstance($instanceId));
                checkInstanceData($result['data']);
            });

            test("Vm::startInstance(): start " . InstanceStatus::AVAILABLE . " instance", function () use ($indent, $vm, $instanceId) {
                markTestIncomplete("Vm::startInstance(): start " . InstanceStatus::AVAILABLE . " instance", __LINE__);
                waitInstanceStatus($vm, $instanceId, InstanceStatus::AVAILABLE, $indent + 1);
                $result = $vm->startInstance($instanceId);

                d($result);

                // todo: fix message "unable to start the stopped instance"
                // @todo: fix parameters:
                checkErrorResult($vm, $result);


                checkInstanceStatus($vm, $instanceId, InstanceStatus::AVAILABLE); // should be not changed
            }, null, $indent);

            test("Vm::stopInstance(): stop " . InstanceStatus::AVAILABLE . " instance", function () use ($instanceId, $indent, $vm) {
                checkInstanceStatus($vm, $instanceId, InstanceStatus::AVAILABLE);
                checkSuccessResult($vm, $vm->stopInstance($instanceId), true);
                waitInstanceStatus($vm, $instanceId, InstanceStatus::STOPPED, $indent + 1);
            }, null, $indent);

            test("Vm::stopInstance(): stop " . InstanceStatus::STOPPED . " instance", function () use ($vm, $instanceId) {
                checkInstanceStatus($vm, $instanceId, InstanceStatus::STOPPED);
                $result = $vm->stopInstance($instanceId);

                d($result);
                // @todo: fix parameters:
                checkErrorResult($vm, $result);
                assert('this instance was found to be already stopped' === $result['data']);
                checkInstanceStatus($vm, $instanceId, InstanceStatus::STOPPED); // should be not changed
            }, null, $indent);

            test("Vm::startInstance(): start " . InstanceStatus::STOPPED . " instance", function () use ($indent, $vm, $instanceId) {
                checkInstanceStatus($vm, $instanceId, InstanceStatus::STOPPED);
                checkSuccessResult($vm, $vm->startInstance($instanceId));
                waitInstanceStatus($vm, $instanceId, InstanceStatus::AVAILABLE, $indent + 1);
            }, null, $indent);

            test("Vm::getInstanceHistory()", function () use ($vm, $instanceId) {
                $result = checkSuccessResult($vm, $vm->getInstanceHistory($instanceId));
                d($result);
            });

            test("Vm::deleteInstance(): delete " . InstanceStatus::STOPPED . " instance", function () use ($instanceId, $indent, $vm) {
                checkInstanceStatus($vm, $instanceId, InstanceStatus::STOPPED);
                checkSuccessResult($vm, $vm->deleteInstance($instanceId), true);
                waitInstanceStatus($vm, $instanceId, InstanceStatus::DELETED, $indent + 1);
            }, null, $indent);
        }, null, $indent);

        test("Vm::createInstance() without ManagedRecordings and without Tags,
            Vm::getInstance()", function () use ($indent, $vm) {
            $result = $vm->createInstance();
            checkCreateInstanceResult($vm, $result);
            $instanceId = $result['data']['ID'];
            waitInstanceStatus($vm, $instanceId, InstanceStatus::AVAILABLE, $indent + 1);

            test("Vm::getInstance()", function () use ($instanceId, $vm) {
                $result = checkSuccessResult($vm, $vm->getInstance($instanceId));
                checkInstanceData($result['data']);
            });
        }, null, $indent);

        test("Vm::deleteInstance(): delete " . InstanceStatus::AVAILABLE . " instance", function () use ($indent, $vm) {
            $instanceId = $vm->createInstance()['data']['ID'];
            waitInstanceStatus($vm, $instanceId, InstanceStatus::AVAILABLE, $indent + 1);
            checkSuccessResult($vm, $vm->deleteInstance($instanceId), true);
            waitInstanceStatus($vm, $instanceId, InstanceStatus::DELETED, $indent + 1);
        }, null, $indent);
    });

    // Meetings
    test("Vm::getMeetings(), Vm::getMeeting()", function () use ($vm) {
        // GET /meetings
        $result = checkSuccessResult($vm, $vm->getMeetings());

        // There should be always not empty list of meetings for testing:
        $meeting = null;
        foreach ($result['data'] as $meetingData) {
            checkMeetingData($meetingData);
            $meeting = $meetingData;
        }
        assert(is_array($meeting));

        // GET /meetings/{meetingID}
        $result = checkSuccessResult($vm, $vm->getMeeting($meeting['MeetingID']));
        checkMeetingData($result['data']);
    });

    // Recordings
    test("Vm::getRecordings(),
        Vm::getRecording(),
        Vm::unpublishRecording(),
        Vm::deleteRecording(),
        Vm::publishRecording()", function () use ($vm) {

        // GET /recordings
        $getRecordingsResult = checkSuccessResult($vm, $vm->getRecordings());

        // There should be always not empty list of recordings for testing:
        $publishedRecording = $unpublishedRecording = null;
        foreach ($getRecordingsResult['data'] as $recordingData) {
            checkRecordingData($recordingData);
            if ($recordingData['State'] === RecordingState::PUBLISHED) {
                $publishedRecording = $recordingData;
            } else {
                $unpublishedRecording = $recordingData;
            }
        }

        // Need to have at least two recordings for testing publishing and unpublishing:
        assert(is_array($publishedRecording) && is_array($unpublishedRecording));

        // It does not matter which recording to use for testing getRecording() - it can either published or unpublished one and we use the published recording.
        $result = checkSuccessResult($vm, $vm->getRecording($publishedRecording['RecordID']));
        checkRecordingData($result['data']);

        // @todo: Compare $getRecordingResult with the new result.

        checkSuccessResult($vm, $vm->publishRecording($unpublishedRecording['RecordID']));

        checkSuccessResult($vm, $vm->unpublishRecording($publishedRecording['RecordID']));

        foreach ([$unpublishedRecording, $publishedRecording] as $recording) {
            checkSuccessResult($vm, $vm->deleteRecording($recording['RecordID']));
        }
    });

    // Regions
    test("Vm::getRegions()", function () use ($vm) {
        $result = checkSuccessResult($vm, $vm->getRegions());
        foreach ($result['data'] as $key => $regionData) {
            assert((bool) preg_match('~^[-0-9a-z]+$~si', $key));
            checkArrHasNotEmptyItems(['Name', 'Town', 'Continent'], $regionData);
        }
    });
}

main();