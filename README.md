# open-encrypt-web

[![License: MIT](https://img.shields.io/badge/License-MIT-brightgreen.svg)](https://opensource.org/licenses/MIT)
![CI](https://github.com/open-encrypt/open-encrypt-web/actions/workflows/ci.yml/badge.svg)

Full-stack encrypted messaging application using lattice-based methods in Rust + PHP + SQL.

## iOS

iOS frontend written in Swift.

[https://github.com/open-encrypt/open-encrypt-ios](https://github.com/open-encrypt/open-encrypt-ios)

## Public API

A public API is available for performing key generation, encryption, and decryption. The public API key is `open-encrypt-public-api-key`.

### Key Generation, endpoint = `keygen.php`

```
curl -X POST https://open-encrypt.com/api/keygen.php \
  -H "Content-Type: application/json" \
  -H "X-API-Key: open-encrypt-public-api-key" \
  -d '{"method": "ring_lwe"}' > keys.json
```

This returns a JSON response with four fields: ["status","method","public_key","secret_key"]. 

The public key and secret key are both base64-encoded strings. They will be piped to a file `keys.json`.

### Encryption, endpoint = `encrypt.php`

To encrypt a message, create a JSON file `to_encrypt.json` with the following format:

```
{"api_key":"open-encrypt-public-api-key",
"method":"ring-lwe",
"public_key":"AAgAAAAAAACD8v\/\/\/\/\/\/\/xsIAAAAAAA...",
"plaintext":"Hello world!"}
```

and run the following command:

```
curl -X POST https://open-encrypt.com/api/encrypt.php \
  -H "Content-Type: application/json" \
  -H "X-API-Key: open-encrypt-public-api-key" \
  -d @to_encrypt.json
```

### Decryption, endpoint = `decrypt.php`

To decrypt a message, create a JSON file `to_decrypt.json` with the following format:

```
{"api_key":"open-encrypt-public-api-key",
"method":"ring-lwe",
"secret_key":"ABAAAAAAAAABAAAAAAAAAAEAAAAAAAAA...",
"ciphertext":"Hello world!"}
```

and run the following command:

```
curl -X POST https://open-encrypt.com/api/decrypt.php \
  -H "Content-Type: application/json" \
  -H "X-API-Key: open-encrypt-public-api-key" \
  -d @to_decrypt.json
```

## Disclaimer

This app is meant for educational use, or as a demo.

The encryption methods used have not been hardened against timing attacks or other side-channel attacks. 

This code has not been audited for security.

## Encryption methods

Rust binaries are executed directly using `shell_exec`. Uses both command line arguments and files as input.

Currently using Rust crates `ring-lwe` v0.1.8 and `module-lwe` v0.1.5. 

- https://crates.io/crates/ring-lwe
- https://crates.io/crates/module-lwe

## Database

- written in `mySQL`
- The database and all tables can be initialized with the script `schema.sql`.
- Passwords are hashed using standard hashing. 
- Secure, random tokens stored for user sessions.
- Messages are stored encrypted on the server in a SQL database.
- For both ring-LWE and module-LWE, messages are stored as compressed and encoded base64 strings.

## Backend

Written in `php`. Used to handle basic account creation, login, and SQL insertions/lookups.

## Copyright

© 2025 Jackson Walters · MIT License
