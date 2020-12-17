# BBB On Demand VM library for PHP

This library is an official client to interact with instances via REST API managed by the [BBB On Demand](https://bbbondemand.com) - a service, which provides BigBlueButton meetings and instances on demand at cloud scale. The service provides everything for customers to run BigBlueButton meetings in a cloud without worrying about an infrastructure.

The library allows to:
* manage instances running BigBlueButton
* get details about meetings
* manage recordings and more.

## Usage of the library

1. Ensure that you have [composer](https://getcomposer.org/doc/00-intro.md) installed.

2. Add to you composer.json file:
```json
{
    "repositories": [
        {
            "url": "https://github.com/bbbondemand/bbb-vm-php",
            "type": "git"
        }
    ],
    "require": {
        "bbbondemand/bbb-vm-php": "dev-main"
    }
}
```

3. Install the library and its dependencies by running:
```sh
composer install --no-dev
```

4. To interact with the service [register on the BBBOnDemand](https://bbbondemand.com/register) and get the following credentials:
* Customer Account ID
* APITOKEN for the For On Demand Instances.

5. Run the code sample using the credentials above:
```php
<?php declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';

$vm = \BBBondemand\Vm::mk([
    'customerId' => 'Your Customer Account ID',
    'customerApiToken' => 'Your APITOKEN for the For On Demand Instances',
]);
var_dump(
    $vm->getInstances()
);
```

## Available methods (API)

Main class to interact with the services is Vm. Its method names are very close to [REST API](https://bbbondemand.com/swagger) provided by the service:
* REST API calls:
    * Billing
        * getBillingSummary()
    * Instances
        * getInstances()
        * createInstance()
        * getInstance()
        * stopInstance()
        * deleteInstance()
        * startInstance()
        * getInstanceHistory()
    * Meetings
        * getMeetings()
        * getMeeting()
    * Recordings
        * getRecordings()
        * getRecording()
        * unpublishRecording()
        * deleteRecording()
        * publishRecording()
    * Regions
        * getRegions()
* Other utility methods:
    * setUrlBuilder()
    * getUrlBuilder()
    * send()
    * getLastResponse()
    * setHttpClient()
    * getHttpClient()

Please check [the official Swagger documenation](https://bbbondemand.com/swagger) for the full description of the supported REST API by this library.

## Examples of usage

Many examples of usage can be found in the [./tests directory](./tests).

## Warning

The result of using of this library may cost money. Please check the [pricing page](https://bbbondemand.com/pricing) for more details.  

## Credits

Thansk to:
* Bhavdip Pambhar (@bhavdip111): for initial version of this library
* @jackstr: for many improvements

## Copyrights

All copyrights and intelectual property of the wonderful BigBlueButton open source project are acknowledged.