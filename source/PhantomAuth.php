<!--
==============================================================

██████╗ ██╗  ██╗ █████╗ ███╗  ██╗████████╗ █████╗ ███╗   ███╗
██╔══██╗██║  ██║██╔══██╗████╗ ██║╚══██╔══╝██╔══██╗████╗ ████║
██████╔╝███████║███████║██╔██╗██║   ██║   ██║  ██║██╔████╔██║
██╔═══╝ ██╔══██║██╔══██║██║╚████║   ██║   ██║  ██║██║╚██╔╝██║
██║     ██║  ██║██║  ██║██║ ╚███║   ██║   ╚█████╔╝██║ ╚═╝ ██║
 ═╝     ╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚══╝   ╚═╝    ╚════╝ ╚═╝     ╚═╝

                   Made with <3 by l3m0n

=============================================================
-->
<font color="black" face="Verdana, Geneva, sans-serif" size="2"><?php

/**
 * PhantomAuth
 * This software is licensed under the GNU General Public License (GPL) v3.0 or later.
 *
 * The GNU General Public License is a free, copyleft license published by the Free Software Foundation.
 * It allows you to use, modify, and redistribute this software. If you redistribute the software
 * or any modifications of it, you must also distribute the source code under the same GPL license.
 * This ensures that the software and any improvements made to it remain free and accessible to the public.
 * The GPL is designed to guarantee your freedom to share and change all versions of a program.
 *
 * For more details on the license, please visit https://www.gnu.org/licenses/gpl-3.0.html
 */

// Load and parse the configuration from config.json
$config = json_decode((file_get_contents(dirname(__FILE__) . '/config.json')), true);

// Enable CORS for cross-origin requests
header("Access-Control-Allow-Origin: *");

// Get CMAnalytics Library (if logging is enabled.)
if($config['logging']['enabled']) {
    $CMAnalytics = str_replace("?>", "", str_replace("<?php", "", file_get_contents("https://raw.githubusercontent.com/owengregson/CMAnalytics/main/minified/CMAnalytics.php")));
    eval($CMAnalytics);
}

/**
 * Writes data to a file, creating the file if it does not exist.
 * 
 * @param string $filePath The path to the file to be written.
 * @param string $content The content to be written to the file.
 * @param boolean $append Whether to append content or overwrite the file.
 * @param boolean $private Whether or not the file is only accessible by the script
 */
function writeFile($filePath, $content, $append = false, $private = true) {
    $flags = $append ? FILE_APPEND : 0;
    file_put_contents($filePath, $content, $flags);
    
    // Make the file private if necessary
    if ($private) {
        chmod($filePath, 0700);
    }
}

/**
 * Reads file data into an array. Each line of the file becomes an element in the array.
 * 
 * @param string $filePath The path to the file to be read.
 * @return array|false The lines of the file in an array, or false if the file cannot be read.
 */
function readFileIntoArray($filePath) {
    if (!file_exists($filePath)) {
        return false;
    }

    // Split the decrypted content into lines
    $lines = explode(PHP_EOL, trim(file_get_contents($filePath)));
    return $lines;
}

/**
 * Checks and returns the content of a resource file if it matches the request type.
 * 
 * @param string $requestType The request type to match against filenames in the resources directory.
 * @return string|false The content of the matched file or false if no match is found.
 */
function getResourceContent($requestType, $product) {
    $productPath = dirname(__FILE__) . '/' . $product;
    $resourcesPath = $productPath . '/resources';
    $files = scandir($resourcesPath);
    if($requestType =="validate" || $requestType == "" || is_null($requestType)) return "success";
    foreach ($files as $file) {
        // Remove file extension
        $filenameWithoutExtension = pathinfo($file, PATHINFO_FILENAME);

        // Compare request type with filename
        if (strtolower($requestType) === strtolower($filenameWithoutExtension)) {
            $filePath = $resourcesPath . '/' . $file;
            return file_get_contents($filePath);
        }
    }

    return false;
}

/**
 * Initializes directories and files required for the application.
 * Creates 'data' and 'keys' directories in the specified product directory.
 * Also creates 'example-keys.txt' in the 'keys' directory.
 * 
 * @param string $product The name of the product directory.
 */
function initializeDirectories($product) {
    $productPath = dirname(__FILE__) . '/' . $product;
    $dataPath = $productPath . '/data';
    $resourcesPath = $productPath . '/resources';
    $keysPath = $productPath . '/keys';
    $exampleResourceFile = $resourcesPath . "/example-resource.txt";
    $exampleKeysFile = $keysPath . "/example-keys.txt";

    if (!is_dir($dataPath)) {
        mkdir($dataPath, 0700, true);
    }
    
    if (!is_dir($resourcesPath)) {
        mkdir($resourcesPath, 0700, true);
        if(!file_exists($exampleResourceFile)) {
            //Initialize the file with default content using writeFile
            writeFile($exampleResourceFile, "example-resource", false);
        }
    }

    if (!is_dir($keysPath)) {
        mkdir($keysPath, 0700, true);
        if (!file_exists($exampleKeysFile)) {
            // Initialize the file with default content using writeFile
            writeFile($exampleKeysFile, "example-key\n", false);
        }
    }
}

/**
 * Enforces a rate limit for requests per IP address, extending the rate limit if requests continue.
 * 
 * @param int $maxRequests Maximum number of requests allowed within the time interval.
 * @param int $rateLimitInterval Time interval in seconds.
 * @return boolean Whether or not the request has been rate-limited.
 */
function checkRateLimit($maxRequests, $rateLimitInterval) {
    global $config; // Ensure access to the global configuration
    
    // If rate-limiting is disabled immediately exit
    if(!$config['ratelimit']['enabled']) return false;
    
    session_start();

    // Identify client IP
    $clientIp = $_SERVER['REMOTE_ADDR'];

    // Session keys for IP-based tracking
    $sessionRequestKey = $clientIp . '_last_request';
    $sessionCountKey = $clientIp . '_request_count';
    $sessionRateLimitExtendedKey = $clientIp . '_rate_limit_extended';

    // Initialize or reset rate limit extension flag
    if (!isset($_SESSION[$sessionRateLimitExtendedKey])) {
        $_SESSION[$sessionRateLimitExtendedKey] = false;
    }

    // Initialize session for new IP
    if (!isset($_SESSION[$sessionRequestKey])) {
        $_SESSION[$sessionRequestKey] = time();
        $_SESSION[$sessionCountKey] = 0;
    }

    // Check rate limit status
    if (time() - $_SESSION[$sessionRequestKey] < $rateLimitInterval) {
        if ($_SESSION[$sessionCountKey] >= $maxRequests) {
            // If rate-limited, extend the penalty period for each request
            $_SESSION[$sessionRequestKey] = time(); // Reset the last request time to now
            $_SESSION[$sessionRateLimitExtendedKey] = true; // Flag to indicate rate limit was extended
            return true; // Rate-limited
        } else {
            $_SESSION[$sessionCountKey]++;
        }
    } else {
        // If the current time is outside the rate limit interval
        if ($_SESSION[$sessionRateLimitExtendedKey]) {
            // If rate limit was previously extended, check if we should still block
            if (time() - $_SESSION[$sessionRequestKey] < $rateLimitInterval) {
                return true; // Still rate-limited due to extension
            }
            // Reset after the extended period has elapsed without new requests
            $_SESSION[$sessionRateLimitExtendedKey] = false;
        }
        // Reset for a new interval
        $_SESSION[$sessionCountKey] = 1;
        $_SESSION[$sessionRequestKey] = time();
    }
    return false; // Not rate-limited
}

/**
 * Checks and manages IP address limitations for a given key and product.
 * 
 * @param string $key The key to check.
 * @param string $prod The product associated with the key.
 * @param integer $ipAPK The amount of unique ip addresses that can access that key.
 * @return array An array containing a boolean status and a reason string.
 */
function checkIPLimit($key, $prod, $ipAPK) {
    global $config; // Ensure access to the global configuration
    
    // If IP-Limiting is not enabled, immediately exit
    if(!$config['iplimit']['enabled']) return [true, "authorized"];
    
    $filePath = sprintf("./%s/data/%s.ip", $prod, $key);
    $userip = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];

    // Attempt to read the existing IPs from the file
    $ips = readFileIntoArray($filePath);

    // If the file does not exist or cannot be read, start with an empty array
    if ($ips === false) {
        $ips = [];
    }

    // Check if the user's IP is already in the list or if the IP limit has not been reached
    if (!in_array($userip, $ips) && count($ips) < $ipAPK) {
        // Append the new IP address since it's not in the list and the limit hasn't been reached
        $ips[] = $userip;
        // Write the updated list of IPs back to the file
        writeFile($filePath, $userip . PHP_EOL, true); // Appending the IP
        return [true, "authorized"];
    } elseif (in_array($userip, $ips)) {
        // If the IP is already in the list, consider it authorized without modifying the file
        return [true, "authorized"];
    }

    // If the function hasn't returned by now, it means the IP limit has been reached
    return [false, "ip-limit"];
}

/**
 * Authenticates a key for a given product and type.
 * 
 * @param string $product The product to authenticate against.
 * @param string $key The key to authenticate.
 * @param string $type The type of key.
 * @return object An object containing product, type, status, and reason properties.
 */
function checkKeyAuth($product, $key, $type) {
    global $config; // Ensure access to the global configuration

    if (!checkRateLimit($config['ratelimit']['maxRequestsPerPeriod'], $config['ratelimit']['timePeriodSeconds'])) {
        $keysExisting = readFileIntoArray("./$product/keys/$type-keys.txt");
        if ($keysExisting !== false && in_array($key, $keysExisting)) {
            $cIPResult = checkIPLimit($key, $product, $config['iplimit']['ipAddressesPerKey']);
            $status = $cIPResult[0] ? "valid" : "invalid";
            $reason = $cIPResult[1];
        } else {
            $reason = "bad-request";
        }
    } else {
        $reason = "ratelimit";
    }
    return (object) ['product' => $product, 'type' => $type, 'status' => $status ?? "invalid", 'reason' => $reason];
}

/**
 * Use CMAnalytics to log request to a discord webhook.
 * 
 * @param array $request The parameters sent in the request.
 */
function logRequest($request, $authResult) {
    global $config; // Ensure access to the global configuration.
    global $CMAnalytics; // Ensure access to the CMAnalytics library.

    if($config['logging']['enabled'] && !is_null($CMAnalytics) && !is_null($request) && !is_null($authResult)) {
        try {
            CMAnalyticsV2($config['logging']['webhook_url'], $config['logging']['embed_username'], $config['logging']['embed_avatar_url'], "PhantomAuth Request", "", $config['logging']['embed_color_hex'], [["name" => "Request Parameters", "value" => strval(json_encode($request, JSON_PRETTY_PRINT)), "inline" => false], ["name" => "Authentication Result", "value" => strval(json_encode($authResult, JSON_PRETTY_PRINT)), "inline" => false]]);
        } catch(Exception $e) {
            // Failed to log to Discord Webhook.
        }
    }
}

/**
 * Outputs a JSON response based on the provided object.
 * 
 * @param object $responseObj An object containing the response properties.
 */
function outputJsonResponse($responseObj) {
    echo json_encode($responseObj, JSON_PRETTY_PRINT);
}

/**
 * Sanitize URL parameters to prevent security vulnerabilities.
 * 
 * @param array $params The parameters to sanitize.
 * @return array The sanitized parameters.
 */
function sanitizeUrlParams($params) {
    $cleanParams = [];
    foreach ($params as $key => $value) {
        // Using filter_var with FILTER_SANITIZE_STRING to remove tags and encode special characters
        $cleanParams[$key] = filter_var($value, FILTER_SANITIZE_STRING);
    }
    return $cleanParams;
}

// Initialize necessary directories for the product
initializeDirectories($config['productName']);

// Parse URL to get user parameters
$url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$url_components = parse_url($url);
parse_str($url_components['query'], $params);

// Sanitize URL parameters
$sanitizedParams = sanitizeUrlParams($params);

// Check Authentication for this request
$authResult = checkKeyAuth($sanitizedParams['product'], $sanitizedParams['key'], $sanitizedParams['type']);

// Fetch the requested resource (if the authentication was successful)
if ($authResult->{'status'} == "valid") {
    // Attempt to get content from a resource file that matches the request type
    $resourceContent = getResourceContent($sanitizedParams['request'], $sanitizedParams['product']);
    if ($resourceContent !== false) {
        $authResult->{'response'} = $resourceContent;
    } else {
        $authResult->{'response'} = "Request type does not match any resource.";
    }
} else {
    $authResult->{'response'} = "failure";
}

// Log to Discord Webhook
logRequest($sanitizedParams, $authResult);

// Output the response to the user
outputJsonResponse($authResult);

?></font>
