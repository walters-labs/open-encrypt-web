# open-encrypt-web

[![License: MIT](https://img.shields.io/badge/License-MIT-brightgreen.svg)](https://opensource.org/licenses/MIT)
![CI](https://github.com/open-encrypt/open-encrypt-web/actions/workflows/ci.yml/badge.svg)

Full-stack encrypted messaging application using lattice-based methods in Rust + PHP + SQL.

## iOS

iOS frontend written in Swift: [https://github.com/open-encrypt/open-encrypt-ios](https://github.com/open-encrypt/open-encrypt-ios)

## Public API

A public API is available for performing key generation, encryption, and decryption.

[https://rapidapi.com/jacksonwalters/api/open-encrypt](https://rapidapi.com/jacksonwalters/api/open-encrypt)

The public API key is `open-encrypt-public-api-key`. It is currently rate limited to 60 requests / min.

### Key Generation

The endpoint is `api/keygen.php`. To generate public/secret keys, run the following command:
```bash
curl -X POST https://open-encrypt.com/api/keygen.php \
  -H "Content-Type: application/json" \
  -H "X-API-Key: open-encrypt-public-api-key" \
  -d '{"method": "ring_lwe"}' > keys.json
```

This returns a JSON response with four fields: `{"status","method","public_key","secret_key"}`. 

The public key and secret key are both base64-encoded strings. They will be piped to a file `keys.json`.

### Encryption

The endpoint is `api/encrypt.php`. Create `to_encrypt.json` using the public key from `keys.json`:

```bash
PUBLIC_KEY=$(jq -r '.public_key' keys.json)
cat > to_encrypt.json <<EOF
{
  "method": "ring_lwe",
  "public_key": "$PUBLIC_KEY",
  "plaintext": "Hello world!"
}
EOF
```

Then encrypt the message:
```bash
curl -X POST https://open-encrypt.com/api/encrypt.php \
  -H "Content-Type: application/json" \
  -H "X-API-Key: open-encrypt-public-api-key" \
  -d @to_encrypt.json > encrypted.json
```

### Decryption

The endpoint is `api/decrypt.php`. Create `to_decrypt.json` using the secret key from `keys.json` and ciphertext from `encrypted.json`:

```bash
SECRET_KEY=$(jq -r '.secret_key' keys.json)
CIPHERTEXT=$(jq -r '.ciphertext' encrypted.json)
cat > to_decrypt.json <<EOF
{
  "method": "ring_lwe",
  "secret_key": "$SECRET_KEY",
  "ciphertext": "$CIPHERTEXT"
}
EOF
```

Then decrypt the message:
```bash
curl -X POST https://open-encrypt.com/api/decrypt.php \
  -H "Content-Type: application/json" \
  -H "X-API-Key: open-encrypt-public-api-key" \
  -d @to_decrypt.json
```

**Note:** The `api_key` field in the JSON body is not needed when using the `X-API-Key` header.

## Disclaimer

This app is meant for educational use.

The encryption methods used have not been hardened against timing attacks or other side-channel attacks. 

This code has not been audited for security.

## Encryption methods

Currently using Rust binaries `ring-lwe` v0.1.8 and `module-lwe` v0.1.5. 

- https://crates.io/crates/ring-lwe
- https://crates.io/crates/module-lwe

## Database

- The `mySQL` database and all tables can be initialized with the script `schema.sql`.
- Passwords are hashed using standard hashing. 
- Secure, random tokens stored for user sessions.
- Messages are stored encrypted on the server in a SQL database.
- For both ring-LWE and module-LWE, messages are stored as compressed and encoded base64 strings.

## Backend

Written in `PHP`. Used to handle basic account creation, login, and SQL insertions/lookups.

## Copyright

© 2025 Jackson Walters · MIT License
