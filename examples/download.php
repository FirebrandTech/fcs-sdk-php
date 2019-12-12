<?php
/*%******************************************************************************************%*/
// SETUP

// Enable full-blown error reporting. http://twitter.com/rasmus/status/7448448829
error_reporting(-1);

require_once '../vendor/autoload.php';
require_once '../config.php';

use Fcs\Fcs;
use Fcs\AssetTypes;

$fcs = new Fcs($FCS_CONFIG);

if (array_key_exists('type', $_GET)) {
    $type = $_GET['type'];
    $uri = $fcs->getAssetUriByEan("9780306406157",
                                  $type,                // Epub or Pdf for Unprotected
                                                        //     TDrm for Temporary Protected
                                                        //     PDrm for Permanent Protected
                                  9.99,                 // Digital list or sales price
                                                        //     If this is null or "",
                                                        //     the TMM price will be used
                                  get_current_user());  // This is a unique id or name of the
                                                        //     current user.  Used to determine
                                                        //     downloads per user.

    header("Location: $uri");
    // If you have the Asset Id you can call the following instead
    /*
    $fcs->getAssetUriByEan("21852b59-7c04-4794-ac70-a06f015ef436",  // This is the Id of an the Asset
                           9.99,                                    // Digital list or sales price
                                                                    //     If this is null or "",
                                                                    //     the TMM price will be used
                           get_current_user());                     // This is a unique id or name of the
                                                                    //     current user.  Used to determine
                                                                    //     downloads per user.
     */
}
?>
<html>
<head>
    <title>Test FCS SDK</title>
</head>
<body>
<a href="download.php?type=<?php echo AssetTypes::Epub?>">Download ePub</a><br>
<a href="download.php?type=<?php echo AssetTypes::Cover?>">Download Cover</a><br>
<a href="download.php?type=<?php echo AssetTypes::TDrm?>">Download Temporary Protected</a><br>
<a href="download.php?type=<?php echo AssetTypes::Kindle?>">Download Kindle (Mobi)</a><br>
</body>
</html>