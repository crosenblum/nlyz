<?php

// test store request

// setup variables
$storename = 'cashinwhi';
$headers = [];
$return = '';
$reporttype = 1;
$valid = 0;
$status = '';


// require the main nlyz class
require_once(dirname(__FILE__) . '/classes/class.nlyz.php');

// assign object for class nlyz
$nlyz = new nlyz();


// validdate store
$valid = $nlyz->valid_store($storename);

// check if valid
if ($valid == 1) {

	// check length
	if (strlen($storename) > 6 && strlen($storename) < 65) {
		
		// now send the request
		$status = $nlyz->send_request($storename, $reporttype);
		
		// create return message
		$return = 'Valid request for store ['.$storename.'] and report type ['.$reporttype.'] have been received! Status is ['.$status.']';
	
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

echo $return;

?>