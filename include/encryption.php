<?php

/** define a function which generates public and private keys
 * 
 *  @param string $encryption_method The encryption method to use (default: "ring_lwe")
 *  @return array An associative array containing 'public_key' and 'secret_key'
 */
function generate_keys(string $encryption_method = "ring_lwe"){
    $binary_path = __DIR__ . '/../bin/';
    $command = escapeshellcmd($binary_path . ($encryption_method == "ring_lwe" ? "ring-lwe-v0.1.8" : "module-lwe-v0.1.5") . " keygen");
    $json_string = shell_exec($command);
    try{
        $json_object = json_decode($json_string, true, 512, JSON_THROW_ON_ERROR);
    }
    catch(Exception $e){
        print $e;
    }
    return $json_object;
}

/** Encrypt a message using the given public key
 * 
 *  @param string $public_key The public key to use for encryption
 *  @param string $plaintext The plaintext message to encrypt
 *  @param string $encryption_method The encryption method to use (default: "ring_lwe")
 *  @return string The encrypted ciphertext
 */
function encrypt_message(string $public_key, string $plaintext, string $encryption_method = "ring_lwe") : string {

    // Validate encryption method explicitly - fail fast if invalid
    $valid_methods = ["ring_lwe", "module_lwe"];
    if (!in_array($encryption_method, $valid_methods, true)) {
        throw new Exception("Invalid encryption method: '$encryption_method'. Must be one of: " . implode(", ", $valid_methods));
    }

    $binary_path = __DIR__ . '/../bin/';
    $binary = ($encryption_method == "ring_lwe" ? "ring-lwe-v0.1.8" : "module-lwe-v0.1.5");
    $binary_full = $binary_path . $binary;

    // Verify binary exists
    if (!file_exists($binary_full)) {
        throw new Exception("Encryption binary not found: $binary_full");
    }

    if ($encryption_method == "ring_lwe") {
        // Inline key works fine for ring-lwe
        $command = escapeshellarg($binary_full)
            . " encrypt "
            . "--pubkey " . escapeshellarg(trim($public_key))
            . " " . escapeshellarg(trim($plaintext))
            . " 2>&1"; //capture stderr
    } else {
        // Write public key to a temp file for module-lwe
        $tmp_pubkey_file = tempnam(sys_get_temp_dir(), "pubkey_");
        file_put_contents($tmp_pubkey_file, trim($public_key));

        $cmd = escapeshellcmd($binary_full);
        $command = $cmd
            . " encrypt "
            . "--pubkey-file " . escapeshellarg($tmp_pubkey_file)
            . " " . escapeshellarg(trim($plaintext))
            . " 2>&1"; //capture stderr
    }

    $output = [];
    $return_var = 0;
    exec($command, $output, $return_var);

    if ($return_var !== 0) {
        $error_message = "Rust encryption failed: " . implode("\n", $output);
        error_log($error_message);
        throw new Exception($error_message);
    }

    // Optionally clean up temp file
    if (isset($tmp_pubkey_file) && file_exists($tmp_pubkey_file)) {
        unlink($tmp_pubkey_file);
    }

    // Return first line if output is single-line; fallback to all lines
    return $output[0] ?? implode("\n", $output);
}

/**
 * Decrypt a message using the secret key
 * 
 * @param string $secret_key The secret key to use for decryption
 * @param string $ciphertext The ciphertext to decrypt
 * @param string $encryption_method The encryption method to use (default: "ring_lwe")
 * @return string The decrypted plaintext
 * @throws Exception if Rust decryption fails
 */
function decrypt_message(string $secret_key, string $ciphertext, string $encryption_method = "ring_lwe"): string {
    $binary_path = __DIR__ . '/../bin/';
    $binary = $encryption_method === "ring_lwe" ? "ring-lwe-v0.1.8" : "module-lwe-v0.1.5";

    $command = $binary_path . $binary
        . " decrypt --secret " . escapeshellarg(trim($secret_key))
        . " " . escapeshellarg(trim($ciphertext))
        . " 2>&1"; // capture stderr

    $output = [];
    $return_var = 0;
    exec($command, $output, $return_var);

    if ($return_var !== 0) {
        $error_message = "Rust decryption failed: " . implode("\n", $output);
        error_log($error_message);
        throw new Exception($error_message);
    }

    // Return first line if output is single-line; fallback to all lines
    return $output[0] ?? implode("\n", $output);
}


/**
 * Decrypt using secret key and ciphertext files
 * 
 * @param string $seckey_file Path to the secret key file
 * @param string $ciphertext_file Path to the ciphertext file
 * @param string $encryption_method The encryption method to use (default: "ring_lwe")
 * @return string The decrypted plaintext
 * @throws Exception if Rust decryption fails
 */
function run_decrypt_with_files(string $seckey_file, string $ciphertext_file, string $encryption_method = "ring_lwe") : string {
    $binary_path = __DIR__ . '/../bin/';
    $binary = ($encryption_method === "ring_lwe")
        ? "ring-lwe-v0.1.8"
        : "module-lwe-v0.1.5";

    $cmd = $binary_path . $binary
        . " decrypt --secret-file " . escapeshellarg($seckey_file)
        . " --ciphertext-file " . escapeshellarg($ciphertext_file)
        . " 2>&1"; // capture stderr

    $output = [];
    $return_var = 0;
    exec($cmd, $output, $return_var);

    if ($return_var !== 0) {
        $error_message = "Rust file decryption failed: " . implode("\n", $output);
        error_log($error_message);
        throw new Exception($error_message);
    }

    // Return first line if available; otherwise all lines
    return $output[0] ?? implode("\n", $output);
}

?>