<?php
session_start();
if(!isset($_SESSION['consumer']) || !isset($_SESSION['user']))
	header("Location:login.php");
if(!isset($_GET['oauth_verifier']) ||! isset($_GET['oauth_token']))
	header("Location: index.php");
$gConsumerKey = $_SESSION['consumer']['Key'];
$gConsumerSecret = $_SESSION['consumer']['Secret'];
$gUserAgent = $_SESSION['consumer']['Agent'];
$gTokenKey = $_SESSION['tokenKey'];
$gTokenSecret=$_SESSION['tokenSecret'];
/**
 * Set this to the Special:OAuth URL. 
 * Note that /wiki/Special:OAuth fails when checking the signature, while
 * index.php?title=Special:OAuth works fine.
 */
$mwOAuthUrl = 'https://meta.wikimedia.org/w/index.php?title=Special:OAuth';

/**
 * Set this to the interwiki prefix for the OAuth central wiki.
 */
$mwOAuthIW = 'meta';
/**
 * Handle a callback to fetch the access token
 * @return void
 */
 /**
 * Utility function to sign a request
 *
 * Note this doesn't properly handle the case where a parameter is set both in 
 * the query string in $url and in $params, or non-scalar values in $params.
 *
 * @param string $method Generally "GET" or "POST"
 * @param string $url URL string
 * @param array $params Extra parameters for the Authorization header or post 
 * 	data (if application/x-www-form-urlencoded).
 * @return string Signature
 */
 function sign_request( $method, $url, $params = array() ) {
 global $gConsumerSecret, $gTokenSecret;
 $parts = parse_url( $url );
 // We need to normalize the endpoint URL
 $scheme = isset( $parts['scheme'] ) ? $parts['scheme'] : 'http';
 $host = isset( $parts['host'] ) ? $parts['host'] : '';
 $port = isset( $parts['port'] ) ? $parts['port'] : ( $scheme == 'https' ? '443' : '80' );
 $path = isset( $parts['path'] ) ? $parts['path'] : '';
 if ( ( $scheme == 'https' && $port != '443' ) ||
 ( $scheme == 'http' && $port != '80' ) 
 ) {
 // Only include the port if it's not the default
 $host = "$host:$port";
 }
 
 // Also the parameters
 $pairs = array();
 parse_str( isset( $parts['query'] ) ? $parts['query'] : '', $query );
 $query += $params;
 unset( $query['oauth_signature'] );
 if ( $query ) {
 $query = array_combine(
 // rawurlencode follows RFC 3986 since PHP 5.3
 array_map( 'rawurlencode', array_keys( $query ) ),
 array_map( 'rawurlencode', array_values( $query ) )
 );
 ksort( $query, SORT_STRING );
 foreach ( $query as $k => $v ) {
 $pairs[] = "$k=$v";
 }
 }
 
 $toSign = rawurlencode( strtoupper( $method ) ) . '&' .
 rawurlencode( "$scheme://$host$path" ) . '&' .
 rawurlencode( join( '&', $pairs ) );
 $key = rawurlencode( $gConsumerSecret ) . '&' . rawurlencode( $gTokenSecret );
 return base64_encode( hash_hmac( 'sha1', $toSign, $key, true ) );
 }

function fetch($action='/token') {
	global $mwOAuthUrl, $gUserAgent, $gConsumerKey, $gTokenKey, $gTokenSecret, $errorCode;
	$url = $mwOAuthUrl . $action;
	$url .= strpos( $url, '?' ) ? '&' : '?';
	$url .= http_build_query( array(
		'format' => 'json',
		'oauth_verifier' => $_GET['oauth_verifier'],

		// OAuth information
		'oauth_consumer_key' => $gConsumerKey,
		'oauth_token' => $gTokenKey,
		'oauth_version' => '1.0',
		'oauth_nonce' => md5( microtime() . mt_rand() ),
		'oauth_timestamp' => time(),

		// We're using secret key signatures here.
		'oauth_signature_method' => 'HMAC-SHA1'
	) );
	$signature = sign_request( 'GET', $url );
	$url .= "&oauth_signature=" . urlencode( $signature );
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $url );
	//curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
	curl_setopt( $ch, CURLOPT_USERAGENT, $gUserAgent );
	curl_setopt( $ch, CURLOPT_HEADER, 0 );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	$data = curl_exec( $ch );
	if ( !$data ) {
	//	header( "HTTP/1.1 $errorCode Internal Server Error" );
		echo 'Curl error: ' . htmlspecialchars( curl_error( $ch ) );
	//	exit(0);
	}
	curl_close( $ch );
	echo "Retrieving $action<br/>";
	$token = json_decode( $data, true);
	// Save the access token
	$_SESSION['tokenKey'] = $gTokenKey = $token['key'];
	$_SESSION['tokenSecret'] = $gTokenSecret = $token['secret'];
}
fetch('/token');
//fetch User info
$data = [
	'action'=>'query',
	'meta'=>'userinfo',
	'format'=>'json',
	'utf8'=>true
];
$ch = curl_init();
$apiUrl = 'https://bn.wikipedia.org/w/api.php';
/**
 * Send an API query with OAuth authorization
 *
 * @param array $post Post data
 * @param object $ch Curl handle
 * @return array API results
 */
function doApiQuery( $post, &$ch = null ) {
	global $apiUrl, $gUserAgent, $gConsumerKey, $gTokenKey, $errorCode;

	$headerArr = array(
		// OAuth information
		'oauth_consumer_key' => $gConsumerKey,
		'oauth_token' => $gTokenKey,
		'oauth_version' => '1.0',
		'oauth_nonce' => md5( microtime() . mt_rand() ),
		'oauth_timestamp' => time(),

		// We're using secret key signatures here.
		'oauth_signature_method' => 'HMAC-SHA1',
	);
	$signature = sign_request( 'POST', $apiUrl, $post + $headerArr );
	$headerArr['oauth_signature'] = $signature;

	$header = array();
	foreach ( $headerArr as $k => $v ) {
		$header[] = rawurlencode( $k ) . '="' . rawurlencode( $v ) . '"';
	}
	$header = 'Authorization: OAuth ' . join( ', ', $header );

	if ( !$ch ) {
		$ch = curl_init();
	}
	curl_setopt( $ch, CURLOPT_POST, true );
	curl_setopt( $ch, CURLOPT_URL, $apiUrl );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $post ) );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, array( $header ) );
	//curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
	curl_setopt( $ch, CURLOPT_USERAGENT, $gUserAgent );
	curl_setopt( $ch, CURLOPT_HEADER, 0 );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	$data = curl_exec( $ch );
	if ( !$data ) {
		//header( "HTTP/1.1 $errorCode Internal Server Error" );
		echo 'Curl error: ' . htmlspecialchars( curl_error( $ch ) );
		//exit(0);
	}
	$ret = json_decode( $data, true);
	if ( $ret == []) {
	//	header( "HTTP/1.1 $errorCode Internal Server Error" );
		echo 'Unparsable API response: <pre>' . htmlspecialchars( $data ) . '</pre>';
		//exit(0);
	}
	return $ret;
}
$res = doApiQuery($data, $ch);
curl_close($ch);
$_SESSION['user']=[
	'name'=>$res['query']['userinfo']
];
session_write_close();
var_dump($_SESSION);
$return = isset($_SESSION['return'])?urldecode($_SESSION['return']):'index.php';
//header("Location: $return");

?>