<?
/* This code is Copyright (c) 2008 Geoff Peters 
and is released open source under the GNU Public License, with the following modifications:

1) Attribution:
Any derived works of this code such as applications, web sites, or games must contain a text credit to Geoff Peters and a clickable link to his web site, www.gpeters.com, displayed on an "About" page or screen or equivalent. That is even if the code is re-written in a different language or for a different platform.

2) Revenue Sharing:
Applications making use of this source code or dervied works are "encouraged" but not required to donate a portion of their advertising revenue or music download referral revenue to Geoff Peters, via Paypal. Geoff Peters can be contacted at geoff@gpeters.com.

3) Any modifications to this code must be made available open source, and also contain this message.

4) This code is released as-is, with no warranty, and no guarantee that it will function correctly. It is not advised to use this code for mission critical or safety-related applications.
*/

ini_set( 'include_path', '/var/www/vhosts/googleduel.com/httpdocs:.:/usr/share/pear');

// load the Google API object (search functions)
// and initialize SOAP web services
include("goapi.php");

// load the MYSQL Database
require("autotypedb.php");

header('Content-type: text/html; charset=UTF-8') ;

$dfile = fopen( "debug.txt", "a");

//////////////////////////////
// Prints debug text
function print_debug( $text )
{
	global $dfile;
	fwrite( $dfile, $text );

	//print( $text );
}
///////////////////////////////////////////////////////////////////////////
// removes accents from a string
function remove_accents( $str )
{
  $str = mb_convert_encoding($str, 'HTML-ENTITIES', 'UTF-8');
  $str = preg_replace('/&([a-zA-Z])(uml|acute|grave|circ|tilde);/','$1',$str);
  return html_entity_decode($str);
}

/////////////////////////////////////////////////////////////////////////////
function convert_html_to_utf( $str )
{
  return mb_convert_encoding($str, 'UTF-8','HTML-ENTITIES');

}

/////////////////////////////////////////////////////////////////////////////
function convert_utf_to_html( $str )
{
  return mb_convert_encoding($str, 'HTML-ENTITIES', 'UTF-8');

}
//////////////////////////////////////////////////////////////////////
function convert_utf_to_iso( $str )
{
  return mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8');
}

//////////////////////////////////////////////////////////////////////
function convert_iso_to_utf( $str )
{
  return mb_convert_encoding($str,'UTF-8','ISO-8859-1');
}

/////////////////////////////////////////////////////////////////////////////
function get_utf8_allaccents_string()
{
	$str = convert_html_to_utf( "&Agrave;&agrave;&Aacute;&aacute;&Acirc;&acirc;&Atilde;&atilde;&Auml;&auml;&Egrave;&egrave;&Eacute;&eacute;&Ecirc;&ecirc;&Euml;euml;&Igrave;&igrave&Iacute;&iacute;&Icirc;&icirc;&Iuml;&iuml;&Ntilde;&ntilde;&Ograve;&ograve;&Oacute;&oacute;&Ocirc;&ocirc;&Otilde;&otilde;&Ouml;&ouml;&Ugrave;&ugrave;&Uacute;&uacute;&Ucirc;&ucirc;&Uuml;&uuml;&Yacute;&yacute;&Yuml;&yuml;" );

	return $str;
}

/////////////////////////////////////////////////////////////////////////////
function replace_vowels_with_regex( $str )
{
	$a_exp = "[aA&Agrave;&agrave;&Aacute;&aacute;&Acirc;&acirc;&Atilde;&atilde;&Auml;&auml;]";
	$e_exp = "[eE&Egrave;&egrave;&Eacute;&eacute;&Ecirc;&ecirc;&Euml;&euml;]";
	$i_exp = "[iI&Igrave;&igrave;&Iacute;&iacute;&Icirc;&icirc;&Iuml;&iuml;]";
	$n_exp = "[nN&Ntilde;&ntilde;]";
	$o_exp = "[oO&Ograve;&ograve;&Oacute;&oacute;&Ocirc;&ocirc;&Otilde;&otilde;&Ouml;&ouml;]";
	$u_exp = "[uU&Ugrave;&ugrave;&Uacute;&uacute;&Ucirc;&ucirc;&Uuml;&uuml;]";
	$y_exp = "[yY&Yacute;&yacute;&Yuml;&yuml;]";
	
	$a_string = convert_html_to_utf( $a_exp );
	$e_string = convert_html_to_utf( $e_exp );
	$i_string = convert_html_to_utf( $i_exp );
	$n_string = convert_html_to_utf( $n_exp );
	$o_string = convert_html_to_utf( $o_exp );
	$u_string = convert_html_to_utf( $u_exp );
	$y_string = convert_html_to_utf( $y_exp );

	mb_regex_encoding( 'UTF-8' );

	$str = mb_ereg_replace( $a_string, $a_string, $str);
	$str = mb_ereg_replace( $e_string, $e_string, $str);
	$str = mb_ereg_replace( $i_string, $i_string, $str);
	$str = mb_ereg_replace( $n_string, $n_string, $str);
	$str = mb_ereg_replace( $o_string, $o_string, $str);
	$str = mb_ereg_replace( $u_string, $u_string, $str);
	$str = mb_ereg_replace( $y_string, $y_string, $str);

	return $str;
}

/////////////////////////////////////////////////////////////////////////
function html_entity_decode_utf($var)
{
	$var=html_entity_decode($var, ENT_QUOTES, 'UTF-8');
	return $var;
}

/////////////////////////////////////////////////////////////////////////
function remove_punctuation( $var )
{
	mb_regex_encoding( 'UTF-8' );

	return mb_ereg_replace( '[.,!?();:\-"]', ' ', $var);
}

/////////////////////////////////////////////////////////////////////////
function remove_commas( $var )
{
        mb_regex_encoding( 'UTF-8' );

        return mb_ereg_replace( '[,]', ' ', $var);
}


/////////////////////////////////////////////////////////////////////
function condense_spaces( $var )
{	
	mb_regex_encoding( 'UTF-8' );

	return mb_ereg_replace( '[ ]+', ' ', $var);
}

/////////////////////////////////////////////////////////////////////////
// Parses a google snippet to extract the found word
function parse_snippet( $snippet, $phrase, &$found_word )
{
	// first remove tags and punctuation
	$snippet = html_entity_decode_utf( $snippet );
	$snippet = mb_ereg_replace("<[^>]*>","",$snippet); 
	$snippet = remove_punctuation( $snippet );
	$snippet = condense_spaces( $snippet );

	$phrase = replace_vowels_with_regex( $phrase );

	// now extract the word

	$all_accents = get_utf8_allaccents_string();

	$reg_expr = "($phrase)([ ]+)([^ ]+)";
	print_debug("\n\n reg expr: $reg_expr");
	
	print_debug("\n\n snippet: $snippet");

	mb_regex_encoding( 'UTF-8' );
	$ok = mb_eregi( $reg_expr, $snippet, $regs );

	if( !$ok )
	{
		print_debug(" bad!! ");
		return false;
	}


	//print_r( $regs);
	//$found_word = strtolower($regs[3]);
	$found_word = $regs[3];

	return true;
}

/////////////////////////////////////////////////////////////////
function add_word( $snippet, &$found_words, $phrase, &$current_word_index )
{
	if( parse_snippet( $snippet, $phrase, $found_word ))
	{
		print_debug( " got word: $found_word )");

		// check if the word has already been found
		$new_word = true;
		for( $i = 0; $i < $current_word_index; $i++ )
		{
			if( $found_words[$i] == $found_word )
			{	
				$new_word = false;
				break;
			}
		}

		// if it's a newly found word
		if( $new_word ) 
		{
			// add the word to the list
			$found_words[ $current_word_index ] = $found_word;
			$current_word_index++;
		}
	}
}
/////////////////////////////////////////////////
// Adds found words to a list of found words
function add_found_words_to_list( $result, &$found_words, $phrase )
{
	$current_word_index = count( $found_words );

	print_debug( "cwi: $current_word_index ");

	// remove surrounding quotes
	$phrase = trim(ereg_replace( "\"", " ", $phrase));

	foreach( $result->resultElements as $element )
	{
		print_debug(" $element->snippet ");

		add_word( $element->snippet, $found_words, $phrase, $current_word_index );
		add_word( $element->title, $found_words, $phrase, $current_word_index );

	}
}

////////////////////////////////////////////////////
// Search for words that follow the given search string
function search_for_words( $key, $search_string, &$found_words, &$count, $expand_results = false )
{
	$ok = search_with_results( $search_string, $key, $count, $result, 0, true /*safesearch*/ );
	if( ! $ok )
	{
		return false;
	}

//print_r( $result );

	if( $count == 0 )
	{
		return true;
	}
	
	add_found_words_to_list( $result, $found_words, $search_string );

	// if it is worth doing another search
	if( $expand_results && $count > 20 )
	{
		// start in a random place in the results
		if( $count - 10 < 100 )
		{
			$max = $count - 10;
		}
		else
		{
			$max = 100;
		}
		$start = rand( 10, $max );
		
		print_debug(" start at $start");

		$ok = search_with_results( $search_string, $key, $count, $result2, $start, true /*safesearch*/ );
		if( $ok )
		{
			//print_r( $result2 );

			// add more words
			add_found_words_to_list( $result2, $found_words, $search_string );
		}
		else
		{
			print_debug("secondary_search_failed");
		}
	}

	return true;
}


/////////////////////////////////////////////////////////////////////////
// Finds out some words to make a sentence
function find_words( $key, $search_words_four, $search_words_three, $search_words_two, &$found_words, &$count )
{

	if( $search_words_four <> "" )
	{
		$ok = search_for_words( $key, $search_words_four, $found_words, $count );
		if( ! $ok )
		{
			return false;
		}
	
		if( count( $found_words ) == 0 ) // didn't find any words
		{
			// search using a smaller search string
			$ok = search_for_words( $key, $search_words_three, $found_words, $count );
			if( ! $ok )
			{
				return false;
			}

			if( count( $found_words ) == 0 ) // didn't find any words
			{
				// search using a smaller search string
				$ok = search_for_words( $key, $search_words_two, $found_words, $count );
				if( ! $ok )
				{
					return false;
				}
			}
		}
	}
	else if( $search_words_three <> "" )
	{
		$ok = search_for_words( $key, $search_words_three, $found_words, $count );
		if( ! $ok )
		{
			return false;
		}
	
		if( count( $found_words ) == 0 ) // didn't find any words
		{
			// search using a smaller search string
			$ok = search_for_words( $key, $search_words_two, $found_words, $count );
			if( ! $ok )
			{
				return false;
			}
		}
	}
	else if( $search_words_two <> "" ) // there wasn't a three word search string, so just use the two word one.
	{
		$ok = search_for_words( $key, $search_words_two, $found_words, $count );
		if( ! $ok )
		{
			return false;
		}
	}


	// sort the array of words
	//asort( $found_words );

	return true;
}

///////////////////////////////////////////////////////////////\
// Prints the input form
function print_input_form( $lookback, $existing_text )
{
print("<div id=\"mainForm\">
<form method=\"POST\" action=\"autotype.php\">
  <p>Start your poem here: <input type=\"text\" name=\"words\" size=\"20\"> <input type=\"submit\" value=\"Submit\"></p>
  <input name=\"text\" type=\"hidden\" value=\"$existing_text\">
  <input name=\"lookback\" type=\"hidden\" value=\"$lookback\">
</form>
	</div>");
}
///////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////\
// Prints the input form
function print_word_select_button( $lookback, $word, $existing_text )
{

print("<form method=\"POST\" action=\"autotype.php\">
  <input type=\"submit\" onclick=\"hideForm();return true;\" value=\"   $word    \">
  <input name=\"text\" type=\"hidden\" value=\"$existing_text $word\">
  <input name=\"lookback\" type=\"hidden\" value=\"$lookback\">
</form>");
}
///////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////\
// Prints the lookback select button
function print_lookback_select_button( $lookback, $existing_text, $description )
{
print("<form method=\"POST\" action=\"autotype.php\">
  <input type=\"submit\" onclick=\"hideForm();return true;\" value=\"   $description    \">
  <input name=\"text\" type=\"hidden\" value=\"$existing_text\">
  <input name=\"lookback\" type=\"hidden\" value=\"$lookback\">
</form>");
}
///////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////\
// Prints the punctuation input form
function print_punctuation_button( $lookback, $punctuation_description,  $punctuation, $existing_text )
{
$punctuationSetting = "1";
/*
if( $punctuation == "," )
{
$punctuationSetting = "0";
}
*/
print("<form method=\"POST\" action=\"autotype.php\">
  <input type=\"submit\" onclick=\"hideForm();return true;\" value=\" $punctuation_description\">
  <input name=\"text\" type=\"hidden\" value=\"$existing_text" . "$punctuation\">
  <input name=\"punctuation\" type=\"hidden\" value=\"$punctuationSetting\">
  <input name=\"lookback\" type=\"hidden\" value=\"$lookback\">
</form>");
}
///////////////////////////////////////////////////////////////

//////////////////////////////////////////////////////////
// Removes a random item from array and returns it
function remove_random_item( &$arr )
{
	$total = count( $arr );
	$item_index = rand( 0, $total-1);
	print_debug( $item_index );
	$value = $arr[ $item_index ];
	$arr[ $item_index ] = $arr[ $total - 1 ];
	array_pop( $arr );

	return $value;
}

///////////////////////////////////////////////////////////////
// Gets a bunch of sentence starters and adds them to the bootstrap string.
function get_sentence_starters(&$bootstrap_string)
{

$sentence_starter_phrases = array( "I wish", "You might", "I want", "I hope", "You are", "My trainer", "Society is", "I think", "We should", "I hate", "I love", "You might", "I might", "My boyfriend", "My boyfriend is", "My girlfriend", "My girlfriend is", "My lover is", "My secret is", "You don't", "I don't", "Why do I", "My favorite", "Why is", "You never", "I never", "My mom said", "My mom", "My dad said", "My dad", "My dog is", "My cat is", "My cat is named", "My dog is named" , "My favorite food is", "I like to eat", "I want to become", "I want to lose", "I want to gain", "I like", "I study", "I learn", "The Internet was", "Bill Gates was", "Google was", "Microsoft was", "Yahoo was", "The USA was", "I enjoy", "I rent", "I buy", "I work", "I work at", "Football was", "Soccer was", "IBM was", "Nokia was", "Motorola was", "Baseball was", "I love my", "I hate my", "I like my", "I hope my", "I want my", "I dream of", "I dream that", "I dream to" ); 

	$number_to_print = 4;

	for( $i = 0; $i < $number_to_print; $i++)
	{
		$bootstrap_string .= "|" . remove_random_item( $sentence_starter_phrases );
	}
}

/////////////////////////////////////////////////
// Submits some text for publishing approval
function save_for_publish( $new_text )
{
	// do publish submission step
	
	$sql = "INSERT INTO Autotype_tbl (Content, IpAddress, DateCreated) VALUES ( '" .  mysql_escape_string( $new_text ) . "','"
	. date ("Y-m-d H:m:s")  . "', '$REMOTE_ADDR')";

	print_debug( $sql );

	$result = mysql_query($sql);
	if (mysql_error())
	{
		print_debug( "MySQL Error 3: " . mysql_error() . "<p>");
		return false;
	}
	return true;
}

/////////////////////////////////////
// Determines search strings based on text and lookback value.
function determine_search_strings( $lookback, $new_text, &$search_words_four, &$search_words_three, &$search_words_two )
{
	$ok = false;

	$new_text = remove_commas( $new_text );
//print("<!--test $new_text -->");
	$search_words_four = "";
	$search_words_three = "";
	$search_words_two = "";

	$accents = get_utf8_allaccents_string();

	mb_regex_encoding( 'UTF-8' );

	// get the last four words from existing text
	if( $lookback == 4 )
	{
		$reg_expr = '([^ ]+)([ ]+)([^ ]+)([ ]+)([^ ]+)([ ]+)([^ ]+)([ ]*)$';

		$ok = mb_eregi( $reg_expr, $new_text, $regs );
	}

	if( $ok )
	{
		$search_words_four = "\"" . $regs[1] ." " . $regs[3] ." " . $regs[5] . " " . $regs[7]. "\"";

		$search_words_three = "\"" . $regs[3] ." " . $regs[5] ." " . $regs[7] . "\"";

		$search_words_two = "\"" . $regs[5] ." " . $regs[7] . "\"";

		return true;
	}

	if ( $lookback == 3 )
	{
		// get the last three words from existing text
		$reg_expr = '([^ ]+)([ ]+)([^ ]+)([ ]+)([^ ]+)([ ]*)$';
		$ok = mb_eregi( $reg_expr, $new_text, $regs );
	}

	if( $ok )
	{
		$search_words_three = "\"" . $regs[1] ." " . $regs[3] ." " . $regs[5] . "\"";

		$search_words_two = "\"" . $regs[3] ." " . $regs[5] . "\"";

		return true;
	}

	// get the last two words from existing text
	$reg_expr = '([^ ]+)([ ]+)([^ ]+)([ ]*)$';
	$ok = mb_eregi( $reg_expr, $new_text, $regs );
	if( $ok )
	{
		$search_words_two = "\"" . $regs[1] ." " . $regs[3] . "\"";
		return true;
	}
	
	$search_words_two = "\"" . $words . "\"";
	
	return true;
}

/////////////////////////////////
// Bootstrap by loading the javascript buttons
function print_bootstrap_script($bootstrap_string)
{
$bootstrap_string = addslashes( $bootstrap_string );
print("
<SCRIPT type=\"text/javascript\">
<!--
	loadBootstrapString('$bootstrap_string');
-->
</SCRIPT>
");

}

//////////////////////////////////////////////////////////
// Routine that does the word suggestion and prints the input boxes
function do_word_suggest($key, $lookback, $new_text, $punctuation, &$word_string)
{
	global $mode;


	determine_search_strings( $lookback, $new_text, $search_words_four, $search_words_three, $search_words_two );

	$found_words = array();

	print_debug("sw4 = $search_words_four<br> sw3 = $search_words_three<br>sw2 = $search_words_two");

	if( !find_words( $key, $search_words_four, $search_words_three, $search_words_two, $found_words, $count ) )
	{
		if( $mode == "normal" )
		{
			print( "Error... unable to complete your request. Sorry, please try again later. I may have run out of searches for today.");
		}
	}

	$bootstrap_string = "";

	if( $mode == "normal" )
	{

		print("\n<!-- begin column table -->");
		print("<table width=\"100%\" cellpadding=\"5\" border=\"0\" cellspacing=\"1\">");
		print("<tr>");

		print("<td valign=\"top\">");

		print("
	<table border=\"0\" cellpadding=\"20\" cellspacing=\"1\" bgcolor=\"#CF9FFF\">
	  <tr>
		<td bgcolor=\"#F5EDF5\">
	  What should I type next?
		  <div id=\"input_buttons\">");

	}
  
	// the bootstrap string contains the text that will be sent back to the client
	// in RPC mode, or initialized by javascript if this is a normal HTTP session.

	$bootstrap_string = $new_text;
	
	// instruct the client to display the punctuation buttons only
	// if the last action wasn't to insert punctuation.

	if( $punctuation )
	{
		$bootstrap_string .= "|0";
	}
	else
	{
		$bootstrap_string .= "|1";
	}

	$word_string = "";
	foreach( $found_words as $word )
	{
		if( $word_string <> "" )
		{
			$word_string .= ",";
		}
		$word_string .= $word;

		$bootstrap_string .= "|$word";


		//print_word_select_button( $lookback, ereg_replace("\\\"", "\\'", $word ), $new_text );
	}

	if( $punctuation )
	{
		get_sentence_starters( $bootstrap_string );
	}	

	if( $mode == "rpc" )
	{
		print("$bootstrap_string");
	}
	else//	if( $mode == "normal" )
	{
		print("</div>"); // end of word buttons

		print("</td>
	  </tr>
	</table>");

		print("</td><td valign=\"top\">");

		print("
	<table border=\"0\" cellpadding=\"20\" cellspacing=\"1\" bgcolor=\"#CF9FFF\">
	  <tr>
		<td bgcolor=\"#F5EDF5\">");

		print("Or type your own words here:<br>");

		print("<input type='text' id='input_text' size='20' value='' onKeyDown='if(event.keyCode==13) clickTextSubmit();'>");
		print(" <input type='button' id='input_text_button' onClick='javascript:clickTextSubmit();' value='Submit'>");
		//print_input_form( $lookback, $new_text );

		print_bootstrap_script($bootstrap_string);

		print("</td>
		  </tr>
		</table>");
		  		print("<P>How many words to look back?<br>");
		print( "<input type='button' id='look_4' onclick='javascript:changeLookback(4);' value='4 words'>");
		print( "<input type='button' id='look_3' onclick='javascript:changeLookback(3);' value='3 words'>");
		print( "<input type='button' id='look_2' onclick='javascript:changeLookback(2);' value='2 words'>");

		print("
<SCRIPT LANGUAGE=\"javascript\">
<!--
	document.getElementById('look_$lookback').className = 'mybold';

-->
</SCRIPT> 
	");

		print("</td>");

		print("</tr></table>\n<!-- end column table -->\n");

	}
	return true;
}

////////////////////////////////////////////////
// Prints head and javascript
function print_head_and_javascript()
{
	global $lookback;

print("<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\"> 
<html><head><title>Google Poetry Robot</title>
<meta http-equiv=\"Content-type\" value=\"text/html; charset=UTF-8\" />
	");

print("
<SCRIPT LANGUAGE=\"javascript\">
<!--
function getRefToDiv(divID,oDoc) {
    if( !oDoc ) { oDoc = document; }
    if( document.layers ) {
        if( oDoc.layers[divID] ) { return oDoc.layers[divID]; } else {
            //repeatedly run through all child layers
            for( var x = 0, y; !y && x < oDoc.layers.length; x++ ) {
                //on success, return that layer, else return nothing
                y = getRefToDiv(divID,oDoc.layers[x].document); }
            return y; } }
    if( document.getElementById ) {
        return document.getElementById(divID); }
    if( document.all ) {
        return document.all[divID]; }
    return false;
}
function hideDiv(divID_as_a_string) {
    myReference = getRefToDiv(divID_as_a_string);
    if( !myReference ) {
        return false;
    }
    if( myReference.style ) { //DOM & proprietary DOM
        myReference.style.visibility = 'hidden';
    } else {
        if( myReference.visibility ) { //Netscape
            myReference.visibility = 'hide';
        } else {
            return false; 
        }
    }
    return true;
}
function showDiv(divID_as_a_string) {
    myReference = getRefToDiv(divID_as_a_string);
    if( !myReference ) {
        return false; 
    }
    //now we have a reference to it
    if( myReference.style ) { //DOM & proprietary DOM
        myReference.style.visibility = 'visible';
    } else {
        if( myReference.visibility ) { //Netscape
            myReference.visibility = 'show';
        } else {
            return false;
        }
    }
    return true;
}
function getScrollXY() {
  var scrOfX = 0, scrOfY = 0;
  if( typeof( window.pageYOffset ) == 'number' ) {
    //Netscape compliant
    scrOfY = window.pageYOffset;
    scrOfX = window.pageXOffset;
  } else if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
    //DOM compliant
    scrOfY = document.body.scrollTop;
    scrOfX = document.body.scrollLeft;
  } else if( document.documentElement &&
      ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) {
    //IE6 standards compliant mode
    scrOfY = document.documentElement.scrollTop;
    scrOfX = document.documentElement.scrollLeft;
  }
  return [ scrOfX, scrOfY ];
}
function showPleaseWait() {
	showDiv('pleaseWait');
}
function hidePleaseWait() {
	hideDiv( 'pleaseWait' );
}
function createRequestObject() {
    var ro;
    var browser = navigator.appName;
    if(browser == \"Microsoft Internet Explorer\"){
        ro = new ActiveXObject(\"Microsoft.XMLHTTP\");
    }else{
        ro = new XMLHttpRequest();
    }
    return ro;
}

var http = createRequestObject();

var showpunctuation = '0';

var lookback = $lookback;

var text = '';

var dopublish = false;

  function sndReqArg(text,words,punctuation) {
    showPleaseWait();
	var publishstring = '';
	if( dopublish ) {
		publishstring = \"&dopublish=1\";
		dopublish = false;
	}
	text = encodeURIComponent(text);
	words = encodeURIComponent(words);
	http.open('get', 'autotype.php?mode=rpc'+publishstring+'&text='+text+'&words='+words+'&punctuation='+punctuation+'&lookback='+lookback);
    http.onreadystatechange = handleResponse;
    http.send(null);
  }
function handleResponse() {
    if(http.readyState == 4){
        var response = http.responseText;

		loadBootstrapString( response );		
		hidePleaseWait();
    }
}
function doPublishNow() {
dopublish = true;
sndReqArg(text,'','');
}
function loadBootstrapString( response ) {
        var update = new Array();

        if(response.indexOf('|') != -1) {
            update = response.split('|');
			text = update[0];
            document.getElementById('currentText').innerHTML = text;
			
			showpunctuation = update[1];
		
			clearWordButtons();
			for( x = 2; x < update.length; x++ )
			{
				addWordButton( update[x] );
			}

			if( showpunctuation == '1' ) {
                                addPunctuationButton(',', ', (insert comma)');
				addPunctuationButton('.', '. (insert period)');
				addPunctuationButton('!', '! (insert exclamation mark)');
				addPunctuationButton('?', '? (insert question mark)');			
			}
			display();
        }
		else if(response.indexOf('published') != -1)
		{
			text = '';
			document.getElementById('currentText').innerHTML = 'Thank you. Your sentence was submitted to the <a href=\"http://234989h39h.blogspot.com/\">blog</a>. It should be posted in the next 8 to 48 hours.';
			clearWordButtons();
			display();
			scrollTo(0,0);

		}

}

function changeLookback(val) {
	for( x=2; x<=4; x++ ) {
		document.getElementById('look_' + x).className = 'mynormal';
	}

	document.getElementById('look_' + val).className = 'mybold';

	lookback=val;
	sndReqArg(text,'','');
}

var arrInput = new Array(0);
var arrInputValue = new Array(0);
var arrInputLabel = new Array(0);
var arrInputIsPunctuation = new Array(0);

var inputText = '';

function addPunctuationButton(symbol, description) {
  arrInput.push(arrInput.length);
  arrInputValue.push(symbol);
  arrInputLabel.push(description);
  arrInputIsPunctuation.push('1');
}
function addWordButton(word) {
  arrInput.push(arrInput.length);
  arrInputValue.push(' ' + word);
  arrInputLabel.push('   ' + word + '   ');
  arrInputIsPunctuation.push('0');
}

function display() {
  document.getElementById('input_buttons').innerHTML=\"\";
  for (intI=0;intI<arrInput.length;intI++) {
    document.getElementById('input_buttons').innerHTML+=createButton(arrInput[intI], arrInputLabel[intI]);
  }

  // clear the input text box
  document.getElementById('input_text').value = '';
  
}

function clickWord(intId) {
	sndReqArg( text + arrInputValue[intId], '', arrInputIsPunctuation[intId] );
}  
function clickTextSubmit() {
	sndReqArg( text, document.getElementById('input_text').value, '' );
}
function createButton(id,label) {
  return \"<input type='button' id='word_\"+ id +\"' onClick='javascript:clickWord(\"+ id +\")' value=\" + '\"' +  label.replace('\"', '&quot;') + '\"' + \"><br>\";
}

function clearWordButtons() {
  while (arrInput.length > 0) { 
     deleteInput();
  }
  display(); 
}

function deleteInput() {
  if (arrInput.length > 0) { 
     arrInput.pop(); 
     arrInputValue.pop();
	 arrInputLabel.pop();
	 arrInputIsPunctuation.pop();
  }
  display(); 
}

-->
</SCRIPT> 
");

print("		<link rel=\"stylesheet\" type=\"text/css\" href=\"autostyle.css\">");

print("</head>");

}
///////////////////////////////////////////

///////////////////////////////////////////
// Prints main table start
function print_main_table_start()
{

print("
<body bgcolor=\"#F3F3F3\">  
");
	print("<div id=\"pleaseWait\" class=\"hidDiv\">
	&nbsp;<br><p><b>Please Wait...</b></p></div>");

print("
<div align=\"center\">
  <center>
<table border=\"0\" cellpadding=\"0\" width=\"725\" cellspacing=\"0\">
  <tr>
    <td>
      <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">
        <tr>
          <td bgcolor=\"#D7D7D7\" align=\"left\">
          &nbsp;<font face=\"Verdana\">&nbsp;<font size=\"5\">Google Poetry Robot
            </font></font>

          </td>
        </tr>
        <tr>
          <td align=\"left\">
                      <table border=\"0\" cellpadding=\"0\" cellspacing=\"1\" width=\"100%\" bgcolor=\"#E9A6AC\">
              <tr>
              <td>
            <table border=\"0\" cellpadding=\"0\" cellspacing=\"5\" width=\"100%\" bgcolor=\"#FFFFFF\">
              <tr>
                <td>
                  <table border=\"0\" cellpadding=\"30\" cellspacing=\"1\" bgcolor=\"#FFFFFF\" width=\"100%\">
                    <tr>
                      <td bgcolor=\"#FFFFFF\">
");

}

/////////////////////////////////////
// Prints the end of the page.
function print_page_end()
{

print("
<p>
	<script type=\"text/javascript\"><!--
google_ad_client = \"pub-0935994156181230\";
google_alternate_color = \"FFFFFF\";
google_ad_width = 468;
google_ad_height = 60;
google_ad_format = \"468x60_as\";
google_ad_type = \"text_image\";
google_ad_channel =\"\";
google_color_border = \"FFFFFF\";
google_color_bg = \"FFFFFF\";
google_color_link = \"0000FF\";
google_color_text = \"000000\";
google_color_url = \"008000\";
//--></script>
<script type=\"text/javascript\"
  src=\"http://pagead2.googlesyndication.com/pagead/show_ads.js\">
</script>
	  </p>
<p>Google Poetry Robot (BETA) is by <a href=\"http://www.sfu.ca/~gpeters/\">Geoff Peters</a>. Currently just an experiment, but I would appreciate <a href=\"mailto:gpeters@sfu.ca\">your feedback</a>!<p>
<p>&nbsp;</p>
<p>To read what some people have been creating using this system, visit the <a href=\"http://234989h39h.blogspot.com/\">Robot Poetry Blog</a>.</p>
<p>I put a bunch of auto-generated Google Poems into this <a href=\"http://www.gpeters.com/google-poem-gallery/\">Online Google Poem Gallery</a>.</p>
<p>To listen to robot voices reading some of these poems, please visit <a href=\"http://www.robotpoetry.com/\">RobotPoetry.com</a>.</p>
<p>Warning: The Poetry Robot may generate potentially offensive words. User discretion is advised.</p>
<p><b>How does it work?</b> This program uses the <a href=\"http://www.google.com\">Google Search Engine</a> to determine some of the most common words that might make sense to complete your sentence. It looks at the last few words of what you typed to determine some words that should come next.</P>
<p>Multiple Languages: You can try any language with Roman-based characters (such as French, German, Italian, Spanish, etc.) by typing words into the box. It can now handle accented characters.</p>
<p>Discussion: Do you find the system somewhat intelligent? Maybe it is a form of Artificial Intelligence, in a limited way? Let me know what you think.</p>
<p>&nbsp;</p>
<p>Please note, that while this web site uses data obtained from Google through the Google API, it is in no way affiliated with Google Inc.</p>
<p>&nbsp;</p>
<a href=\"http://www.gpeters.com/happy/mood-health.php\"><img border=\"0\" src=\"http://www.gpeters.com/happy/mood-health.php?report=happyimage\" alt=\"How Happy is everyone today? Click to find out.\"></a>
<p>&nbsp;</p><center>Copyright (c) 2006 <a href=\"http://www.sfu.ca/~gpeters/\">Geoff Peters</a>.
</center>
                      <!-- end main content -->
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
            
              </td>
              </tr>
            </table>            
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</div>
</body>
</html>");
}

///////////////////////////////////////////
function get_request_param( $name )
{
	if ( isset($_REQUEST[$name]) )
	{
		return $_REQUEST[$name];
	}
	else
	{
		return "";
	}
}


$key = get_request_param('key');
$mode = get_request_param('mode');
$text = get_request_param('text');
$words = get_request_param('words');
$lookback = get_request_param('lookback');
$dopublish = get_request_param('dopublish');
$punctuation = get_request_param('punctuation');


if ( $key == "" )
{
	// enter your google SOAP API key here
	$key = 'thisisnotarealkey9234h2qG';
}

if( $mode <> "rpc" )
{
	$mode = "normal";
}

// remove tags from text
$text = ereg_replace("\\\"", "\\'", trim(stripslashes(ereg_replace("<[^>]*>","",$text)))); 
$words = ereg_replace("\\\"", "\\'", trim(stripslashes(ereg_replace("<[^>]*>","",$words)))); 

// set how many words to look back
if( $lookback == "" || $lookback < 2 || $lookback > 4 )
{
	$lookback = 3; // default lookback
}

if( $mode == "normal" )
{
	print_head_and_javascript();

	print_main_table_start();

	print("<p><div id=\"currentText\">$text $words</div></b></div>");

	print("<p>&nbsp;</p>");
	/*print("<div id=\"pleaseWait\" class=\"hidDiv\" style=\"position: absolute; left: 50px; top: 120px;\">
	&nbsp;<br><p><b>Please Wait...</b></p></div>");*/
}

$new_text = "";

if( $words == "" && $text == "" )
{
	if( $mode=="normal")
	{

print("<table cellpadding=\"10\" cellspacing=\"0\" border=\"0\" width=\"100%\"><tr><td valign=\"top\">");
		print("
<p>
The Poetry Robot will help you write poems, suggesting words from Google. Type a few words to get started, and click submit.
 </p>");

		print_input_form( $lookback, $text );

		print("<br>For example, try entering <b>I dream to</b> and then click on Submit.<p>&nbsp;</p>
		<p>&nbsp;</p><p><b>Where do the suggested words come from?</b> The words are taken from other web pages which rank highly on the Google search engine.</p><p>Update: April 11th, 2008 - I added a button to insert commas in the poems.</p>");
		//print(" <p>Or click one of the suggestions below:</p>");
		//print_sentence_starters($lookback);

		print("</td><td nowrap valign=\"top\"><table bgcolor=\"#CEF2E0\" cellpadding=\"10\" cellspacing=\"0\" border=\"1\"><tr><td nowrap>
		<b>Example poem</b> \"Here in Canada\":
		<p>Mooing is more than just Breathing. <br>
Clucking is sooo out of date.<br>
Laughing is Healthy and crying is ignored but why?<br>
I believe breathing is illegal here in Canada.<br>
Writing the right words is always welcomed graciously<br>
but those who believe that human wisdom<br>
can do away with nationalism and religious beliefs<br>
are truly inspiring but severely deranged.<br>
			&nbsp;<br>
			-Geoff Peters and the Google Poetry Robot, 2006<br>
			Published in the May 2006 issue of <a href=\"http://www.sfu.ca/~hapoetry/\">High<br>
			Altitude Poetry</a>.</p>
			<p>It works with <b>other languages</b> too.<br>Example poem \"Je mange\":</p>
		<p>Je mange parce que<br>
j'ai manqu&eacute; d'argent.<br>
Je mange donc je maigris<br>
et je reste mince<br>
avec des plantes carnivores.</p>
			<p><a href=\"http://234989h39h.blogspot.com/\">More examples...</a></p>
</td></tr></table>
		</td></tr></table>");
	}
	else
	{
		print("error");
		exit();
	}
}
else
{

	$new_text = trim("$text $words");

	if( $new_text <> "" && $dopublish == "1" )
	{
		if( $mode == "rpc" )
		{
			save_for_publish( $new_text );
			print("published");
			exit();
		}

		if( ! save_for_publish( $new_text ) )
		{	
			print("<P>Sorry, cannot publish your text now. Please try again later.</p>");
		}	
		else
		{
			print("
<P>Thank you, we received your submission. It will be posted on the <a href=\"http://234989h39h.blogspot.com/\">blog</a> in 8 to 48 hours. Thanks for your patience.</p>");
		}
		
		print("<br>Please type the first few words of a sentence:");
		print_input_form( $lookback, "" );

		//print(" <p>Or click one of the suggestions below:</p>");
		//print_sentence_starters($lookback);

	}
	else
	{

		$last_char = $new_text[ count($new_text) ];
		if( $last_char != "." && $last_char != "?" && $last_char != "!")
		{
			do_word_suggest($key, $lookback, $new_text, $punctuation, $word_string);
		}
	
		if( $mode == "normal" && $new_text <> "" )
		{

			  print("
			  	&nbsp;<br>
			<table border=\"0\" width=\"200\" cellpadding=\"20\" cellspacing=\"1\" bgcolor=\"#000000\">
			  <tr>
				<td bgcolor=\"#FFFFFF\">");

				print("<p>Is your poem interesting? Want to share it with others? You can publish it on the Robot Poetry blog.</p>");

	print("<p><input type=\"button\" onclick=\"javascript:doPublishNow()\" value=\"Publish to Blog\"></P>
");

				print("</td>
			  </tr>
			</table>");
		}

		$file = fopen("autotype-results.txt","a");

		if( $file )
		{  fputs( $file, $_SERVER['REMOTE_ADDR']."," . date("M j G:i:s T Y") . ",$word_string\n$new_text\n" );

		   fclose( $file );
		}
		else
		{	if( $mode == "normal" )
			{
				print( "<!-- Could not open results.txt for append. -->");
			}
		}
	}

	if( $mode == "normal" )
	{
		print("<p>&nbsp;</p>
		<form method=\"POST\" action=\"autotype.php\">
		  <input type=\"submit\" value=\"   Start Over    \">
		  <input name=\"text\" type=\"hidden\" value=\"\">
		</form>");


	}
}

if( $mode == "normal" )
{

	print_page_end();

}

//////////////////////////////////////////////////////////////
?>
