<?php
// Encryption functions with proper error handling

function encryptData($data) {
    if(empty($data)) {
        return null;
    }
    
    $key = 'SMMAS2025SecretKey';
    $method = 'aes-256-cbc';
    
    try {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
        $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    } catch(Exception $e) {
        // Fallback to base64 if encryption fails
        return base64_encode($data);
    }
}

function decryptData($data) {
    if(empty($data)) {
        return '';
    }
    
    $key = 'SMMAS2025SecretKey';
    $method = 'aes-256-cbc';
    
    try {
        // Check if data is base64 encoded
        $decoded = base64_decode($data);
        if($decoded === false) {
            return $data;
        }
        
        // Check if it contains the separator
        if(strpos($decoded, '::') !== false) {
            list($encrypted_data, $iv) = explode('::', $decoded, 2);
            $decrypted = openssl_decrypt($encrypted_data, $method, $key, 0, $iv);
            return $decrypted !== false ? $decrypted : $data;
        } else {
            return $data;
        }
    } catch(Exception $e) {
        return $data;
    }
}
?>