<?php

// load composer autoloader
require('C:\Users\crosenblum\vendor\autoload.php');

// enable guzzle
use GuzzleHttp\Client;
use GuzzleHttp\Promise;

// enable htmlpurifier
use ezyang\htmlpurifier;


// class to handle all nlyz functions

class nlyz {

	// sent from ajax to start the backend processing
	public function send_request(string $storename, int $reporttype) {
		
		// check if storename is empty
		if (empty($storename)) {
			
			// return nothing
			return;
		}

		// get the basic info and store it as a request
		$sql = 'replace into nlyz.requests (storename, reporttype) values ("'.$storename.'", '.$reporttype.')';
		
		// execute this
		$this->query($sql);
		
		return;
		
	}
	
	// check if url is valid
	public function valid_url(string $url) {

		// setup variables
		$valid = 0;
		$headers = [];

		// check if storename is empty
		if (empty($url)) {
			
			// return invlaid
			return $valid;
		}
		

		// use curl to get http hheaders
		// https://stackoverflow.com/questions/11797680/getting-http-code-in-php-using-curl
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0)");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		$rt = curl_exec($ch);
		$headers = curl_getinfo($ch);		
		
		// check redirect_url
		if ($headers['redirect_url'] == 'https://www.ebay.com/n/error?statuscode=404') {
			
			// return valid
			return $valid;
			
		} else {
			
			// return valid
			return 1;
			
		}
	}
	
	
	// check if store is valid
	public function valid_store(string $storename) {
		
		// setup variables
		$valid = 0;
		$store_url = 'http://stores.ebay.com/';
		$headers = [];

		// check if storename is empty
		if (empty($storename)) {
			
			// return invlaid
			return $valid;
		}
		
		// add storename to storeurl
		$url = $store_url.$storename;

		// use curl to get http hheaders
		// https://stackoverflow.com/questions/11797680/getting-http-code-in-php-using-curl
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0)");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		$rt = curl_exec($ch);
		$headers = curl_getinfo($ch);		
		
		// check redirect_url
		if ($headers['redirect_url'] == 'https://www.ebay.com/n/error?statuscode=404') {
			
			// return valid
			return $valid;
			
		} else {
			
			// return valid
			return 1;
			
		}
		
		
	}
	
	public function check_request(string $storename, int $reporttype) {
	
		// check if storename is empty
		if (empty($storename)) {
			
			// return nothing
			return;
		
		}
		
		// setup variables and arrays
		$results = [];
		$return = [];
		
		// get links and completed total
		$sql = 'select links, items, completed from nlyz.vResults where storename = "'.$storename.'"';
		
		// get results
		$results = $this->query($sql);
		
		// create array
		$return[0] = $results[0]['links'];
		$return[1] = $results[0]['completed'];
		$return[2] = $results[0]['items'];
		

		// return this array
		return $return;

		
	}
	
	// https://gist.github.com/wzed/d9273719e54e78da4a6a63d4fc9e2606
	public function complete_request() {
		
		// setup arrays and variables
		$results = [];
		$links = [];
		$storename = '';
		$item_count = 0;

		// setup guzzle client
		$client = new GuzzleHttp\Client(['base_uri' => 'https://www.ebay.com']);

		// get 1 unfilled request
		$sql = 'select storename, links, items from requests where completed = 0 order by datetime asc limit 1';
		
		// get the request data
		$results = $this->query($sql);
		
		// check if any results found
		if (empty($results)) {
			
			// return nothing no request unfilled found
			return;
			
		}
		
		// get the storename and only the storename
		$storename = $results[0]['storename'];

		// check if storename is empty
		if (empty($storename)) {
			
			// return nothing
			return;
		}

		
		// get all links for storename
		$links = $this->get_all_links($storename);
		
		// get all items for each item link
		$this->getitems($storename, $links);
		
		// mark completed
		$sql = 'update requests set completed = 1 where storename = "'.$storename.'"';
		$this->query($sql);
				
	}
	
	private function getitems(string $storename, array $links) {
		
		// check if storename is empty
		if (empty($storename)) {
			
			// return nothing
			return;
		}
		
		// empty items data for this store
		$sql = 'delete from item_data where storename = "'.$storename.'"';
		$this->query($sql);
		
		// set item count in request table to zero
		$sql = 'update requests set items = 0 where storename = "'.$storename.'"';
		$this->query($sql);
		
		// setup guzzle client
		$client = new GuzzleHttp\Client(['base_uri' => 'https://www.ebay.com']);

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

				// get the body
				$body = $response->getBody();

				// setup variables
				$details = [];
				$images = [];
				$item_id = 0;
				
				// get the details for this url
				$details = $this->get_item_details($body);

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
				$sql = 'insert into item_data (itemid, storename, title, price, cond, gallery) values ("'.$item_id.'", "'.$storename.'", "'.$title.'", '.$price.', "'.$cond.'", "'.$thumb.'")';
				$this->query($sql);
				
				// increment item count
				$sql = 'update requests set items = items + 1 where storename = "'.$storename.'"';
				$this->query($sql);
				
				// delete link from links table
				
				
				
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
		
	}
	

	public function wait_request(string $storename) {
	
		// check if storename is empty
		if (empty($storename)) {
			
			// return nothing
			return;
		}

		
		// setup arrays and variables
		$results = [];
		$sql = '';
		$reporttype = 0;
		
		// check for completed request for storename
		$sql = 'select reporttype from requests where storename = "'.$storename.'" and completed = 1';
		
		// get results
		$results = $this->query($sql);
		
		// check if row count or empty
		if (empty($results)) {
			
			
			// no results return and check again later
			return;
			
		}
		
		// get report type
		$reporttype = $results['reporttype'];

		// check if report type not empty and greater than zero
		if (empty($reporttype) || $reporttype == 0) {
			
			// return cuz no report set
			return;
			
		}

		// switch case get data based on type of report
		
		
		
		
	}
	
	public function get_all_links(string $storename) {


		// check if storename is empty
		if (empty($storename)) {
			
			// return nothing
			return;
		}
		
		// set max execution time
		ini_set('max_execution_time', 2000);
		
		// xss filter store name
		$storename = filter_var($storename, FILTER_SANITIZE_STRING);
		
		// filter variable
		$storename = preg_replace("/[^A-Za-z0-9 ]/", '', $storename);
		
		// wipe all links stored for this store
		$sql = 'delete from links where storename "'.$storename.'"';
		$this->query($sql);
		
		// reset link count
		$sql = 'update requests set links = 0 where storename "'.$storename.'"';
		$this->query($sql);
		
		// setup variables and arrays
		$url = 'https://www.ebay.com/str/' . $storename . '?_sop=10';
		$temp = [];
		$limit = 1000;
		$count = 0;
		$result = [];
		$results = [];
		$data = [];

		// step 1. get the html
		$html = $this->gethttp($url);

		// setup guzzle client
		$client = new GuzzleHttp\Client(['base_uri' => 'https://www.ebay.com']);

		// use this to pool all guzzle http requests together
		$requestGenerator = function($results) use ($client, $url) {

			// loop from 1 to 10
			for($i = 1; $i<=20; $i++) {

				// add page number to url
				$cur_url = $url.'&_pgn='.$i;
				
				// check if valid url
				if ($this->valid_url($cur_url) == 1) {
					
					// The magic happens here, with yield key => value
					yield $i => function() use ($client, $cur_url) {
						
						// Our identifier does not have to be included in the request URI or headers
						return $client->getAsync($cur_url);

					};
				}
			}

		};
		
		// capture the output here
		$pool = new GuzzleHttp\Pool($client, $requestGenerator($results), [
			'concurrency' => 10,
			'fulfilled' => function(GuzzleHttp\Psr7\Response $response, $index) use (&$data, $storename) {
				// This callback is delivered each successful response

				// If these values don't match, something is very wrong
				// echo "Requested eBay Listing: [", $index, "]<br/>";
				
				// get the body
				$html = $response->getBody();
				
				// use regex to grab all links
				preg_match_all('/\b(?:(?:https?|ftp|file):\/\/|www\.|ftp\.)[-A-Z0-9+&@#\/%=~_|$?!:,.]*[A-Z0-9+&@#\/%=~_|$]/i', $html, $result, PREG_PATTERN_ORDER);

				// grabs only ebay item links
				foreach ($result[0] as $key => $value) {

					// remove this item from array
					if(stripos($value, "/itm/") == true) {

						// grab first 37 chars of string
						$itm_url = mb_substr($value, 0, 37);

						// append to new array
						$data[] = $itm_url;
						
						// add link to table
						// $sql = 'insert into links (storename, url) values ("'.$storename.'", "'.$itm_url.'")';
						// $this->query(sql);
						
						
					}
				}
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
		
		// count number of items in data array
		$link_count = count($data);
		
		// update links count inside requests table
		$sql = 'update requests set links = '.$link_count.' where storename = "'.$storename.'"';
		$this->query($sql);
		
		// return the data
		return $data;
		
	}
	

	// assume i got the html as async parallel http requests
	public function get_item_details(string $html) : array {
		
		// setup variables
		$desc = [];
		$images = [];

		// get page title
		$title = $this->page_title($html);
		
		
		// now get condition name and text
		$condText = $this->getebyclass('d-item-condition-text', $html);
		
		// if condition has duplicates
		switch ($condText) {
			case 'NewNew':
				$condText = 'New';
				break;
			case 'New – Open boxNew box';
				$condText = 'Open Box';
				break;
			case 'New â Open boxNew box';
				$condText = 'Open Box';
				break;
			case 'New Open boxNew box';
				$condText = 'Open Box';
				break;
			case 'New with tagsNew Tags';
				$condText = 'New With Tags';
				break;
			case 'New other (see details)New details)';
				$condText = 'New Other';
				break;
			case 'New OtherNew Other':
				$condText = 'New Other';
				break;
			case 'Open boxOpen box';
				$condText = 'Open Box';
				break;
			case 'UsedUsed';
				$condText = 'Used';
				break;
		}
		
		// just in case
		if (strtolower($condText) == 'NewNew') {
			
			// replace it
			$condText = 'New';
			
		}
			
		// get images from html
		$images = $this->getitmphotos($html);
		
		// get first image
		$image = $images[0];
		
		// get price information
		$price = $this->getebyid('prcIsum', $html);
		$price = $price->textContent;
		$price = preg_replace('/[^.0-9]/s', '', $price );
		
		// now create array
		$desc[0] = $title;
		$desc[1] = $price;
		$desc[2] = $condText;
		$desc[3] = $image;
		
		// now return item
		return $desc;
		
	}
		

	private function getebyclass(string $classname, string $html) {
		
		// setup variables
		$return = '';
		
		// do try and catch
		try {

			// create dom document
			$dom = new DOMDocument;
			libxml_use_internal_errors(true);

			// disable error checking
			$dom->strictErrorChecking = false;
			
			// load html into dom
			$dom->loadHTML($html);

			// Enable validate on parse
			$dom->validateOnParse = true;

			// clear dom errors
			libxml_clear_errors();
			
			// get xpath of dom
			$xpath = new DOMXpath($dom);
			
			// get content by classname
			$results = $xpath->query("//*[@class='" . $classname . "']");

			// get div content
			$return = $results->item(0)->nodeValue;	
			$return = implode(" ",array_unique(explode(" ", $return )));
		
		
		} catch (Exception $e) {
			
			// do nothing
			
		}


		// return the data we found
		return $return;
		
	}
	
	private function getebyid(string $id, string $html) {
		
		// setup variables
		$return = '';
		
		// do try and catch
		try {
		
			// create dom document
			$dom = new DOMDocument;
			libxml_use_internal_errors(true);

			// disable error checking
			$dom->strictErrorChecking = false;
			
			// load html into dom
			$dom->loadHTML($html);

			// Enable validate on parse
			$dom->validateOnParse = true;

			// clear dom errors
			libxml_clear_errors();
			
			// get content by id
			$return = $dom->getElementById($id);
		
		} catch (Exception $e) {
			
			// do nothing
			
		}

		
		// return the data we found
		return $return;
		
		
	}
	
	public function page_title(string $html): ?string {
		

		// setup variables
		$title = '';
		$res = '';

		// do preg match to get title from html
        $res = preg_match("/<title>(.*)<\/title>/siU", $html, $title_matches);
        if (!$res) 
            return null; 

        // Clean up title: remove EOL's and excessive whitespace.
        $title = preg_replace('/\s+/', ' ', $title_matches[1]);
        $title = trim($title);
		
		// remove the ebay part of the title
		$title = str_replace(' | eBay','', $title);
		
		// return it
        return $title;
    }


	public function getiframesrc(string $html) {

	
		// setup variables
		$iframe_src = '';

		// create dom document
		$dom = new DOMDocument;
		libxml_use_internal_errors(true);
		// $html = file_get_contents($url);
		libxml_clear_errors();
		$html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $html);

		
		$dom->strictErrorChecking = false;
		$dom->loadHTML($html);
		libxml_clear_errors();

		$xpath = new DOMXpath($dom);
		foreach($xpath->query('//iframe') as $iframe) {
			$iframe_src = $iframe->getAttribute('src');
		}
		
		// return src
		return $iframe_src;

	}

	private function getitmphotos(string $html) {
		
		// setup array
		$images = [];
		
		// check if html is empty
		if ($html === '' || $html === '0') {
			
			// return nothing
			return $images;
		
		}
		// get html inside div content gallThumbs
		$doc = new DOMDocument();
		@$doc->loadHTML($html);
		$tags = $doc->getElementsByTagName('img');

		// loop thru tags
		foreach ($tags as $tag) {
			
			// get url of img src
			$img_url = $tag->getAttribute('src');
			
			// check if url starts with https://kyozoufs.blob.core.windows.net - 38 chars
			// check if already exists in array
			if (substr( $img_url, 0, 29 ) === "https://i.ebayimg.com/images/" && !in_array($img_url, $images)) {
				
				// replace the filename to get the full size image
				$img_url = str_replace('s-l64.jpg','s-l1600.jpg',$img_url);
				
				// replace other size with big size
				$img_url = str_replace('s-l300.jpg','s-l1600.jpg',$img_url);
				
				// pass image url to array
				$images[] = $img_url;
				
			}
			
		}

		// return images
		return $images;
		
	}

	/**
	 * @return string[]
	 *
	 * @psalm-return list<string>
	 */
	private function iframephotos(string $html): array {

		// setup array
		$images = [];
		
		// check if html is empty
		if ($html === '' || $html === '0') {
			
			// return nothing
			return $images;
		
		}
		
		// get html inside div content gallThumbs
		$doc = new DOMDocument();
		@$doc->loadHTML($html);
		$tags = $doc->getElementsByTagName('img');

		// loop thru tags
		foreach ($tags as $tag) {
			
			// get url of img src
			$img_url = $tag->getAttribute('src');
			
			// check if url starts with https://kyozoufs.blob.core.windows.net - 38 chars
			// check if already exists in array
			if (substr( $img_url, 0, 38 ) === "https://kyozoufs.blob.core.windows.net" && !in_array($img_url, $images)) {
				
				// echo $img_url.'<br/>';
				$images[] = $img_url;
				
			}
			
		}

		// return images
		return $images;
		
		
	}

	/**
	 * @return null|string
	 */
	private function getdesc(string $iframe_src = null) {

		// check if valid url
		if (filter_var($iframe_src, FILTER_VALIDATE_URL) === false) {
			return;
		}
		
		// check if iframe_src is empty
		if (empty($iframe_src)) {
			
			// return nothing
			return null;
			
		}
		
		// create dom document
		$dom = new DOMDocument;
		libxml_use_internal_errors(true);
		$html = file_get_contents($iframe_src);
		$html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $html);
		$dom->strictErrorChecking = false;
		$dom->loadHTML($html);
		$xpath = new DOMXPath($dom);
		libxml_clear_errors();
		$classname="Description";
		$results = $xpath->query("//*[@class='" . $classname . "']");
		
		// get div content
		$desc = $results->item(0)->nodeValue;
		
		// remove the word description
		$desc = str_replace('Description', '', $desc);
		
		// escape html special characters
		$desc = htmlentities($desc, ENT_QUOTES);
		
		// return description
		return $desc;
		
	}
	
	public function gethttp(string $url) {
		
		// check if url is empty
		if (empty($url)) {
			return;
		}

		// setup guzzle client
		$client = new GuzzleHttp\Client(['base_uri' => 'https://www.ebay.com']);
		
		// get response
		$response = $client->get($url);
		
		// get body of response
		$body = $response->getBody();
		
		// return response
		return $body;
		
	}
	
	
	// https://www.codegrepper.com/code-examples/php/php+how+to+Sanitize+POST+data
	public function sanitize($string) {

		// strip html special characters
		$string = htmlspecialchars($string);
		
		// strip slashes
		$string = stripslashes($string);
		
		// filter sanitize
		// $string = filter_var($string, FILTER_SANITIZE_STRING);
		
		// trim it
		$string = trim($string);

		// return value
		return $string;		
		
	}


	public function query(string $sql) {
		
		// setup variables
		$DB_USER = 'root';
		$DB_PW = '';
		$stmt = '';
		

		// try fail catch for the pdo connection
		try {
			// $db = new PDO('mysql:host=localhost;dbname=nlyz', $DB_USER, $DB_PW);
			$db = new PDO('mysql:host=localhost;dbname=nlyz', $DB_USER, $DB_PW, 
						  [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]
			);			
		} catch(PDOException $e) {
			return 'ERROR: ' . $e->getMessage();
		}

		// set fetch mode
		$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); 

		// check if database connection worked
		if ($db == null) {

			// result: db does open
			return 'Can not connect to mysql database!';

		}
		
		// now get results
		try {
			
			// do query
			$stmt = $db->query($sql);

			// check if failure in the query
			$results = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : $stmt->fetchAll();

		} catch(PDOException $e) {
			

			// send error message
			return "Statement failed: " . $e->getMessage();
		}		

		// close connection
		// $db->close();
		// unset($db);
		
		// return result
		return $results;
		
	}
	
}

?>