<?php

namespace Fcs;

use DOMNode;
use DOMDocument;
use ErrorException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;

/*
function fcsDisplayArray($arrayname, $tab = "&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp", $indent = 0) {
    $curtab = "";
    $returnvalues = "";
    while (list($key, $value) = each($arrayname)) {
        for ($i = 0; $i < $indent; $i++) {
            $curtab .= $tab;
        }
        if (is_array($value)) {
            $returnvalues .= "$curtab$key : Array: <br />$curtab{<br />\n";
            $returnvalues .= fcsDisplayArray($value, $tab, $indent + 1) . "$curtab}<br />\n";
        }
        else $returnvalues .= "$curtab$key => $value<br />\n";
        $curtab = NULL;
    }
    return $returnvalues;
}
*/

class AssetTypes {
    const Epub = 'CLD_AT_Epub';
    const Pdf = 'CLD_AT_WebPdf';
    const Kindle = 'CLD_AT_Kindle';
    const PublisherKindle = 'CLD_AT_PublisherKindle';
    const Cover = 'CLD_AT_CoverArtHigh';
    const TDrm = 'CLD_AT_TDrm'; // Temporary Protected (ACS) - expires after 55 days
    const TDrmEpub = 'CLD_AT_TDrmEpub'; // Temporary Protected EPUB (ACS) - expires after 55 days
    const TDrmPdf = 'CLD_AT_TDrmPdf'; // Temporary Protected PDF (ACS) - expires after 55 days
    const PDrm = 'CLD_AT_PDrm'; // Permanent Protected (ACS)
    const PDrmEpub = 'CLD_AT_PDrmEpub'; // Permanent Protected EPUB (ACS)
    const PDrmPdf = 'CLD_AT_PDrmPdf'; // Permanent Protected PDF (ACS)
    const EDrmEpub = 'CLD_AT_EDrmEpub'; // Enthrill social DRMed EPUB
    const PLcpDrmEpub = 'CLD_AT_LcpDrmEpub'; // Permanent Protected EPUB (LCP)
}

class ConversionStatuses {
    const Requested = 'CLD_CS_Requested';
    const Accepted = 'CLD_CS_Accepted';
    const Completed = 'CLD_CS_Completed';
    const Approved = 'CLD_CS_Approved';
    const Rejected = 'CLD_CS_Rejected';
    const Canceled = 'CLD_CS_Canceled';
    const Failed = 'CLD_CS_Failed';
    const Error = 'CLD_CS_Error';
}

class AssetStatuses {
    const Pending = 'CLD_AS_Pending';
    const Uploaded = 'CLD_AS_Uploaded';
    const Approved = 'CLD_AS_Approved';
    const Rejected = 'CLD_AS_Rejected';
    const Deleted = 'CLD_AS_Deleted';
    const Archived = 'CLD_AS_Archived';
    const OnHold = 'CLD_AS_OnHold';
}

class Fcs {
    const CHUNK_SIZE = 1048576; // 1 MB
    const ATTRIBUTES = '__attributes__';
    const CONTENT = '__content__';
    const NEWLINE = "\n";
    const CLD_NAMESPACE = 'http://cloud.firebrandtech.com/';
    const MODEL_NAMESPACE = 'http://schemas.datacontract.org/2004/07/Cloud.Model';

    // When uploading an asset, if you do not specify an asset type, the file extension
    // will be used to look up the default asset type here:
    private static $_assetTypes = array("epub" => AssetTypes::Epub,
                                        "pdf" => AssetTypes::Pdf,
                                        "mobi" => AssetTypes::Kindle,
                                        "jpg" => AssetTypes::Cover,
                                        "gif" => AssetTypes::Cover,
                                        "png" => AssetTypes::Cover,
                                        "acsm" => AssetTypes::TDrm,
                                        "tdrm" => AssetTypes::TDrm,
                                        "pdrm" => AssetTypes::PDrm);

    private static $_mimeTypes = array("acsm" => "application/vnd.adobe.adept+xml",
                                       "ai" => "application/postscript",
                                       "aif" => "audio/x-aiff",
                                       "aifc" => "audio/x-aiff",
                                       "aiff" => "audio/x-aiff",
                                       "asc" => "text/plain",
                                       "atom" => "application/atom+xml",
                                       "au" => "audio/basic",
                                       "avi" => "video/x-msvideo",
                                       "bcpio" => "application/x-bcpio",
                                       "bin" => "application/octet-stream",
                                       "bmp" => "image/bmp",
                                       "cdf" => "application/x-netcdf",
                                       "cgm" => "image/cgm",
                                       "class" => "application/octet-stream",
                                       "cpio" => "application/x-cpio",
                                       "cpt" => "application/mac-compactpro",
                                       "csh" => "application/x-csh",
                                       "css" => "text/css",
                                       "dcr" => "application/x-director",
                                       "dif" => "video/x-dv",
                                       "dir" => "application/x-director",
                                       "djv" => "image/vnd.djvu",
                                       "djvu" => "image/vnd.djvu",
                                       "dll" => "application/octet-stream",
                                       "dmg" => "application/octet-stream",
                                       "dms" => "application/octet-stream",
                                       "doc" => "application/msword",
                                       "docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                                       "dtd" => "application/xml-dtd",
                                       "dv" => "video/x-dv",
                                       "dvi" => "application/x-dvi",
                                       "dxr" => "application/x-director",
                                       "eml" => "message/rfc822",
                                       "epub" => "application/epub+zip",
                                       "eps" => "application/postscript",
                                       "etx" => "text/x-setext",
                                       "exe" => "application/octet-stream",
                                       "ez" => "application/andrew-inset",
                                       "gif" => "image/gif",
                                       "gram" => "application/srgs",
                                       "grxml" => "application/srgs+xml",
                                       "gtar" => "application/x-gtar",
                                       "hdf" => "application/x-hdf",
                                       "hqx" => "application/mac-binhex40",
                                       "htm" => "text/html",
                                       "html" => "text/html",
                                       "ice" => "x-conference/x-cooltalk",
                                       "ico" => "image/x-icon",
                                       "ics" => "text/calendar",
                                       "ief" => "image/ief",
                                       "ifb" => "text/calendar",
                                       "iges" => "model/iges",
                                       "igs" => "model/iges",
                                       "jnlp" => "application/x-java-jnlp-file",
                                       "jp2" => "image/jp2",
                                       "jpe" => "image/jpeg",
                                       "jpeg" => "image/jpeg",
                                       "jpg" => "image/jpeg",
                                       "js" => "application/x-javascript",
                                       "kar" => "audio/midi",
                                       "latex" => "application/x-latex",
                                       "lha" => "application/octet-stream",
                                       "lzh" => "application/octet-stream",
                                       "m3u" => "audio/x-mpegurl",
                                       "m4a" => "audio/mp4a-latm",
                                       "m4b" => "audio/mp4a-latm",
                                       "m4p" => "audio/mp4a-latm",
                                       "m4u" => "video/vnd.mpegurl",
                                       "m4v" => "video/x-m4v",
                                       "mac" => "image/x-macpaint",
                                       "man" => "application/x-troff-man",
                                       "mathml" => "application/mathml+xml",
                                       "me" => "application/x-troff-me",
                                       "mesh" => "model/mesh",
                                       "mid" => "audio/midi",
                                       "midi" => "audio/midi",
                                       "mif" => "application/vnd.mif",
                                       "mobi" => "application/octet-stream",
                                       "mov" => "video/quicktime",
                                       "movie" => "video/x-sgi-movie",
                                       "mp2" => "audio/mpeg",
                                       "mp3" => "audio/mpeg",
                                       "mp4" => "video/mp4",
                                       "mpe" => "video/mpeg",
                                       "mpeg" => "video/mpeg",
                                       "mpg" => "video/mpeg",
                                       "mpga" => "audio/mpeg",
                                       "ms" => "application/x-troff-ms",
                                       "msh" => "model/mesh",
                                       "mxu" => "video/vnd.mpegurl",
                                       "nc" => "application/x-netcdf",
                                       "oda" => "application/oda",
                                       "ogg" => "application/ogg",
                                       "pbm" => "image/x-portable-bitmap",
                                       "pct" => "image/pict",
                                       "pdb" => "chemical/x-pdb",
                                       "pdf" => "application/pdf",
                                       "pgm" => "image/x-portable-graymap",
                                       "pgn" => "application/x-chess-pgn",
                                       "pic" => "image/pict",
                                       "pict" => "image/pict",
                                       "png" => "image/png",
                                       "pnm" => "image/x-portable-anymap",
                                       "pnt" => "image/x-macpaint",
                                       "pntg" => "image/x-macpaint",
                                       "ppm" => "image/x-portable-pixmap",
                                       "ppt" => "application/vnd.ms-powerpoint",
                                       "prc" => "application/octet-stream",
                                       "ps" => "application/postscript",
                                       "qt" => "video/quicktime",
                                       "qti" => "image/x-quicktime",
                                       "qtif" => "image/x-quicktime",
                                       "ra" => "audio/x-pn-realaudio",
                                       "ram" => "audio/x-pn-realaudio",
                                       "ras" => "image/x-cmu-raster",
                                       "rdf" => "application/rdf+xml",
                                       "rgb" => "image/x-rgb",
                                       "rm" => "application/vnd.rn-realmedia",
                                       "roff" => "application/x-troff",
                                       "rtf" => "text/rtf",
                                       "rtx" => "text/richtext",
                                       "sgm" => "text/sgml",
                                       "sgml" => "text/sgml",
                                       "sh" => "application/x-sh",
                                       "shar" => "application/x-shar",
                                       "silo" => "model/mesh",
                                       "sit" => "application/x-stuffit",
                                       "skd" => "application/x-koan",
                                       "skm" => "application/x-koan",
                                       "skp" => "application/x-koan",
                                       "skt" => "application/x-koan",
                                       "smi" => "application/smil",
                                       "smil" => "application/smil",
                                       "snd" => "audio/basic",
                                       "so" => "application/octet-stream",
                                       "spl" => "application/x-futuresplash",
                                       "src" => "application/x-wais-source",
                                       "sv4cpio" => "application/x-sv4cpio",
                                       "sv4crc" => "application/x-sv4crc",
                                       "svg" => "image/svg+xml",
                                       "swf" => "application/x-shockwave-flash",
                                       "t" => "application/x-troff",
                                       "tar" => "application/x-tar",
                                       "tcl" => "application/x-tcl",
                                       "tex" => "application/x-tex",
                                       "texi" => "application/x-texinfo",
                                       "texinfo" => "application/x-texinfo",
                                       "tif" => "image/tiff",
                                       "tiff" => "image/tiff",
                                       "tr" => "application/x-troff",
                                       "tsv" => "text/tab-separated-values",
                                       "txt" => "text/plain",
                                       "ustar" => "application/x-ustar",
                                       "vcd" => "application/x-cdlink",
                                       "vrml" => "model/vrml",
                                       "vxml" => "application/voicexml+xml",
                                       "wav" => "audio/x-wav",
                                       "wbmp" => "image/vnd.wap.wbmp",
                                       "wbmxl" => "application/vnd.wap.wbxml",
                                       "wml" => "text/vnd.wap.wml",
                                       "wmlc" => "application/vnd.wap.wmlc",
                                       "wmls" => "text/vnd.wap.wmlscript",
                                       "wmlsc" => "application/vnd.wap.wmlscriptc",
                                       "wrl" => "model/vrml",
                                       "xbm" => "image/x-xbitmap",
                                       "xht" => "application/xhtml+xml",
                                       "xhtml" => "application/xhtml+xml",
                                       "xls" => "application/vnd.ms-excel",
                                       "xlsx" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                                       "xml" => "application/xml",
                                       "xpm" => "image/x-xpixmap",
                                       "xsl" => "application/xml",
                                       "xslt" => "application/xslt+xml",
                                       "xul" => "application/vnd.mozilla.xul+xml",
                                       "xwd" => "image/x-xwindowdump",
                                       "xyz" => "chemical/x-xyz",
                                       "zip" => "application/zip");

    private static $_config;
    private static $_debug = false;
    private static $_logPath = null;
    private static $_uploadDebug = false;
    private static $_uploadProgress = false;

    private $_baseUri;
    private $_basePath;
    private $_accessKey;
    private $_accessSecret;
    private $_userName;

    public static function configure($config) {
        if (!$config) return self::$_config;
        self::$_config = self::$_config ? array_merge(self::$_config, $config) : $config;
        self::$_debug = self::getArrayValue(self::$_config, 'debug');
        self::$_logPath = self::getArrayValue(self::$_config, 'logPath');
        self::$_uploadDebug = self::getArrayValue(self::$_config, 'uploadDebug');
        self::$_uploadProgress = self::getArrayValue(self::$_config, 'uploadProgress');
        return self::$_config;
    }

    public function __construct($config = null) {
        $config = self::configure($config);
        $servicesUrl = self::getArrayValue($config, 'url');
        $accessKey = self::getArrayValue($config, 'key');
        $accessSecret = self::getArrayValue($config, 'secret');

        if (!$servicesUrl || !$accessKey || !$accessSecret) {
            throw self::error("FCS Client Error: One or all of the following parameters are invalid: " .
                                  "servicesUrl, accessKey, accessSecret.");
        }

        $this->_baseUri = rtrim($servicesUrl, '/');
        $url = parse_url($this->_baseUri);
        $this->_basePath = $url['path'];
        self::debug("FCS Client: basePath=" . $this->_basePath);
        $this->_accessKey = $accessKey;
        $this->_accessSecret = $accessSecret;
        $this->_userName = "PHPSDK";
    }

    public function putProduct(array $product) {
        return $this->send("PUT", "products", "product", $product);
    }

    public function getProducts(array $filter) {
        $products = $this->send("POST", "products", "product-filter", $filter);
        return $products['product'];
    }

    public function getAssets(array $filter) {
        $assets = $this->send("POST", "assets", "asset-filter", $filter);
        if (!array_key_exists('asset', $assets)) return array();
        return $assets['asset'];
    }

    public function getAsset($assetId) {
        return $this->send("GET", "assets/" . $assetId, "asset", null);
    }

    public function uploadAsset(array $product, $assetPath, $assetType = null) {
        self::info("FCS Uploading $assetPath");
        $pathInfo = pathinfo($assetPath);
        $ext = strtolower($pathInfo['extension']);
        $fileName = $pathInfo['basename'];
        $contentType = self::$_mimeTypes[$ext];

        if ($assetType == null) {
          $assetType = self::$_assetTypes[$ext];
        }

        $asset = array('tag' => $product['tag'] . "-" . $assetType,
                       'status-tag' => 'CLD_AS_Pending',
                       'product-id' => $product['id'],
                       'asset-type-name' => $assetType,
                       'original-file-name' => $pathInfo['basename'],
                       'generated-file-name' => $pathInfo['basename'],
                       'content-type' => $contentType);

        $asset = $this->send("PUT", "assets", "asset", $asset);

        $this->sendFile("asset-files/" . $asset['id'], $assetPath, $fileName, $contentType);

        return $asset;
    }

    public function convertAsset(array $product, $sourceAssetId, $targetAssetType) {
        self::info("FCS Converting $sourceAssetId to $targetAssetType");
        $targetAsset = array('tag' => $product['tag'] . "-" . $targetAssetType,
                             'status-tag' => 'CLD_AS_Pending',
                             'product-id' => $product['id'],
                             'asset-type-name' => $targetAssetType,
                             'original-file-name' => 'UNKNOWN',
                             'generated-file-name' => 'UNKNOWN',
                             'content-type' => 'UNKNOWN');

        $targetAsset = $this->send("PUT", "assets", "asset", $targetAsset);

        $conversion = array('status-tag' => 'CLD_CS_Requested',
                            'source-id' => $sourceAssetId,
                            'target-id' => $targetAsset['id']);

        return $this->send("PUT", "conversions", "conversion", $conversion);
    }

    public function emailAsset(array $emailAssetRequest) {
        self::info("FCS Emailing Asset " . $emailAssetRequest['AssetId']);
        $this->send("POST", "email-asset", "email-asset-request", $emailAssetRequest, false);
    }

    public function getConversion($conversionId) {
        return $this->send("GET", "conversions/" . $conversionId, "conversion", null);
    }

    public function getConversions(array $filter) {
        $assets = $this->send("POST", "conversions", "conversion-filter", $filter);
        if (!array_key_exists('conversion', $assets)) return array();
        return $assets['conversion'];
    }

    // LCP APIs:

    public function lcpGetContentId($productId) {
        return $this->send("GET", "lcp/product/" . $productId . "/content", null, null, false);
    }
    public function lcpGetEncryptedContent($contentId) {
        return $this->send("GET", "lcp/content/" . $contentId, null, null, false);
    }
    public function lcpGetLicensedContent($user, $contentId, $licenseId) {
        // returns an EPUB file with the license.lcpl file embedded in it
        return $this->send("POST", "lcp/content/" . $contentId . "/" . $licenseId, "user", $user, false);
    }
    public function lcpGetLicense($user, $licenseId) {
        // returns the license.lcpl file
        // The file content is JSON, so you can download or parse it.
        return $this->send("POST", "lcp/license/" . $licenseId, "user", $user, false);
    }
    public function lcpGenerateLicense($user, $contentId) {
        return $this->send("POST", "lcp/license/" . $contentId . "/generate", "user", $user, false);
    }
    public function lcpGetLicenseStatus($licenseId) {
        return $this->send("GET", "lcp/license/" . $licenseId . "/status", null, null, false);
    }
    public function lcpRegisterDevice($licenseId, $deviceName) {
        return $this->send("POST", "lcp/license/" . $licenseId . "/register?deviceName=" . $deviceName, null, null, false);
    }
    public function lcpRenewLicense($licenseId, $deviceName) {
        return $this->send("PUT", "lcp/license/" . $licenseId . "/renew?deviceName=" . $deviceName, null, null, false);
    }
    public function lcpReturnLicense($licenseId, $deviceName) {
        return $this->send("PUT", "lcp/license/" . $licenseId . "/return?deviceName=" . $deviceName, null, null, false);
    }
    public function lcpHashPassphrase($pass) {
        $hash = hash_hmac("sha256", $pass, true);
        $encoded = base64_encode($hash);
        return $encoded;
    }

    public static function conversionIsApproved(array $conversion) {
        if ($conversion['status-tag'] == ConversionStatuses::Approved) {
            return true;
        }
        return false;
    }

    public static function conversionHasError(array $conversion) {
        if ($conversion['status-tag'] == ConversionStatuses::Error ||
            $conversion['status-tag'] == ConversionStatuses::Failed
        ) {
            return true;
        }
        return false;
    }

    public static function assetIsAvailable(array $asset) {
        if ($asset['status-tag'] == AssetStatuses::Uploaded ||
            $asset['status-tag'] == AssetStatuses::Approved
        ) {
            return true;
        }
        return false;
    }

    public function getUserLibraryUri($site, $email) {
        return $this->send("GET", "user-library-uri?site=".$site."&email=".$email, null, null, false);
    }

    public function getAssetUriById($assetId, $price = "", $user = "") {
        return $this->send("GET", "asset-uris/" . $assetId . "?price=" . $price . "&user=" . $user, null, null, false);
    }

    public function getAssetUriByEan($ean, $type, $price = "", $user = "") {
        return $this->send("GET", "asset-uris?ean=" . $ean . "&type=" . $type . "&price=" . $price . "&user=" . $user, null, null, false);
    }

    public function getAssetTypesByEan($ean) {
        return $this->send("GET", "asset-types?ean=" . $ean, null, null, true);
    }

    private function send($method, $uri, $root, $obj, $returnsXml = true) {
        $xml = '';
        if ($obj) {
            if ($method == 'PUT') {
                $id = 'new';
                if (array_key_exists('id', $obj)) $id = $obj['id'];
                $uri .= '/' . $id;
            }
            $this->reKey($obj);
            $xml = self::arrayToXMLString($obj, $root, $root == 'asset-filter' ? self::MODEL_NAMESPACE : self::CLD_NAMESPACE);
        }

        $uriParts = explode('?', $uri);
        $client = new Client(array('base_uri' => rtrim($this->_baseUri, '/') . '/'));

        $req = null;
        $authorization = $this->getAuthorize($method, $uriParts[0]);
        if ($obj) {
            $req = new Request(
                $method,
                new Uri($uri),
                array('Authorization' => $authorization,
                      "Content-Type" => "application/xml; charset=utf-8"),
                $xml
            );
        }
        else {
            $req = new Request(
                $method,
                new Uri($uri),
                array('Authorization' => $authorization)
            );
        }

        self::debug("FCS Sending Request: " . (string)$req->getUri() . self::NEWLINE . $xml);
        /** @var Response $resp */
        try {
            $resp = $client->send($req);
        }
        catch (BadResponseException $e) {
            throw self::error("FCS Send Error: $e");
        }

        $responseCode = $resp->getStatusCode();
        $responseBody = $resp->getBody();
        self::debug("FCS Received Response: [$responseCode] $responseBody");

        if ($responseCode != 200) {
            throw self::error("FCS Send Error: [$responseCode] $responseBody)");
        }

        $data = $responseBody;
        if ($returnsXml) {
            $dom = new DOMDocument();
            if (!$dom->loadXML($responseBody)) {
                throw self::error('FCS Client Error: failed to load response');
            }
            $data = self::domDocumentToArray($dom);
            $root = $dom->documentElement;

            if ($root->tagName == "error") {
                throw self::error('FCS Client Error: [' . $data['code'] . '] ' . $data['message']);
            }
        }
        return $data;
    }

    private function sendFile($uri, $path, $fileName, $contentType) {
        self::debug("FCS Sending File $path to $uri");
        $fileName = urlencode($fileName);
        $size = filesize($path);
        if ($size <= 0) {
            throw self::error("FCS Send Error: $path is empty");
        }
        $chunks = ceil($size / self::CHUNK_SIZE);
        $lastChunkSize = $size % self::CHUNK_SIZE;
        $bytesSent = 0;
        for ($chunk = 0; $chunk < $chunks; $chunk++) {
            $isLastChunk = ($chunk == ($chunks - 1));
            $chunkSize = $isLastChunk ? $lastChunkSize : self::CHUNK_SIZE;
            $chunkQuery = "name=$fileName&chunk=$chunk&chunks=$chunks";
            self::debug("Sending Chunk isLastChunk=$isLastChunk, lastChunkSize=$lastChunkSize, chunkSize=$chunkSize, chunk=$chunk, chunks=$chunks");
            $this->sendChunk($uri, $chunkQuery, $path, $contentType, $bytesSent, $chunkSize);
            $bytesSent += $chunkSize;
        }
    }

    private function sendChunk($uri, $query, $filePath, $contentType, $pos, $size) {
        if ($size <= 0) {
            throw self::error("FCS Send Error: chunk is empty for $filePath");
        }
        $fullUri = rtrim($this->_baseUri, '/') . '/' . $uri . '?' . $query;
        $fp = fopen($filePath, "rb");
        if (!$fp) {
            throw self::error("FCS Send Error: failed to open $filePath");
        }
        $stream = new FcsStream($fp, $size, $pos);

        $httpDate = gmdate("D, d M Y G:i:s T");
        $http = curl_init();
        curl_setopt($http, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($http, CURLOPT_LOW_SPEED_LIMIT, 1024);
        curl_setopt($http, CURLOPT_LOW_SPEED_TIME, 120);
        curl_setopt($http, CURLOPT_READFUNCTION, array($stream, "read"));
        curl_setopt($http, CURLOPT_URL, $fullUri);
        curl_setopt($http, CURLOPT_UPLOAD, true);
        curl_setopt($http, CURLOPT_PUT, true);
        curl_setopt($http, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($http, CURLOPT_INFILESIZE, $size);
        curl_setopt($http, CURLOPT_NOPROGRESS, !self::showUploadProgress());
        curl_setopt($http, CURLOPT_NOSIGNAL, true);
        curl_setopt($http, CURLOPT_VERBOSE, self::isUploadDebugging());

        $header[] = "Date: $httpDate";
        $header[] = "Content-Type: $contentType";
        $header[] = "Content-Length: $size";
        $header[] = "Authorization: " . $this->getAuthorize("PUT", $uri);

        curl_setopt($http, CURLOPT_HTTPHEADER, $header);
        curl_setopt($http, CURLOPT_RETURNTRANSFER, true);

        self::debug("FCS Sending File Chunk $fullUri pos=$pos, size=$size");
        $result = curl_exec($http);
        if (!$result) {
            $err = curl_error($http);
            fclose($fp);
            throw self::error("FCS Send Error: $err");
        }

        $responseCode = curl_getinfo($http, CURLINFO_HTTP_CODE);
        if (!fclose($fp)) {
            throw self::error("FCS Send Error: failed to close file $filePath");
        }
        curl_close($http);
        if ($responseCode != 200) {
            throw self::error("FCS Send Error: [$responseCode] $result)");
        }
        $rtnObj = json_decode($result);
        if (!is_object($rtnObj)) throw self::error("FCS Send Error: NULL no return object");
        if ($rtnObj->Status != 200) {
            throw self::error("FCS Send Error: [$rtnObj->Status] $rtnObj->Message");
        }
    }

    private function objectCompare($a, $b) {
        if ($a == 'id' && $b != 'id') return -1;
        if ($a != 'id' && $b == 'id') return 1;
        if ($a == 'tag' && $b != 'tag') return -1;
        if ($a != 'tag' && $b == 'tag') return 1;
        if ($a == 'status-id' && $b != 'status-id') return -1;
        if ($a != 'status-id' && $b == 'status-id') return 1;
        if ($a == 'status-tag' && $b != 'status-tag') return -1;
        if ($a != 'status-tag' && $b == 'status-tag') return 1;
        return strcmp($a, $b);
    }

    private function reKey(&$in) {
        uksort($in, array($this, "objectCompare"));
        foreach ($in as &$item) {
            if (is_array($item) && !empty($item)) {
                self::reKey($item);
            }
        }
    }

    private function getAuthorize($method, $uri) {
        $content = $this->_basePath
            ? $method . $this->_basePath . '/' . $uri
            : $method . '/' . $uri;

        self::debug($content);
        $signature = base64_encode(hash_hmac("SHA1", $content, $this->_accessSecret, true));
        return 'FBT ' . $this->_accessKey . ':' . $signature . ':' . $this->_userName;
    }

    private static function arrayToDOMDocument(array $source, $rootTagName = 'root', $rootNamespace = self::CLD_NAMESPACE) {
        $document = new DOMDocument();
        $document->appendChild(self::createDOMElement($source, $rootTagName, $document, $rootNamespace));

        //unset($source);
        return $document;
    }

    private static function arrayToXMLString(array $source, $rootTagName = 'root', $rootNamespace = self::CLD_NAMESPACE, $formatOutput = true) {
        $document = self::arrayToDOMDocument($source, $rootTagName, $rootNamespace);
        $document->formatOutput = $formatOutput;

        return $document->saveXML();
    }

    private static function domDocumentToArray(DOMDocument $document) {
        return self::createArray($document->documentElement);
    }

    private static function xmlStringToArray($xmlString) {
        $document = new DOMDocument();

        return $document->loadXML($xmlString) ? self::domDocumentToArray($document) : array();
    }

    private static function xmlEntities($string) {
        return str_replace(array("&", "<", ">", "\"", "'"),
                           array("&amp;", "&lt;", "&gt;", "&quot;", "&apos;"), $string);
    }

    private static function createDOMElement($source, $tagName, DOMDocument $document, $namespace = false) {
        if (!is_array($source)) {
            $element = $document->createElement($tagName, self::xmlEntities($source));
            return $element;
        }

        $element = $namespace
            ? $document->createElementNS($namespace, $tagName)
            : $document->createElement($tagName);

        foreach ($source as $key => $value) {
            if (is_string($key) && !is_numeric($key)) {
                if ($key === self::ATTRIBUTES) {
                    foreach ($value as $attributeName => $attributeValue) {
                        $element->setAttribute($attributeName, $attributeValue);
                    }
                }
                elseif ($key === self::CONTENT) {
                    $element->appendChild($document->createCDATASection($value));
                }
                elseif (is_string($value) && !is_numeric($value)) {
                    $element->appendChild(self::createDOMElement($value, $key, $document));
                }
                elseif (is_array($value) && count($value)) {
                    $keyNode = $document->createElement($key);

                    foreach ($value as $elementKey => $elementValue) {
                        if (is_string($elementKey) && !is_numeric($elementKey)) {
                            $keyNode->appendChild(self::createDOMElement($elementValue, $elementKey, $document));
                        }
                        else {
                            $element->appendChild(self::createDOMElement($elementValue, $key, $document));
                        }
                    }

                    if ($keyNode->hasChildNodes()) {
                        $element->appendChild($keyNode);
                    }
                }
                else {
                    if (is_bool($value)) {
                        $value = $value ? 'true' : 'false';
                    }

                    $element->appendChild(self::createDOMElement($value, $key, $document));
                }
            }
            else {
                $element->appendChild(self::createDOMElement($value, $tagName, $document));
            }
        }

        return $element;
    }

    private static function createArray(DOMNode $domNode) {
        $array = array();

        for ($i = 0; $i < $domNode->childNodes->length; $i++) {
            $item = $domNode->childNodes->item($i);

            if ($item->nodeType === XML_ELEMENT_NODE) {
                $arrayElement = array();

                for ($attributeIndex = 0; !is_null($attribute = $item->attributes->item($attributeIndex)); $attributeIndex++) {
                    if ($attribute->nodeType === XML_ATTRIBUTE_NODE) {
                        $arrayElement[self::ATTRIBUTES][$attribute->nodeName] = $attribute->nodeValue;
                    }
                }

                $children = self::createArray($item);

                if (is_array($children)) {
                    $arrayElement = array_merge($arrayElement, $children);
                    $array[$item->nodeName][] = $arrayElement;
                }
                else {
                    $array[$item->nodeName] = $children;
                }

            }
            elseif ($item->nodeType === XML_CDATA_SECTION_NODE || ($item->nodeType === XML_TEXT_NODE && trim($item->nodeValue) !== '')) {
                return $item->nodeValue;
            }
        }

        return $array;
    }

    public static function error($msg) {
        self::log("error", $msg);
        return new ErrorException($msg);
    }

    public static function info($msg) {
        self::log("info", $msg);
    }

    public static function debug($msg) {
        self::log("debug", $msg);
    }

    private static function log($level, $msg) {
        if (class_exists('sfContext')) {
            $logger = sfContext::getInstance()->getLogger();
            if ($level == 'error') {
                $logger->err($msg);
            }
            else {
                if ($level == 'info') {
                    $logger->info($msg);
                }
                else if ($level == 'debug') $logger->debug($msg);
            }
        }
        else {
            self::write("[" . strtoupper($level) . "] " . $msg);
        }
    }

    private static function write($msg) {
        if (self::isDebugging()) {
            date_default_timezone_set("America/New_York");
            $dt = date("c");
            $formattedMsg = sprintf("[%s] %s%s", $dt, $msg, self :: NEWLINE);
            if (self::debugFilePath()) {
                self::writeToFile($formattedMsg);
            }
        }
    }

    private static function writeToFile($msg) {
        $logPath = self::debugFilePath();
        if (!$logPath) return;
        if (!file_exists($logPath)) {
            if (!file_put_contents($logPath, $msg)) {
                throw new ErrorException("Error writing to $logPath");
            }
        }
        else {
            if (!file_put_contents($logPath, $msg, FILE_APPEND)) {
                throw new ErrorException("Error writing to $logPath");
            }
        }
    }

    private static function isDebugging() {
        if (self::$_debug) {
            return true;
        }

        return false;
    }

    private static function isUploadDebugging() {
        if (self::$_uploadDebug) {
            return true;
        }
        return false;
    }

    private static function showUploadProgress() {
        if (self::$_uploadProgress) {
            return true;
        }
        return false;
    }

    private static function debugFilePath() {
        if (self::$_logPath) {
            return self::$_logPath;
        }
        return false;
    }

    private static function getArrayValue(array $array, $key) {
        if (!array_key_exists($key, $array)) return null;
        return $array[$key];
    }
}

class FcsStream {
    private $_stream;
    private $_bytesRead;
    private $_seekPosition;
    private $_size;

    public function __construct($stream, $size, $pos) {
        $this->_stream = $stream;
        $this->_size = $size;
        $this->_bytesRead = 0;
        $this->_seekPosition = $pos;
    }

    public function read($curl_handle, $file_handle, $length) {
        // Once we've sent as much as we're supposed to send...
        if ($this->_bytesRead >= $this->_size) {
            // Send EOF
            return '';
        }

        // If we're at the beginning of an upload and need to seek...
        if ($this->_bytesRead == 0 && isset($this->_seekPosition) && $this->_seekPosition !== ftell($this->_stream)) {
            if (fseek($this->_stream, $this->_seekPosition) !== 0) {
                throw new ErrorException('The stream does not support seeking and is either not at the requested position or the position is unknown.');
            }
        }

        $read = fread($this->_stream, min($this->_size - $this->_bytesRead, $length)); // Remaining upload data or cURL's requested chunk size
        $this->_bytesRead += strlen($read);

        $out = $read === false ? '' : $read;

        return $out;
    }
}