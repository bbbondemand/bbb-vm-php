<?php declare(strict_types=1);
namespace BBBondemand\Test;
// These are integration tests for the remote VM REST API.

use BBBondemand\InstanceStatus;
use BBBondemand\MachineSize;
use BBBondemand\RecordingState;
use BBBondemand\Vm;
use Closure;
use DateTimeImmutable;
use DateTimeZone;
use ErrorException;
use PHPUnit\Framework\IncompleteTestError;
use PHPUnit\TextUI\XmlConfiguration\Loader;
use RuntimeException;
use UnexpectedValueException;
use function ini_set;

require __DIR__ . '/bootstrap.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');
set_error_handler(function ($severity, $message, $filePath, $lineNo) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    /** @noinspection PhpUnhandledExceptionInspection */
    throw new ErrorException($message, 0, $severity, $filePath, $lineNo);
});
set_exception_handler(function ($e) {
    d((string) $e);
});
if (ini_get('zend.assertions') !== '1') {
    throw new UnexpectedValueException("The zend.assertions must be set to '1' for testing");
}
ini_set('assert.active', '1');
ini_set('assert.exception', '1');

function checkSuccessResultShapeAndStatusCode(Vm $vm, array $result): array {
    assert(count($result) === 2, print_r($result, true));
    assert(200 === $vm->getLastResponse()->getStatusCode());
    assert(Vm::SUCCESS_STATUS === $result['status']);
    return $result;
}

function checkSuccessResult(Vm $vm, array $result, bool $dataIsNull = false): array {
    checkSuccessResultShapeAndStatusCode($vm, $result);
    if ($dataIsNull) {
        assert($result['data'] === null, print_r($result['data'], true));
    } else {
        assert(is_array($result['data']) && count($result['data']) > 0, print_r($result['data'], true));
    }
    return $result;
}

function checkErrorResult(Vm $vm, array $result, string $expectedMessage, int $expectedHttpStatusCode): array {
    assert(count($result) === 3);
    assert(null === $result['data']);
    assert(Vm::ERR_STATUS === $result['status']);
    assert($expectedMessage === $result['message']);
    assert($expectedHttpStatusCode, $vm->getLastResponse()->getStatusCode());
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
        writeLnIndent("{$redColor}Skipped incomplete test: " . $e->getMessage() . "$noColor\n", $indent);
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

function waitUntil(Closure $predicate, string $errorMessage, int $waitSecs, int $sleepSecs = 10) {
    for ($i = 0; $i < $waitSecs / $sleepSecs; $i++) {
        $res = $predicate($i, $sleepSecs);
        if ($res) {
            return $res;
        }
        sleep($sleepSecs);
    }
    throw new RuntimeException(str_replace('{waitedSecs}', $i * $sleepSecs, $errorMessage));
}

function waitInstanceStatus(Vm $vm, string $instanceId, string $expectedStatus, int $indent = 0): void {
    waitUntil(function (int $i, int $sleepSecs) use ($vm, $instanceId, $expectedStatus, $indent): bool {
        $curStatus = $vm->getInstance($instanceId)['data']['Status'];
        if ($curStatus === $expectedStatus) {
            writeLnIndent("The instance '$instanceId' now has the expected '$curStatus' status", $indent);
            return true;
        }
        writeLnIndent("Waiting until instance '$instanceId' will have the '$expectedStatus' status, current status: '$curStatus', waiting $sleepSecs seconds...", $indent);
        return false;
    }, "Instance '$instanceId' can't get the expected status '$expectedStatus', waited: {waitedSecs} seconds", 60 * 5);
}

function waitRecordingState(Vm $vm, string $recordingId, string $expectedState, int $indent = 0): void {
    waitUntil(function (int $i, int $sleepSecs) use ($vm, $recordingId, $expectedState, $indent): bool {
        $curState = $vm->getRecording($recordingId)['data']['State'];
        if ($curState === $expectedState) {
            writeLnIndent("The recording '$recordingId' now has the expected '$curState' state", $indent);
            return true;
        }
        writeLnIndent("Waiting until recording '$recordingId' will have the '$expectedState' state, current state: '$curState', waiting $sleepSecs seconds...", $indent);
        return false;
    }, "Recording '$recordingId' can't get the expected state '$expectedState', waited: {waitedSecs} seconds", 60 * 5);
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

function createOnDemandInstance(Vm $vm): array {
    return $vm->createInstance([
        'ManageRecordings' => true,
        'Tags' => 'foo,bar',
    ]);
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
        //'HasUserJoined': can be missing
        //'Recording': can be missing
        'StartTime',
        'EndTime',
        //'ParticipantCount': can be missing
        //'ListenerCount': can be missing
        //'VideoCount': can be missing
        //'MaxUsers': can be missing
        //'ModeratorCount': can be missing
    ];
    checkArrHasNotEmptyItems($meetingKeys, $meetingData);
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

function markTestIncomplete(string $test) {
    throw new IncompleteTestError($test);
}

function main(): void {
    $vm = mkVm();

    register_shutdown_function(function () use ($vm) {
        writeLnIndent("Deleted leftover instances: " . deleteAllInstances($vm), 0);
    });

    $indent = 0;

    writeLnIndent("Tests started at " . (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s') . ' (UTC)', $indent);

    $availableInstances = $totalInstances = 0;
    foreach ($vm->getInstances()['data'] as $instanceData) {
        if ($instanceData['Status'] === InstanceStatus::AVAILABLE) {
            $availableInstances++;
        }
        $totalInstances++;
    }
    writeLnIndent("Total instances: " . $totalInstances, $indent);
    writeLnIndent(InstanceStatus::AVAILABLE . " instances: " . $availableInstances, $indent);
    writeLnIndent("Deleted leftover instances: " . deleteAllInstances($vm) . "\n", $indent);

    // Billing
    test("Vm::getBillingSummary()", function () use ($vm) {
        $result = checkSuccessResult($vm, $vm->getBillingSummary());
        foreach ($result['data'] as $billingActivityData) {
            checkArrHasNotEmptyItems(['ID', 'CustomerID', 'Year', 'Week', 'Updated'], $billingActivityData);
        }
    }, null, $indent);

    // Instances
    test("Vm::getInstances(),
        Vm::createInstance(),
        Vm::getInstance(),
        Vm::stopInstance(),
        Vm::deleteInstance(),
        Vm::startInstance(),
        Vm::getInstanceHistory()", function () use ($indent, $vm) {
        $indent += 1;

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
            Vm::deleteInstance(): delete " . InstanceStatus::STOPPED . " instance,
            Vm::deleteInstance(): delete " . InstanceStatus::DELETED . " instance", function () use ($indent, $vm) {
            $indent += 1;

            $instanceId = test('Vm::createInstance() with ManagedRecordings and with Tags', function () use ($indent, $vm) {
                $createInstanceResult = createOnDemandInstance($vm);
                checkCreateInstanceResult($vm, $createInstanceResult);
                $instanceId = $createInstanceResult['data']['ID'];
                waitInstanceStatus($vm, $instanceId, InstanceStatus::AVAILABLE, $indent + 1);
                return $instanceId;
            }, null, $indent);

            test("Vm::getInstance()", function () use ($instanceId, $vm) {
                $result = checkSuccessResult($vm, $vm->getInstance($instanceId));
                checkInstanceData($result['data']);
            }, null, $indent);

            test("Vm::startInstance(): start " . InstanceStatus::AVAILABLE . " instance", function () use ($indent, $vm, $instanceId) {
                waitInstanceStatus($vm, $instanceId, InstanceStatus::AVAILABLE, $indent + 1);
                $result = $vm->startInstance($instanceId);
                checkErrorResult($vm, $result, 'Cannot start an already AVAILABLE instance', 400);
                checkInstanceStatus($vm, $instanceId, InstanceStatus::AVAILABLE); // should be not changed
            }, null, $indent);

            test("Vm::stopInstance(): stop " . InstanceStatus::AVAILABLE . " instance", function () use ($indent, $instanceId, $vm) {
                checkInstanceStatus($vm, $instanceId, InstanceStatus::AVAILABLE);
                checkSuccessResult($vm, $vm->stopInstance($instanceId), true);
                waitInstanceStatus($vm, $instanceId, InstanceStatus::STOPPED, $indent + 1);
            }, null, $indent);

            test("Vm::stopInstance(): stop " . InstanceStatus::STOPPED . " instance", function () use ($vm, $instanceId) {
                checkInstanceStatus($vm, $instanceId, InstanceStatus::STOPPED);
                $result = $vm->stopInstance($instanceId);
                checkErrorResult($vm, $result, 'Instance is already stopped', 400);
                checkInstanceStatus($vm, $instanceId, InstanceStatus::STOPPED); // should be not changed
            }, null, $indent);

            test("Vm::startInstance(): start " . InstanceStatus::STOPPED . " instance", function () use ($indent, $vm, $instanceId) {
                checkInstanceStatus($vm, $instanceId, InstanceStatus::STOPPED);
                checkSuccessResult($vm, $vm->startInstance($instanceId), true);
                waitInstanceStatus($vm, $instanceId, InstanceStatus::AVAILABLE, $indent + 1);
            }, null, $indent);

            test("Vm::getInstanceHistory()", function () use ($vm, $instanceId) {
                $result = checkSuccessResult($vm, $vm->getInstanceHistory($instanceId));
                foreach ($result['data'] as $instanceHistoryData) {
                    checkArrHasNotEmptyItems(['ID', 'Type', 'TimeStamp'], $instanceHistoryData);
                }
            }, null, $indent);

            test("Vm::deleteInstance(): delete " . InstanceStatus::STOPPED . " instance", function () use ($indent, $instanceId, $vm) {
                $instanceStatus = $vm->getInstance($instanceId)['data']['Status'];
                if ($instanceStatus !== InstanceStatus::STOPPED) {
                    assert($instanceStatus !== InstanceStatus::DELETED);
                    $vm->stopInstance($instanceId);
                    waitInstanceStatus($vm, $instanceId, InstanceStatus::STOPPED, $indent + 1);
                }
                checkInstanceStatus($vm, $instanceId, InstanceStatus::STOPPED);
                checkSuccessResult($vm, $vm->deleteInstance($instanceId), true);
                waitInstanceStatus($vm, $instanceId, InstanceStatus::DELETED, $indent + 1);
            }, null, $indent);

            test('Vm::deleteInstance(): delete ' . InstanceStatus::DELETED . ' instance', function () use ($vm, $instanceId) {
                checkInstanceStatus($vm, $instanceId, InstanceStatus::DELETED);
                // this is idempotent API call
                checkSuccessResult($vm, $vm->deleteInstance($instanceId), true);
            }, null, $indent);
        }, null, $indent);

        test("Vm::createInstance() without ManagedRecordings and without Tags,
            Vm::getInstance(),
            Vm::deleteInstance(): delete " . InstanceStatus::AVAILABLE . " instance", function () use ($indent, $vm) {
            $indent += 1;

            $result = $vm->createInstance();
            checkCreateInstanceResult($vm, $result);
            $instanceId = $result['data']['ID'];
            waitInstanceStatus($vm, $instanceId, InstanceStatus::AVAILABLE, $indent);

            test("Vm::getInstance()", function () use ($instanceId, $vm) {
                $result = checkSuccessResult($vm, $vm->getInstance($instanceId));
                checkInstanceData($result['data']);
            }, null, $indent);

            test("Vm::deleteInstance(): delete " . InstanceStatus::AVAILABLE . " instance", function () use ($instanceId, $indent, $vm) {
                waitInstanceStatus($vm, $instanceId, InstanceStatus::AVAILABLE, $indent + 1);
                checkSuccessResult($vm, $vm->deleteInstance($instanceId), true);
                waitInstanceStatus($vm, $instanceId, InstanceStatus::DELETED, $indent + 1);
            }, null, $indent);
        }, null, $indent);
    }, null, $indent);

    // Meetings
    test("Vm::getMeetings(),
        Vm::getMeeting()", function () use ($vm) {
        $result = checkSuccessResult($vm, $vm->getMeetings());

        // There should be always not empty list of meetings for testing:
        $meeting = null;
        foreach ($result['data'] as $meetingData) {
            checkMeetingData($meetingData);
            $meeting = $meetingData;
        }
        assert(is_array($meeting));

        $result = checkSuccessResult($vm, $vm->getMeeting($meeting['MeetingID']));
        checkMeetingData($result['data']);
    }, null, $indent);

    // Recordings
    test("Vm::getRecordings(),
        Vm::getRecording(),
        Vm::unpublishRecording(),
        Vm::deleteRecording(),
        Vm::publishRecording()", function () use ($indent, $vm) {
        $indent += 1;

        $testingRecording = test("Vm::getRecordings()", function () use ($indent, $vm) {
            $getRecordingsResult = checkSuccessResult($vm, $vm->getRecordings());

            // There should be always at least 1 recordings for testing:
            $testingRecording = null;
            foreach ($getRecordingsResult['data'] as $recordingData) {
                checkRecordingData($recordingData);
                $testingRecording = $recordingData;
            }
            assert(is_array($testingRecording));

            return $testingRecording;
        }, null, $indent);

        test("Vm::getRecording()", function () use ($testingRecording, $vm) {
            // It does not matter which recording to use for testing getRecording() - it can either published or unpublished one and we use the published recording.
            $recordingId = $testingRecording['RecordID'];
            $result = checkSuccessResult($vm, $vm->getRecording($recordingId));
            checkRecordingData($result['data']);
        }, null, $indent);

        test("Vm::publishRecording(),
            Vm::unpublishRecording()", function () use ($indent, $vm, $testingRecording) {
            if ($testingRecording['State'] === RecordingState::PUBLISHED) {
                $result = $vm->unpublishRecording($testingRecording['RecordID']);
                checkSuccessResultShapeAndStatusCode($vm, $result);
                // todo: right now $result['data'] === '', expected null
                assert($result['data'] === '');
                waitRecordingState($vm, $testingRecording['RecordID'], RecordingState::UNPUBLISHED, $indent + 1);
            } elseif ($testingRecording['State'] === RecordingState::UNPUBLISHED) {
                $result = $vm->publishRecording($testingRecording['RecordID']);
                checkSuccessResult($vm, $result);
                waitRecordingState($vm, $testingRecording['RecordID'], RecordingState::PUBLISHED, $indent + 1);
            }
        }, null, $indent);

        test("Vm::deleteRecording()", function () use ($indent, $vm, $testingRecording) {
            $recordingId = $testingRecording['RecordID'];
            checkSuccessResult($vm, $vm->getRecording($recordingId)); // Ensure that the recording exist before deletion
            $result = $vm->deleteRecording($recordingId);
            checkSuccessResultShapeAndStatusCode($vm, $result);
            // todo: right now $result['data'] === '', expected null
            assert($result['data'] === '');
            /* todo:
            waitUntil(function (int $i, int $sleepSecs) use ($recordingId, $indent, $vm): bool {
                foreach ($vm->getRecordings()['data'] as $recordingData) {
                    if ($recordingData['RecordID'] === $recordingId) {
                        writeLnIndent("Waiting until recording '$recordingId' will be deleted, waiting $sleepSecs seconds...", $indent);
                        return false;
                    }
                }
                writeLnIndent("The recording '$recordingId' has been deleted sucessfully", $indent + 1);
                return true;
            }, "Recording '$recordingId' can't be deleted, waited: {waitedSecs} seconds", 60 * 5);
            */
        }, null, $indent);
    }, null, $indent);

    // Regions
    test("Vm::getRegions()", function () use ($vm) {
        $result = checkSuccessResult($vm, $vm->getRegions());
        foreach ($result['data'] as $key => $regionData) {
            assert((bool) preg_match('~^[-0-9a-z]+$~si', $key));
            checkArrHasNotEmptyItems(['Name', 'Town', 'Continent'], $regionData);
        }
    }, null, $indent);
}

main();