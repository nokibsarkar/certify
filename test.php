<?php 

$config = array(
    "digest_alg" => "sha512",
    "private_key_bits" => 4096,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
);
   
// Create the private and public key
$res = openssl_pkey_new($config);

// Extract the private key from $res to $privKey
openssl_pkey_export($res, $privKey);

// Extract the public key from $res to $pubKey
$pubKey = openssl_pkey_get_details($res);
$pubKey = $pubKey["key"];
$fp = fopen("public.pem","w");
file_put_contents($fp,$pubKey);
fclose($fp);
$fp = fopen("../private.pem","w");
file_put_contents($fp,$privKey);
fclose($fp);
$data = 'plaintext data goes here';

// Encrypt the data to $encrypted using the public key
openssl_public_encrypt($data, $encrypted, $pubKey);

// Decrypt the data using the private key and store the results in $decrypted
openssl_private_decrypt($encrypted, $decrypted, $privKey);

echo $decrypted."<br/>";
// Store a string into the variable which 
// need to be Encrypted 

$simple_string = "Welcome to GeeksforGeeks\n"; 

  
// Display the original string 

echo "Original String: " . $simple_string; 

  
// Store the cipher method 

$ciphering = "AES-128-CTR"; 

  
// Use OpenSSl Encryption method 

$iv_length = openssl_cipher_iv_length($ciphering); 

$options = 0; 

  
// Non-NULL Initialization Vector for encryption 

$encryption_iv = '1234567891011121'; 

  
// Store the encryption key 

$encryption_key = "GeeksforGeeks"; 

  
// Use openssl_encrypt() function to encrypt the data 

$encryption = openssl_encrypt($simple_string, $ciphering, 

            $encryption_key, $options, $encryption_iv); 

  
// Display the encrypted string 

echo "Encrypted String: " . $encryption . "\n"; 

  
// Non-NULL Initialization Vector for decryption 

$decryption_iv = '1234567891011121'; 

  
// Store the decryption key 

$decryption_key = "GeeksforGeeks"; 

  
// Use openssl_decrypt() function to decrypt the data 

$decryption=openssl_decrypt ($encryption, $ciphering,  

        $decryption_key, $options, $decryption_iv); 

  
// Display the decrypted string 

echo "Decrypted String: " . $decryption; 

  
?> 