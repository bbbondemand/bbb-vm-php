<?php declare(strict_types=1);
namespace BBBondemand;

abstract class Endpoint {
    // Billing
    public const BILLING_SUMMARY = 'billing/activity';
    // Instances
    public const LIST_INSTANCES = 'instances';
    public const CREATE_INSTANCE = 'instances';
    public const GET_INSTANCE = 'instances/{instanceID}';
    public const DELETE_INSTANCE = 'instances/{instanceID}';
    public const START_INSTANCE = 'instances/start';
    public const STOP_INSTANCE = 'instances/stop';
    public const INSTANCE_HISTORY = 'instances/{instanceID}/history';
    // Meetings
    public const LIST_MEETINGS = 'meetings';
    public const GET_MEETING = 'meetings/{meetingID}';
    // Recordings
    public const LIST_RECORDINGS = 'recordings';
    public const GET_RECORDING = 'recordings/{recordingID}';
    public const PUBLISH_RECORDING = 'recordings/{recordingID}';
    public const UNPUBLISH_RECORDING = 'recordings/{recordingID}';
    public const DELETE_RECORDING = 'recordings/{recordingID}';
    // Regions
    public const LIST_REGIONS = 'regions';
}