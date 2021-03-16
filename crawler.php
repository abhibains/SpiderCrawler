<?php



$start = "http://localhost/cp476/a4/q3/index.html";

// Our 2 global arrays containing our links to be crawled.
$already_crawled = array();
$crawling = array();

function get_details($url) {

	url_index($url);
	index_url($url);
	
	// The array that we pass to stream_context_create() to modify our User Agent.
	$options = array('http'=>array('method'=>"GET", 'headers'=>"User-Agent: sing6290\n"));
	// Create the stream context.
	$context = stream_context_create($options);
	// Create a new instance of PHP's DOMDocument class.
	$doc = new DOMDocument();
	// Use file_get_contents() to download the page, pass the output of file_get_contents()
	// to PHP's DOMDocument class.
	@$doc->loadHTML(@file_get_contents($url, false, $context));
	follow_links($url);
	// Create an array of all of the title tags.
	$title = $doc->getElementsByTagName("title");
	// There should only be one <title> on each page, so our array should have only 1 element.
	$title = $title->item(0)->nodeValue;
	// Give $description and $keywords no value initially. We do this to prevent errors.
	$description = "";
	$keywords = "";
	// Create an array of all of the pages <meta> tags. There will probably be lots of these.
	$metas = $doc->getElementsByTagName("meta");
	// Loop through all of the <meta> tags we find.
	for ($i = 0; $i < $metas->length; $i++) {
		$meta = $metas->item($i);
		// Get the description and the keywords.
		if (strtolower($meta->getAttribute("name")) == "description")
			$description = $meta->getAttribute("content");
		if (strtolower($meta->getAttribute("name")) == "keywords")
			$keywords = $meta->getAttribute("content");

	}

	index_keyword($url,$keywords);

	file_put_contents("url_keyword.txt", $url."  ".$keywords."\n",FILE_APPEND | LOCK_EX );
	
	// Return our JSON string containing the title, description, keywords and URL.
	return '{ "Title": "'.str_replace("\n", "", $title).'", "Description": "'.str_replace("\n", "", $description).'", "Keywords": "'.str_replace("\n", "", $keywords).'", "URL": "'.$url.'"},';

}

function follow_links($url) {
	// Give our function access to our crawl arrays.

	global $already_crawled;
	global $crawling;
	// The array that we pass to stream_context_create() to modify our User Agent.
	$options = array('http'=>array('method'=>"GET", 'headers'=>"User-Agent: howBot/0.1\n"));
	// Create the stream context.
	$context = stream_context_create($options);
	// Create a new instance of PHP's DOMDocument class.
	$doc = new DOMDocument();
	// Use file_get_contents() to download the page, pass the output of file_get_contents()
	// to PHP's DOMDocument class.
	@$doc->loadHTML(@file_get_contents($url, false, $context));
	// Create an array of all of the links we find on the page.
	$linklist = $doc->getElementsByTagName("a");

	 url_graph($linklist, $url);
	 index_graph($linklist,$url);

	// Loop through all of the links we find.
	foreach ($linklist as $link) {
		$l =  $link->getAttribute("href");
		// Process all of the links we find. This is covered in part 2 and part 3 of the video series.
		if (substr($l, 0, 1) == "/" && substr($l, 0, 2) != "//") {
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"].$l;
		} else if (substr($l, 0, 2) == "//") {
			$l = parse_url($url)["scheme"].":".$l;
		} else if (substr($l, 0, 2) == "./") {
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"].dirname(parse_url($url)["path"]).substr($l, 1);
		} else if (substr($l, 0, 1) == "#") {
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"].parse_url($url)["path"].$l;
		} else if (substr($l, 0, 3) == "../") {
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"]."/".$l;
		} else if (substr($l, 0, 11) == "javascript:") {
			continue;
		} else if (substr($l, 0, 5) != "https" && substr($l, 0, 4) != "http") {
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"]."/".$l;
		}
		// If the link isn't already in our crawl array add it, otherwise ignore it.
		if (!in_array($l, $already_crawled)) {
				$already_crawled[] = $l;
				$crawling[] = $l;
				// Output the page title, descriptions, keywords and URL. This output is
				// piped off to an external file using the command line.
				echo get_details($l)."\n";
		}

	}
	// Remove an item from the array after we have crawled it.
	// This prevents infinitely crawling the same page.
	array_shift($crawling);
	// Follow each link in the crawling array.
	foreach ($crawling as $site) {
		follow_links($site);
	}

}

function index_keyword($url, $keywords){
	$output = "\n".hasher($url)." ".$keywords;
	file_put_contents("index_keywords.txt",$output, FILE_APPEND);
}

function index_graph($neighbour,$node){
	$output = "\n".hasher($node);

	foreach($neighbour as $nexturl){
		$output.= " ,".hasher($nexturl->getAttribute("href"));
	}

	file_put_contents("index_graph.txt",$output, FILE_APPEND);
}

function hasher($input_link){
	$urlindex = bcmod(base_convert(sha1($input_link), 16, 10), 1000);
	return $urlindex;
}

function url_index($url){
		
	$range = 1000;
	$urlindex = bcmod(base_convert(sha1($url), 16, 10), $range);
	$output = "\n".$url." ,".$urlindex;
	file_put_contents("url_index.txt",$output, FILE_APPEND);
}
function index_url($url){
		
	$range = 1000;
	$urlindex = bcmod(base_convert(sha1($url), 16, 10), $range);
	$output = "\n".$urlindex." ,".$url;
	file_put_contents("index_url.txt",$output, FILE_APPEND);
}

function url_graph($neighbours, $node){
	$output = "\n".$node;
	foreach($neighbours as $nexturl){
		$output.= " ,".$nexturl->getAttribute("href");
	}
	file_put_contents("url_graph.txt",$output, FILE_APPEND);	
}
// Begin the crawling process by crawling the starting link first.
follow_links($start);