<?php

// test check results


// setup variables
$storename = 'cashinwhi';
$reporttype = 1;
$results = [];
$return = '';

// require the main nlyz class
require_once(dirname(__FILE__) . '/classes/class.nlyz.php');

// assign object for class nlyz
$nlyz = new nlyz();

// get the results
$results = $nlyz->check_request($storename, $reporttype);

// set results to variables
$links = $results[0];
$completed = $results[1];
$items = $results[2];

echo 'Links: ['.$links.']<br/>';
echo 'Items: ['.$items.']<br/>';
echo 'Completed: ['.$completed.']<br/>';

?>