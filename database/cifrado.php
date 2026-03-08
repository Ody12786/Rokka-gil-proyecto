<?php
define('SECRET_KEY', 'Other_LEVEL_La_ROKA_Sports_GIL_');
define('METHOD', 'AES-256-CBC');

// Función para cifrar el ID
function encryptId($id) {
    $id = strval($id); 
    $iv_length = openssl_cipher_iv_length(METHOD);
    $iv = openssl_random_pseudo_bytes($iv_length); // Genera IV aleatorio
    $encrypted = openssl_encrypt($id, METHOD, SECRET_KEY, 0, $iv);
    if ($encrypted === false) {
    
        return false;
    }
   
    return base64_encode($iv . $encrypted);
}

// Función para descifrar el ID
function decryptId($ciphertext_base64) {
    if (empty($ciphertext_base64)) {
        return false;
    }
    $ciphertext_dec = base64_decode($ciphertext_base64);
    if ($ciphertext_dec === false) {
        return false; 
    }
    $iv_length = openssl_cipher_iv_length(METHOD);
    if (strlen($ciphertext_dec) < $iv_length) {
        return false; 
    }
    $iv = substr($ciphertext_dec, 0, $iv_length);
    $encrypted = substr($ciphertext_dec, $iv_length);
    $decrypted = openssl_decrypt($encrypted, METHOD, SECRET_KEY, 0, $iv);
    if ($decrypted === false) {
        return false; 
    }
    return $decrypted;
}
?>