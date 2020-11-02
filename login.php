<?php 
session_start();
$return = urldecode((isset($_REQUEST['return'])&&$_REQUEST['return'])?$_REQUEST['return'] : 'index.php');
   	if(isset($_REQUEST['confirm'])){
   		if(isset($_REQUEST['logout'])){
   			session_unset();//Delete the session
   			//Delete the cookie
   			goto login;
   			
   		}
   		else{
   		/****** First check if loginned yet ****/
   		if(isset($_SESSION['user'])){
   			/*** Already Logged in***/
   			header("Location: $return");
   		}
   		/***Not logged in**/
   		if(!isset($_SESSION['consumer']))
   		{ //Session has no data about this secret
   			// Read the ini file
   			$inifile = '../oauth.ini';
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
	   		$_SESSION['consumer'] = [
	   			'Key'=> $gConsumerKey,
	   			'Secret'=> $gConsumerSecret,
	   			'Agent'=> $gUserAgent
	   		];
   		}else{//fetch from session
   			$gUserAgent = $_SESSION['consumer']['Agent'];
   			$gConsumerKey = $_SESSION['consumer']['Key'];
   			$gConsumerSecret = $_SESSION['consumer']['Secret'];
   		}
   		// Load the user token (request or access) from the session
   		$gTokenKey = '';
   		$gTokenSecret = '';
   		/**
   		* Set this to the Special:OAuth/authorize URL. 
   		* To work around MobileFrontend redirection, use /wiki/ rather than /w/index.php.
   		*/
   		$mwOAuthAuthorizeUrl = 'https://meta.wikimedia.org/wiki/Special:OAuth/authorize';
   		
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
   		$errorCode = 200;
   		function sign_request( $method, $url, $params = array() ) {
   			global $gConsumerSecret, $gTokenSecret;
   			$parts = parse_url($url);
   			// We need to normalize the endpoint URL
   			$scheme = isset($parts['scheme']) ? $parts['scheme'] : 'http';
   			$host = isset($parts['host']) ? $parts['host'] : '';
   			$port = isset($parts['port']) ? $parts['port'] : ($scheme == 'https' ? '443' : '80');
   			$path = isset($parts['path']) ? $parts['path'] : '';
   			if (($scheme == 'https' && $port != '443' ) ||
   				($scheme == 'http' && $port != '80' ) 
   			){
   				// Only include the port if it's not the default
   				$host = "$host:$port";
   			}
   			// Also the parameters
   			$pairs = array();
   			parse_str( isset( $parts['query'] ) ? $parts['query'] : '', $query );
   			$query += $params;
   			unset($query['oauth_signature']);
   			if($query){
   				$query = array_combine(
   				// rawurlencode follows RFC 3986 since PHP 5.3
   					array_map( 'rawurlencode', array_keys( $query ) ),
   					array_map( 'rawurlencode', array_values( $query ) )
   				);
   			ksort($query,SORT_STRING );
   			foreach ($query as $k => $v)
   				$pairs[] = "$k=$v";
   			}
   			$toSign = rawurlencode( strtoupper( $method ) ) . '&' .
   			rawurlencode( "$scheme://$host$path" ) . '&' .
   			rawurlencode( join( '&', $pairs ) );
   			$key = rawurlencode( $gConsumerSecret ) . '&' . rawurlencode( $gTokenSecret );
   			return base64_encode( hash_hmac( 'sha1', $toSign, $key, true ) );
   		}
   		/**End signature function**/
   		/**
   		* Request authorization
   		* @return void*/
   		// First, we need to fetch a request token.
   		// The request is signed with an empty token secret and no token key.
   		$gTokenSecret = '';
   		$url = $mwOAuthUrl . '/initiate';
   		$url .= strpos( $url, '?' ) ? '&' : '?';
   		$url .= http_build_query( array(
   		'format' => 'json',
   		
   		// OAuth information
   		'oauth_callback' => 'https://goodarticlebot.toolforge.org/authorize.php', // Must be "oob" or something prefixed by the configured callback URL
   		'oauth_consumer_key' => $gConsumerKey,
   		'oauth_version' => '1.0',
   		'oauth_nonce' => md5( microtime() . mt_rand() ),
   		'oauth_timestamp' => time(),
   		
   		// We're using secret key signatures here.
   		'oauth_signature_method' => 'HMAC-SHA1',
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
   		exit(0);
   		}
   		curl_close( $ch );
   		$token = json_decode( $data );
   		if ( is_object( $token ) && isset( $token->error ) ) {
   	//	header( "HTTP/1.1 $errorCode Internal Server Error" );
   		echo 'Error retrieving token: ' . htmlspecialchars( $token->error ) . '<br>' . htmlspecialchars( $token->message );
   		exit(0);
   		}
   		if ( !is_object( $token ) || !isset( $token->key ) || !isset( $token->secret ) ) {
   	//	header( "HTTP/1.1 $errorCode Internal Server Error" );
   		echo 'Invalid response from token request';
   		exit(0);
   		}
   		
   		// Now we have the request token, we need to save it for later.
   		$_SESSION['tokenKey'] = $token->key;
   		$_SESSION['tokenSecret'] = $token->secret;
   		session_write_close();
   		
   		// Then we send the user off to authorize
   		$url = $mwOAuthAuthorizeUrl;
   		$url .= strpos( $url, '?' ) ? '&' : '?';
   		$url .= http_build_query( array(
   		'oauth_token' => $token->key,
   		'oauth_consumer_key' => $gConsumerKey,
   		) );
   		header( "Location: $url" );
   		echo 'Please see <a href="' . htmlspecialchars( $url ) . '">' . htmlspecialchars( $url ) . '</a>';
   		}
   	}elseif(isset($_REQUEST['logout'])){
   ?>
  	 <script>window.location = confirm('Do You want to logout?')?'login.php?logout&confirm&return=<?php echo $return;?>' : '<?php echo urldecode($return);?>';</script>
   <?php
   	}
   	else{
   	login:
   		?>
<link rel="stylesheet" href="Styles/font.css"/>
<style>
#login
{
top:calc(43% - 24px);
height:7%;
left:calc((30% - 24px)/2);
width:70%;
padding:30px;
position:absolute;
text-decoration:none;
background:green;
background-opacity:0.5;
color:white;
border:2px solid green;
border-radius:50px;
font-size:400%;
text-align:center;
}
</style>
<a href="login.php?confirm&return=<?php echo $return;?>"<button id="login">প্রবেশ করুন</button></a>
<?php
}
?>