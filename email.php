<?php
if(php_sapi_name()!="cli")
	exit(http_response_code(403));
// Read the ini file
$inifile = '/data/project/certify/oauth.ini';
$ini = parse_ini_file( $inifile );
if ( $ini === false ) {
	//header( "HTTP/1.1 $errorCode Internal Server Error" );
	echo 'The ini file could not be read';
	exit(0);
}
if(!isset($ini['gAgent']) ||
!isset( $ini['gConsumerKey'] ) ||
 !isset( $ini['gConsumerSecret'] )
){
	//header( "HTTP/1.1 $errorCode Internal Server Error" );
	echo 'Required configuration directives not found in ini file';
	exit(0);
}
$gUserAgent = $ini['gAgent'];
$gConsumerKey = $ini['gConsumerKey'];
$gConsumerSecret = $ini['gConsumerSecret'];
/*Fetch who initiated it */
if($argc==1)
	exit("No initiator given");
/*Fetch from database*/
$host = "tools.db.svc.eqiad.wmflabs";
$creds = parse_ini_file("/data/project/certify/replica.my.cnf");
$conn = mysqli_connect($host,$creds['user'],$creds['password'],'s54548__certify');
$sql = "SELECT Users.Token_Key AS K, Users.Token_Secret AS S, Queue.Task AS T, Queue.ID AS I FROM Users JOIN Queue WHERE Users.Username = '".addslashes($argv[1])."' AND Users.Username = Queue.Initiator AND Queue.Type = 1 AND Queue.Status = 0";
$res = $conn->query($sql);
echo mysqli_error($conn);
if(!($res = $res->fetch_assoc()))
	exit("No task is Defined");
eval('$mail_list = '.$res["T"].';');
$gTokenKey = $res["K"];
$gTokenSecret= $res["S"];
$id = $res["I"];
//Update the Status
$sql = "UPDATE Queue SET Status = 1 WHERE ID = $id";
$conn->query($sql);
$mwOAuthUrl = 'https://meta.wikimedia.org/w/index.php?title=Special:OAuth';
$mwOAuthIW = 'meta';

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
		exit(0);
	}
	return $ret;
}
function fetch_token(){
	$data = [
	"action"=> "query",
	"format"=> "json",
	"meta"=> "tokens",
	"type"=> "csrf"
	];
	$res = doApiQuery($data)["query"]["tokens"]["csrftoken"];
	return $res=="+\\"?NULL : $res;
	
}
$token = fetch_token();
foreach($mail_list as $v){
	send:
	$data = [
	"action"=> "emailuser",
	"format"=> "json",
	"target"=> $v[0],
	"subject"=> $v[1],
	"text"=> $v[2],
	"token"=> $token
	];
	$res = doApiQuery($data);
	if(isset($res["error"])){
		if($res["error"]["code"]=="badtoken")
			{
				$token = fetch_token();
				goto send;
			}
		echo $res["info"];
	}
	else
		echo "Sent to :".$v[0];
}
$sql = "UPDATE Queue SET Status = 2 WHERE ID = $id";
$conn->query($sql);
?>