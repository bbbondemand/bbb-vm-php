BBB On Demand VM library for PHP
=========================

BigBlueButton is the leading open source software for video seminar and classrooms. [BBB On Demand](https://bbbondemand.com) provides BigBlueButton meetings and instances at cloud scale.  The service provides two API endpoints: 

The first (https://api.bbbondemand.com/v1/*CustomerID*/**bigbluebutton/api**) aims to be compatible with the BBB Api specification and allows the use of [standard BBB libraries](https://github.com/bigbluebutton/bigbluebutton-api-php) to manage meetings 'as if' you are using a single BBB instance. In the background your meetings are spread across shared, cloud scaled virtual servers managed by BBB On Demand. 

The Second API end point (https://api.bbbondemand.com/v1/*customerID*/**vm**) allows customers to create and manage their own, *dedicated virtual servers* running BBB - and you would then use a [BBB library](https://github.com/bigbluebutton/bigbluebutton-api-php) to interact with the actual BBB instance running on each machine.

This github project is a PHP library to interact with the 'vm' API endpoint.  You can use this library to create, stop, start and delete virtual machines running BigBlueButton.  These machines are not shared by other customers and persist data if stopped and restarted (though data is lost if you delete the machine).

The CreateMeeting method of this library returns a URL and secret for the BigBlueButton instance - and you canthen use any BigBlueButton library (such as this one for php) to create meetings etc.  So you use the bbbondemand.com API to manage dedicated *instances* and vanilla BBB libraries to manage *meetings*.

Managed Recordings
--------
You have two options for how to handle recordings.  

If you use the ManagedRecordings flag when creating an instance, any recordings will be processed on a separate dedicated virtual machine and then moved to cloud storage.  BBB On Demand will then serve the recording from our cloud scale [recordings server](https://recordings.bbbondemand.com).  In this instance you would manage recordings using the /vm API endpoint using the 'Recordings' methods of this library.  Using the ManagedRecordings option incurs additional charges - [please see the website.](https://www.bbbondemand.com) 

If turn off ManagedRecordings, the instance will work as a 'normal' BBB Instance and you manage recordings using the normal BBB Api for each instance.  You need to remember that if you delete an instance, any recordings on that instance are lost.  Also, the instances are fixed at 10GB and you may run out of disk space at some point.        

API Specification
--------
The Swagger documentation for the API is [explained here:](https://api.bbbondemand.com/v1/openapi/index.html) and or see the [raw swagger.json](https://api.bbbondemand.com/v1/openapi/swagger.json)  

API requests are authenticated with the customer ID in the URL path and a header APITOKEN containing the secret provided on your BBB On Demand customer account page. This is handled automatically by the library by calling the creator as follows: 

Usage
-----
Todo

Tests
-----
Copy phpunit.xml.template to phpunit.xml and edit to add your customer ID, API Token and a random string up to 32 chars for the instance name to test. Note that the tests create actual instances and will thus incur charges to your BBB ON Demand account.  

Warning
--------
The use of this library using a live customer ID and secret will result services used which result in billable costs.  It is your responsibility to make sure that you delete machines and or recordings that you do not wish to be charged for.  

Library Features
--------

* PSR-4 autoloading compliant structure
* Unit-Testing with PHPUnit

PHP 7.1 or later is recommended and the library is untested with earlier versions. 


Credits
-------
With thanks to Bhavdip Pambhar (@bhavdip111) for his work on this library.

All copyrights and intelectual property of the wonderful BigBlueButton open source project are acknowledged.  