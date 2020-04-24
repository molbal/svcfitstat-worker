<p align="center">
<img src="logo_giant.png" alt="logo" width="460">
</p>

# Introduction
This repository focuses on how the [CLI modified Pyfa](https://github.com/molbal/Pyfa) runs in Docker and how it is exposed

Please see the [svcfitstat](https://github.com/molbal/svcfitstat) repository for the full solution, with an architecture diagram. 

# Usage
The image is published in Docker Hub, so usage is straightforward:

`docker pull molbal/svcfitstat`

https://hub.docker.com/r/molbal/svcfitstat

## Webservice calls
By default the container listens on port 80. There is only one endpoint, which takes both *GET* and *POST* requests. GET requests are trivial and POST requests have to made as if html form submitted it. (So the content type must be `application/x-www-form-urlencoded`)

### Parameters
At all times returned content will have the `Content-type: application/json` header and will be a valid json. One exception could be a severely broken input that makes Pyfa hang.

|Parameter|Methods|Description|Mandatory|
|---|---|---|---|
|secret|GET,POST|Secret key, defined in the SFS_SECRET environment variable|Yes, if set in the environment variable|
|fit|GET, POST|The fit, in EFT format. You should make sure it is a valid format, because the wrapper does not check it, just feeds it to Pyfa. If supplied via GET parameter, it passes through PHP's [urldecode](https://www.php.net/manual/en/function.urldecode.php) function, so encode it properly. If via POST, it is not urldecoded.|
|restart|GET|If set, it tries to immediately restart the container|-|

### Example output
...

## Resources
When idle an unused, the container uses *20MB* of memory. When used it peaks at around *350-400 MB* and then returns to *150-200 MB* idle memory. The container size is *1.69 GB*

## Technical solution
It was challanging to get it working. The container uses a CentOS 7 base image and has installs following software:

|Package name|What for?|
|---|---|
|python36u|Python environment|
|python36u-libs|Python environment|
|python36u-devel|Python environment|
|python36u-pip|Python package manager|
|git|Getting modified pyfa|
|gcc-c++|A wxWidgets is a dependency of Pyfa and we need to compile it|
|make|A wxWidgets is a dependency of Pyfa and we need to compile it|
|which|A wxWidgets is a dependency of Pyfa and we need to compile it|
|gtk3|Pyfa dependency|
|gtk3-devel|Pyfa dependency|
|xorg-x11-server-Xvfb|Emulating a display|
|httpd|Exposing Pyfa CLI|
|yum-utils|For adding a repository to yum|
|php|v7.4, the wrapper script|
|php-opcache|minor performance boost for the wrapper|
|python/pathlib2|Pyfa building requirement|

It builds the modified pyfa's dependencies and simulates a virtual screen where it can run. 
I modified it earlier so it also prints the stats to stdout, which is more than enough for us. 
Its process terminates after that and the [wrapper script](index.php) can parse its results.

During build period a pre-built eve.db is entered to shorten the build time.

## Version numbers
The first part of version will refer to the integration built around pyfa, and the second part is the base pyfa's version.
So for example, 0.9-2.20.2 is for the 0.9 version for the integration layers, built around the 2.20.2 original Pyfa. 


# Configuration
The container can be configured with the followign environment variables:

|Name|Description|Default value if not set|
|---|---|---|
|SFS_FIT_MAX_LENGTH|Maximum length of input allowed (chars)|2048|
|SFS_MAX_EXEC_TIME|Maximum runtime of a single get (seconds)|15|
|SFS_ADDITIONAL_CMD|Additional command line parameters for Pyfa|`-r -l Critical`|
|SFS_SECRET|Secret key, that the web service asks for. If not set, specifying this key is unnecessary.|not set|

# Future
The container size is 1.69GB which is a bit heavy. Also, building the image is longer than I would like (10m+).
