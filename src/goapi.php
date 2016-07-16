<?
////////////////////////////////////////////////////////////
// Constants

////////////////////////////////////////////////////////////
// initialization

// should set this in the file that includes the current file
// set the include path to use the new pear stuff
/*
ini_set( 'include_path', '/home/gpeters/pub_html/cgi-bin/ultra:.:/usr/local/lib/php');
include("goapi.php");
*/

// initialize SOAP web services
include("SOAP/Client.php");

////////////////////////////////////////////////////////////
// Calls the Google API
function do_search( $query, $key, &$num, &$result, $maxresults, $start = 0, $safe = false )
{
$soapclient = new SOAP_Client('http://api.google.com/search/beta2');
$soapoptions = array('namespace' => 'urn:GoogleSearch',
                 'trace' => 0);

	$params =  array(
                'key' => $key,
                'q' => $query,
                'start' => $start,
                'maxResults' => $maxresults,
                'filter' => false,
                'restrict' => '',
                'safeSearch' => $safe,
                'lr' => '',
                'ie' => '',
                'oe' => '',
        );

	$ret = $soapclient->call('doGoogleSearch', $params,
	$soapoptions);

	if (PEAR::isError($ret))
    {
/*	print("<!--An error #" . $ret->getCode() . " occurred!");
		print(" Error: " . $ret->getMessage() . "-->\n");
*/

		return false;
	}
	else
	{
		/*print("<pre>");
		print_r($ret);
		print("</pre><br><br>");
		*/
		
		// We have proper search results
		$num = $ret->estimatedTotalResultsCount;
		$result = $ret;
	}

	return true;
}

////////////////////////////////////////////////
// Does Google search with retry. 
// Retry is useful because sometimes the connection will
// fail for some reason but will succeed when retried.
function search( $query, $key, &$num )
{
	$result = false;
	$max_retries = 10;
	$retry_count = 0;

	while( !$result && $retry_count < $max_retries )
	{
		$num_results = 1;
  		$result = do_search( $query, $key, $num, $temp, $num_results );	
        if( !$result )
		{
		//print( "\n<!-- query: $query  Attempt: $retry_count failed. -->\n");
		}	
		$retry_count++;
	}
	return $result;
}

////////////////////////////////////////////////
// Does Google search with retry. 
// Retry is useful because sometimes the connection will
// fail for some reason but will succeed when retried.
// Overloaded version returns the result object.
function search_with_results( $query, $key, &$num, &$search_results, $start = 0, $safe = false )
{
	$result = false;
	$max_retries = 10;
	$retry_count = 0;

	while( !$result && $retry_count < $max_retries )
	{
  		$result = do_search( $query, $key, $num, $search_results, 10, $start, $safe );	
	    if( !$result )
		{
			//print( "\n<!-- Query: $query  Attempt: $retry_count failed. -->\n");
		}	
		$retry_count++;
	}
	return $result;
}
