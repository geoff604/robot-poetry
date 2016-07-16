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
ini_set( 'include_path', '/var/www/vhosts/gpeters.com/library:/var/www/vhosts/googleduel.com/httpdocs:.:/usr/share/pear');


// load the MYSQL Database
require("../autotypedb.php");

require_once 'Zend/Gdata.php';
require_once 'Zend/Gdata/ClientLogin.php';

header('Content-type: text/html; charset=UTF-8') ;


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
?>
<html>

<head>
<title>Auto Blogger</title>
<meta http-equiv="Content-type" value="text/html; charset=UTF-8" />

<script language="JavaScript">
function chkAll(frm, arr, mark) {
  for (i = 0; i <= frm.elements.length; i++) {
   try{
     if(frm.elements[i].name == arr) {
       frm.elements[i].checked = mark;
     }
   } catch(er) {}
  }
}
</script>

<?


/* 
Autotype_tbl

AutotypeID
Approval   U - unapproved..    A - approved but not blogged  D - Done and blogged  B - bad and not blogged
Content
*/

function publish_blog_post( $AutotypeID, $postcontent )
{

	try 
	{
		$email = 'email@sample.com';
		$passwd = 'passwordgoeshere';
		$client = Zend_Gdata_ClientLogin::getHttpClient($email, $passwd, 'blogger');

		$gdata = new Zend_Gdata($client);
		$entry = $gdata->newEntry();
	//	$entry->title = $gdata->newTitle('Playing football at the park');
		$content = $gdata->newContent($postcontent);
		$content->setType('text');
		$entry->content = $content;
		$entryResult = $gdata->insertEntry($entry,         'http://www.blogger.com/feeds/345235235/posts/default');

		$blogger_id = $entryResult->id->text;
		echo "\n<br>Posted $AutotypeID with blogger id $blogger_id";

		$query = "UPDATE Autotype_tbl SET Approval='D', PostID='$blogger_id' WHERE AutotypeID = '$AutotypeID'";
		$result = mysql_query($query);
		if (mysql_error())
		{
			print( "ID = $AutoTypeID, Post = $postid - MySQL Error on update: " . mysql_error() . "<p>");
			exit();
		}
	} 
	catch(Zend_Gdata_App_Exception $ex) 
	{    
		// Report the exception to the user    
		print("<br>Error: " . $ex->getMessage());
		return false;
	}
	return true;
}

///////////////////////////////
// Publishes a blog post with the given ID.
function publish_blog_post_old( $AutotypeID, $content )
{
	
	return true;
}

//////////////////////////////////////////
function doPostingProcess()
{
	$query = "select a.AutotypeID, a.Content FROM Autotype_tbl a WHERE a.Approval = 'A'";
	$result = mysql_query($query);
	if (mysql_error())
	{
		print( "MySQL Error: " . mysql_error() . "<p>");
		return false;
	}

	print("<P>Publishing: ");
	while ($myrow = mysql_fetch_row($result)) 
	{
		print("$myrow[0] ");

		if( ! publish_blog_post( $myrow[0], $myrow[1] ) )
		{
			print("<p>Error, aborted.");
			return false;
		}
	}
	print("...done!");
	return true;
}

//////////////////////////////////////////
function approve( $id )
{
$query = "UPDATE Autotype_tbl SET Approval='A' WHERE AutotypeID = '$id'";
		$result = mysql_query($query);
		if (mysql_error())
		{
			print( "MySQL Error 2: " . mysql_error() . "<p>");
			exit();
		}
		return true;
}

//////////////////////////////////////////
function reject( $id )
{
$query = "UPDATE Autotype_tbl SET Approval='B' WHERE AutotypeID = '$id'";
		$result = mysql_query($query);
		if (mysql_error())
		{
			print( "MySQL Error 3: " . mysql_error() . "<p>");
			exit();
		}
		return true;
}

$dopost = get_request_param('dopost');

$approved = get_request_param('approved');
$bad = get_request_param('bad');

if( $dopost == "1" )
{
	doPostingProcess();
}

// do approvals
for( $i=0; $i<count($approved); $i++ )
{
approve( $approved[$i] );
}

// do rejections
for( $i=0; $i<count($bad); $i++ )
{
reject( $bad[$i] );
}
print("</head>
<body>
");
print("<p><a href=\"autoblog.php?dopost=1\">Do posting now.</a></p>");
print("<p><a href=\"http://234989h39h.blogspot.com/\">Blog</a></p>");
print("<p><a href=\"http://www.gpeters.com/auto/autotype.php\">Main - manual</a></p>");

print("<br>".count($approved). " posts approved.<br>");
print("<br>".count($bad). " posts rejected.<br>");

$query = "Select count(*) from Autotype_tbl where Approval = 'U'";
 $result = mysql_query($query);

if($myrow = mysql_fetch_row($result))
{
print( $myrow[0] . " left to approve, have fun! :)<br>\n" );

}


?>

<p>Name approval page</p>
<form name="approveFrm" method="POST" action="autoblog.php">



<input type="checkbox" onClick="chkAll(this.form, 'approved[]', this.checked)">
Check all Good<br><br>

<input type="checkbox" onClick="chkAll(this.form, 'bad[]', this.checked)">
Check all Bad<br>

 <table border="1" cellpadding="2" >
    <tr>
      <td >Bad?</td>
      <td >Good?</td>
      <td >Post</td>
    </tr>
 

<?
// do query to get items which are not yet approved/rejected

	$query = "select a.AutotypeID, a.Content FROM Autotype_tbl a WHERE a.Approval = 'U' LIMIT 40";
	$result = mysql_query($query);
	if (mysql_error())
	{
		print( "MySQL Error: " . mysql_error() . "<p>");
		return false;
	}

	while ($myrow = mysql_fetch_row($result)) 
	{
		print("<tr><td><input type='checkbox' name='bad[]' value='" . $myrow[0] ."'></td>");
		print("<td><input type='checkbox' name='approved[]' value='" . $myrow[0] ."'></td>");
		print("<td>". $myrow[1] ."</td></tr>");
	}
?>
  </table>
  <p><input type="submit" value="Submit"><input type="reset" value="Reset"></p>
</form>



</body>

</html>
