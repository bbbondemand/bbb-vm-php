<?php declare(strict_types=1);
/**
 * /**
 *  BBB On Demand VM library for PHP
 *
 *  This allows customers to create and manage their own, dedicated virtual servers running BBB. So the '/bigbluebutton/api' end *  point is used
 *  to manage meetings using a standard BBB library or integration; the /vm endpoint is used to manage your own virtual machines - and you would
 *  then use a BBB library to interact with the actual BBB instance running on each machine.
 *
 * @author Richard Phillips
 */

namespace BBBondemand;

class RecordingsApiRoute {
    public const LIST = 'recordings';
    public const GET = 'recordings/{recordingID}';
    public const PUBLISH = 'recordings/{recordingID}';
    public const UNPUBLISH = 'recordings/{recordingID}';
    public const DELETE = 'recordings/{recordingID}';
}
