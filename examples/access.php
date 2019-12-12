<?php

/*%******************************************************************************************%*/
// SETUP

// Enable full-blown error reporting. http://twitter.com/rasmus/status/7448448829
error_reporting(-1);

// Set plain text headers
header("Content-type: text/plain; charset=utf-8");

require_once '../vendor/autoload.php';
require_once '../config.php';

use Fcs\Fcs;

Fcs::configure($FCS_CONFIG);
$fcs = new Fcs();

// Get the access token
Fcs::debug('get token');
$uri = $fcs->getUserLibraryUri('XY', 'user@mail.com');

Fcs::debug('uri='.$uri);

header("Location: $uri");

