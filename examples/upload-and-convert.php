<?php

/*%******************************************************************************************%*/
// SETUP

// Enable full-blown error reporting. http://twitter.com/rasmus/status/7448448829
error_reporting(-1);

// Set plain text headers
header("Content-type: text/plain; charset=utf-8");

require_once 'vendor/autoload.php';
require_once 'config.php';

use Fcs\Fcs;
use Fcs\AssetTypes;

Fcs::configure($FCS_CONFIG);
$fcs = new Fcs();

/*%******************************************************************************************%*/
// Upload Assets to the Cloud and then perform conversions

// Put the Product (Title) to the Cloud
echo "Put the Product...\n";
$product = array("tag" => "SAMPLES-9780306406157",
                 "title" => "The Count of Monte Cristo",
                 "ean13" => "9780306406157",
                 "publisher-name" => "Public Domain",
                 "full-author-display-name" => "Alexandre Dumas");

$product = $fcs->putProduct($product);

// Upload the ePub
echo "Upload the ePub...\n";
$epub = $fcs->uploadAsset($product, 'examples/testfiles/monteCristo.epub');
$epubId = $epub['id'];

// Upload the Cover image
echo "Upload the Cover...\n";
$cover = $fcs->uploadAsset($product, 'examples/testfiles/monteCristo.jpg');
$coverId = $cover['id'];

// Request conversion from ePub to Temporary Protected (DRM) format
$drmConversion = $fcs->convertAsset($product, $epubId, AssetTypes::TDrm);

// Request conversion from ePub to Kindle (MOBI) format
$kindleConversion = $fcs->convertAsset($product, $epubId, AssetTypes::Kindle);


// Now let's check the status of the DRM Conversion
$conversion = $fcs->getConversion($drmConversion['id']);
while (!$fcs->conversionIsApproved($conversion)) {
    if ($fcs->conversionHasError($conversion)) {
        echo "DRM Conversion Error: " . $conversion['error-text'] . "\n";
        break;
    }
    sleep(1);
    $conversion = $fcs->getConversion($drmConversion['id']);
}

echo "The DRM Conversion is completed...\n";

// Check the status of the Kindle Conversion
$conversion = $fcs->getConversion($kindleConversion['id']);
while (!$fcs->conversionIsApproved($conversion)) {
    if ($fcs->conversionHasError($conversion)) {
        echo "Kindle Conversion Error: " . $conversion['error-text'] . "\n";
        break;
    }
    sleep(1);
    $conversion = $fcs->getConversion($kindleConversion['id']);
}

echo "The Kindle Conversion is completed...\n";

// We can check whether the converted DRM Asset is Available (ie. the file exists)
$available = $fcs->assetIsAvailable($drmConversion['target-id']);
if ($available) {
    echo "The converted DRM Asset is Available...\n";
}

// We can check whether the converted Kindle Asset is Available (ie. the file exists)
$available = $fcs->assetIsAvailable($kindleConversion['target-id']);
if (!$available) {
    echo "The converted Kindle Asset is Available...\n";
}
