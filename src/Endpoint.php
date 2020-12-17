<?php declare(strict_types=1);
/**
 * BBB On Demand PHP VM Library
 *
 * Copyright (c) BBB On Demand
 * All rights reserved.
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this
 * software and associated documentation files (the "Software"), to deal in the Software
 * without restriction, including without limitation the rights to use, copy, modify, merge,
 * publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons
 * to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or
 * substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED *AS IS*, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE
 * FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */
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