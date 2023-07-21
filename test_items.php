<?php

// test the items data available

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

// setup variables and arrays
$links = [];
$items = [];
$storename = 'cashinwhi';

// get all links for cashinwhi
$links = $nlyz->get_all_links($storename);

// use this to pool all guzzle http requests together
$requestGenerator = function($links) use ($client) {
	foreach ($links as $key => $value) {
		// The magic happens here, with yield key => value
		yield $value => function() use ($client, $value) {
			// Our identifier does not have to be included in the request URI or headers
			return $client->getAsync($value);
		};
	}
};

// get all the results for each http request
$pool = new GuzzleHttp\Pool($client, $requestGenerator($links), [
	'concurrency' => 10,
	'fulfilled' => function(GuzzleHttp\Psr7\Response $response, $index) use ($storename) {

		// require the main nlyz class
		require_once(dirname(__FILE__) . '/classes/class.nlyz.php');

		// assign object for class nlyz
		$nlyz = new nlyz();

		// get the body
		$body = $response->getBody();

		// setup variables
		$details = [];
		$images = [];
		$item_id = 0;
		
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
		
		// create sql script
		// $sql = 'insert into item_data (itemid, storename, title, price, cond, gallery) values ("'.$item_id.'", "'.$storename.'", "'.$title.'", '.$price.', "'.$cond.'", "'.$thumb.'")';
		
		// execute
		// $this->query($sql);
		
		// increment item count
		// $sql = 'update requests set items = items + 1 where storename = "'.$index.'"';

		// debug the sql
		
		echo $cond.' - '.implode(' ',array_unique(explode(' ', $cond))).'<Br/>';
		
				
	},
	'rejected' => function(Exception $reason, $index) {
		// This callback is delivered each failed request
		// echo "Requested search term: ", $index, "\n";
		echo $reason->getMessage(), "\n\n";
		echo $reason->getBody(), "\n\n";
	},
]);

// Initiate the transfers and create a promise
$promise = $pool->promise();

// Force the pool of requests to complete
$promise->wait();
?>