<?php

// test bed for testing different functions

// load composer autoloader
require('C:\Users\crosenblum\vendor\autoload.php');

// enable tracy debugger
use Tracy\Debugger;
Debugger::enable();

// require the main nlyz class
require_once(dirname(__FILE__) . '/classes/class.nlyz.php');

// assign object for class nlyz
$nlyz = new nlyz();

// setup arrays and variables
$results = [];
$storename = 'cashinwhi';

// get results from function
$results = $nlyz->getlinks($storename);

// loop thru links
foreach ($results as $key => $value) {

	// start timer
	$start = microtime(true);

	// get link detauls
	$details = [];
	$images = [];
	$details = $nlyz->getlinkdetails($value);
	
	// get specific values
	$title = $details[0];
	$price = $details[1];
	$cond = $details[2];
	$images = $details[3];
	$image = $images[0];
	
	// create thumbnail reference
	$thumb = str_replace('s-l1600.jpg','s-l64.jpg',$images);
	
	// display results
	echo 'Listing #: '.$key.'<br/>';
	echo ' - URL: [<a href="'.$value.'" target="_new">'.$value.'</a>]<br/>';
	echo ' - Title: ['.$title.']<br/>';
	echo ' - Price: ['.$price.']<br/>';
	echo ' - Condition: ['.$cond.']<br/>';
	echo ' - Cover Photo: [<a href="'.$images.'" target="_new">'.$images.'</a>]<br/>';
	echo ' - ThumbNail : [<a href="'.$thumb.'" target="_new">'.$thumb.'</a>]<br/>';
	echo '<br/>';
	
	// end time
	$end = microtime(true);

	// time calculations
	$time = number_format(($end - $start), 2);

	// show results
	echo 'This page loaded in ', $time, ' seconds<br/><br/>';
	
	flush();
    ob_flush();
    sleep(1);

}
?>