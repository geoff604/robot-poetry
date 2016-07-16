<?
// googledb.php
// Establish a database connection.

$mysql_host = 'localhost';
$mysql_login = '';  // fill in your username and password here
$mysql_password = '';
$mysql_database = 'autotype_db';

$c = mysql_pconnect ( $mysql_host, $mysql_login, $mysql_password );
if ( ! $c ) {
  echo "Sorry, had an error connecting to the database. The server appears to be down. Please try again later.<P>";
  exit;
}

if ( ! mysql_select_db ( $mysql_database ) ) {
  echo "Error selecting \"$mysql_database\" database!<P>" . mysql_error ();
  exit;
}
?>
