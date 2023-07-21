<?php

// que processor

// require the main nlyz class
require_once(dirname(__FILE__) . '/classes/class.nlyz.php');

// assign object for class nlyz
$nlyz = new nlyz();
$results = [];

// get one request and process it
$nlyz->complete_request();

?>