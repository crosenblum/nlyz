<?php

// ajax json output program

// use content header to indicate json
header('Content-Type: application/json; charset=utf-8');

// load composer autoloader
require('C:\Users\crosenblum\vendor\autoload.php');

// enable guzzle
use GuzzleHttp\Client;
use GuzzleHttp\Promise;

// setup guzzle client
$client = new GuzzleHttp\Client(['base_uri' => 'https://www.ebay.com']);

// require the main nlyz class
require_once(dirname(__FILE__) . '/classes/class.nlyz.php');

// assign object for class nlyz
$nlyz = new nlyz();

// setup arrays and variables
$results = [];
$count = 0;
$storename = 'cashinwhi';

// get results from function
$results = $nlyz->get_all_links($storename);

// setup array
$data = [];

// use this to pool all guzzle http requests together
$requestGenerator = function($results) use ($client) {
	foreach ($results as $key => $value) {
		// The magic happens here, with yield key => value
		yield $value => function() use ($client, $value) {
			// Our identifier does not have to be included in the request URI or headers
			return $client->getAsync($value);
		};
	}
};

$pool = new GuzzleHttp\Pool($client, $requestGenerator($results), [
	'concurrency' => 3,
	'fulfilled' => function(GuzzleHttp\Psr7\Response $response, $index) use (&$data) {
		// This callback is delivered each successful response

		// If these values don't match, something is very wrong
		// echo "Requested eBay Listing: [", $index, "]<br/>";
		
		// get the body
		$body = $response->getBody();
		
		// now do my normal parsing

		// assign object for class nlyz
		$nlyz = new nlyz();

		// get link detauls
		$details = [];
		$images = [];
		
		// get the details for this url
		$details = $nlyz->get_item_details($body);

		// get specific values
		$title = $details[0];
		$price = $details[1];
		$cond = $details[2];
		$images = $details[3];
		$image = $images[0];
		
		// get item id from url
		$item_id = substr($index, -12);
		
		// create thumbnail reference
		$thumb = str_replace('s-l1600.jpg','s-l64.jpg',$images);
		
		// add to $data json array
		$data[] = [
			'url' => '<a href="'.$index.'" target="_new">'.$item_id.'</a>',
			'title' => $title,
			'price' => $price,
			'condition' => $cond,
			'photo' => $images,
			'thumb' => '<a href="'.$index.'" target="_new"><img src="'.$thumb.'"></a>'
		];
		
		
	},
	'rejected' => function(Exception $reason, $index) {
		// This callback is delivered each failed request
		echo "Requested search term: ", $index, "\n";
		echo $reason->getMessage(), "\n\n";
	},
]);

// Initiate the transfers and create a promise
$promise = $pool->promise();

// Force the pool of requests to complete
$promise->wait();

// output the data in json format
echo json_encode($data);

?>