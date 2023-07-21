<?php

// ajax process

// https://stackoverflow.com/questions/37912937/how-to-send-secure-ajax-requests-with-php-and-jquery

// start session
session_start();

// check for csrf token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// check csrf header
header('Content-Type: application/json');

// check http origin
if (isset($_SERVER['HTTP_ORIGIN'])) {
    $address = 'http://' . $_SERVER['SERVER_NAME'];
    if (strpos($address, $_SERVER['HTTP_ORIGIN']) !== 0) {
        exit(json_encode([
            'error' => 'Invalid Origin header: ' . $_SERVER['HTTP_ORIGIN']
        ]));
    }
} else {
    exit(json_encode(['error' => 'No Origin header']));
}

// require the main nlyz class
require_once(dirname(__FILE__) . '/classes/class.nlyz.php');

// assign object for class nlyz
$nlyz = new nlyz();

// setup variables
$return = '';
$storename = '';
$reporttype = 0;
$valid = 0;
$status = '';

// get the storename
if (isset($_POST['storename']) && isset($_POST['reporttype'])) {
    
    // set report type
    $reporttype = $_POST['reporttype'];
    // $reporttype = preg_replace('/[^0-9]/', '', $reporttype);
    
    // set storename
    // $storename = json_decode($_POST['storename']);
    $storename = $_POST['storename'];
    
    // let us sanitize
    // $storename = $nlyz->sanitize($storename);
    $reportype = $nlyz->sanitize($reporttype);
    
    // validdate store
    $valid = $nlyz->valid_store($storename);
    
    // check if valid
    if ($valid == 1) {
    
        // check length
        if (strlen($storename) > 6 && strlen($storename) < 65) {
            
            // now send the request
            $status = $nlyz->send_request($storename, $reporttype);
            
            // create return message
            $return = 'Valid request for store ['.$storename.'] and report type ['.$reporttype.'] have been received!';
        
        } elseif (strlen($storename) < 6) {
            
            // send invalid store name length
            $return = 'The store name ['.$storename.'] length ['.strlen($storename).'] is too short , please try again!';
            
        } elseif (strlen($storename) > 63) {
            
            // send invalid store name length
            $return = 'The store name ['.$storename.'] length ['.strlen($storename).'] is too long , please try again!';
            
        }
        
    } else {
        
        // invalid store
        $return = 'Invalid store, please enter correct eBay store name!';
        
    }
}

// send back 
echo json_encode($return);

?>