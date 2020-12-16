<?php declare(strict_types=1);
namespace BBBondemand;

abstract class Endpoint {
    // ------------------------------------------------------------------------
    // Billing:

    // GET
    public const BILLING_SUMMARY = 'billing/activity';

    // ------------------------------------------------------------------------
    // Instances:

    // GET
    public const LIST_INSTANCES = 'instances';
    // POST
    public const CREATE_INSTANCE = 'instances';
    // GET
    public const GET_INSTANCE = 'instances/{instanceID}';
    // DELETE
    public const DELETE_INSTANCE = 'instances/{instanceID}';
    // POST
    public const START_INSTANCE = 'instances/start';
    // POST
    public const STOP_INSTANCE = 'instances/stop';
    // GET
    public const INSTANCE_HISTORY = 'instances/{instanceID}/history';

    // ------------------------------------------------------------------------
    // Meetings:

    // GET
    public const LIST_MEETINGS = 'meetings';
    // GET
    public const GET_MEETING = 'meetings/{meetingID}';

    // ------------------------------------------------------------------------
    // Recordings:

    // GET
    public const LIST_RECORDINGS = 'recordings';
    // GET
    public const GET_RECORDING = 'recordings/{recordingID}';
    // POST
    public const PUBLISH_RECORDING = 'recordings/publish';
    // POST
    public const UNPUBLISH_RECORDING = 'recordings/unpublish';
    // DELETE
    public const DELETE_RECORDING = 'recordings/{recordingID}';

    // ------------------------------------------------------------------------
    // Regions:

    // GET
    public const LIST_REGIONS = 'regions';
}