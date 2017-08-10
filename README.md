# FCS SDK for PHP

The FCS SDK for PHP enables PHP developers to build solutions with Firebrand Cloud Services (FCS).
With the FCS SDK for PHP, developers can get started in minutes by using [Composer](http://getcomposer.org).

This new version is built on top of [Guzzle](http://guzzlephp.org), a PHP HTTP client
framework, which provides increased performance.  The FCS SDK for PHP requires PHP 5.3.2.

## Before Using the SDK

There is some basic information you need to know before you get started using the SDK.

### Signing Up for FCS

First your organization must be setup with Firebrand Technologies Title Management and
Content Services.

### Configuration Settings for FCS

Your FCS Configuration Settings will be sent to your organization.

## Installing the SDK

Using [Composer](http://getcomposer.org) is the recommended way to install the FCS SDK for PHP . Composer is
dependency management tool for PHP that allows you to declare the dependencies your project needs and installs them into
your project. In order to use the FCS SDK for PHP through Composer, you must do the following:

1. Add `"fcs/fcs-sdk-php"` as a dependency in your project's `composer.json` file.

    ```json
        {
            "require": {
                "fcs/fcs-sdk-php": "*"
            }
        }
    ```

1. Download and install Composer.

        curl -s "http://getcomposer.org/installer" | php

1. Install your dependencies.

        php composer.phar install

1. Require Composer's autoloader.

    Composer also prepares an autoload file that's capable of autoloading all of the classes in any of the libraries that
    it downloads. To use it, just add the following line to your code's bootstrap process.

        require '/path/to/sdk/vendor/autoload.php';

You can find out more on how to install Composer, configure autoloading, and other best-practices for defining
dependencies at [getcomposer.org](http://getcomposer.org).

## Using the SDK

### Quick Start

To get started downloading a file from FCS you can do the following:

```php
<?php

// Include the SDK along with you other project dependencies
// using the Composer autoloader
require 'vendor/autoload.php';

use Fcs\Fcs;
use Fcs\AssetTypes;

$fcs = new Fcs(array(
    'url' => 'fcs-services-url',
    'key' => 'your-fcs-access-key',
    'secret' => 'your-fcs-access-secret'
));

$uri = $fcs->getAssetUriByEan("9780306406157",      // EAN/ISBN13 of the book to download
                              "epub",               // See Asset Types below
                              9.99,                 // Digital list or sales price
                                                    //     If this is null or "",
                                                    //     the TMM price will be used
                              get_current_user());  // This is a unique id or name of the
                                                    //     current user.  Used to determine
                                                    //     downloads per user.

header("Location: $uri");
```

### Asset Types
The following is a list of the asset types that can be used in getAssetUriByEan.  Auto asset types are used if your account is configured to do Auto DRM conversion.  If conversion requests are made manually, the non Auto asset types are used instead.

Asset Type|Description
----------|-----------
**"epub"**|Unprotected (Open) EPUB
**"publisherkindle"**|Mobi (created by the publisher, rathern than being auto converted from an EPUB)
**"pdf"**|Unprotected (Open) PDF
**"tdrmepubauto"**|Temporarily (55 days) Protected (Adobe DRM) EPUB.  Auto converted.
**"tdrmpdfauto"**|Temporarily (55 days) Protected (Adobe DRM) PDF.  Auto converted.
**"tdrmauto"**|Temporarily (55 days) Protected (Adobe DRM) asset (EPUB if it exists; PDF otherwise).  Auto converted.
**"tdrmepub"**|Temporarily (55 days) Protected (Adobe DRM) EPUB.  Manually converted.
**"tdrmpdf"**|Temporarily (55 days) Protected (Adobe DRM) PDF.  Manually converted.
**"tdrm"**|Temporarily (55 days) Protected (Adobe DRM) asset (EPUB if it exists; PDF otherwise).  Manually converted.
**"pdrmepubauto"**|Permanently Protected (Adobe DRM) EPUB.  Auto converted.
**"pdrmpdfauto"**|Permanently Protected (Adobe DRM) PDF.  Auto converted.
**"pdrmauto"**|Permanently Protected (Adobe DRM) asset (EPUB if it exists; PDF otherwise).  Auto converted.
**"pdrmepub"**|Permanently Protected (Adobe DRM) EPUB.  Manually converted.
**"pdrmpdf"**|Permanently Protected (Adobe DRM) PDF.  Manually converted.
**"pdrm"**|Permanently Protected (Adobe DRM) asset (EPUB if it exists; PDF otherwise).  Manually converted.
**"ancillaryzip"**|Unprotected zip file that is associated with a title but ancillary to it.
**"ancillarypdf"**|Unprotected PDF file that is associated with a title but ancillary to it.
**"ancillarypdrmpdf"**|Permanently Protected (Adobe DRM) PDF that is associated with a title but ancillary to it.  Auto converted.
