<?php

// test get headers

// setup variables
$storename = 'asdasd123123123';
$headers = [];

// require the main nlyz class
require_once(dirname(__FILE__) . '/classes/class.nlyz.php');

// assign object for class nlyz
$nlyz = new nlyz();

// get headers
$headers = $nlyz->valid_store($storename);

echo $headers;

?>