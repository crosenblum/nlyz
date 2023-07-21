<?php

// ajax check for completed results

// set unlimited execution time
ini_set('max_execution_time', '0');

// start session
session_start();

// check for csrf token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// check csrf header
header('Content-Type: application/json');

// check http origin
// if (isset($_SERVER['HTTP_ORIGIN'])) {
    // $address = 'http://' . $_SERVER['SERVER_NAME'];
    // if (strpos($address, $_SERVER['HTTP_ORIGIN']) !== 0) {
        // exit(json_encode([
            // 'error' => 'Invalid Origin header: ' . $_SERVER['HTTP_ORIGIN']
        // ]));
    // }
// } else {
    // exit(json_encode(['error' => 'No Origin header']));
// }

// require the main nlyz class
require_once(dirname(__FILE__) . '/classes/class.nlyz.php');

// assign object for class nlyz
$nlyz = new nlyz();

// set results
$perc = 0;

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

	// get the results
	$results = $nlyz->check_request($storename, $reporttype);
	
	// set results to variables
	$links = $results[0];
	$completed = $results[1];
	$items = $results[2];
	
    // if links ggreater than zero
    if ($links > 0) {
        
        // calculate completion percentage
        $perc = ($items / $links) * 100;
        
    } else {
        
        // set to zero
        $perc = 0;
        
    }

}

// return percentage
echo json_encode($perc);

?>