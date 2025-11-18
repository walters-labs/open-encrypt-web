# open-encrypt-web

[![License: MIT](https://img.shields.io/badge/License-MIT-brightgreen.svg)](https://opensource.org/licenses/MIT)
![CI](https://github.com/open-encrypt/open-encrypt-web/actions/workflows/ci.yml/badge.svg)

Full-stack encrypted messaging application using lattice-based methods in Rust + PHP + SQL.

## Disclaimer

This app is meant for educational use.

The encryption methods used have not been hardened against timing attacks or other side-channel attacks. 

This code has not been audited for security.

## iOS

iOS frontend written in Swift: [https://github.com/open-encrypt/open-encrypt-ios](https://github.com/open-encrypt/open-encrypt-ios)

## Public API

A public API is available for performing key generation, encryption, and decryption.

The public API key is `open-encrypt-public-api-key`. It is currently rate limited to 60 requests / min.

- Documentation: [https://docs.open-encrypt.com](https://docs.open-encrypt.com)
- RapidAPI: [https://rapidapi.com/jacksonwalters/api/open-encrypt](https://rapidapi.com/jacksonwalters/api/open-encrypt)

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
