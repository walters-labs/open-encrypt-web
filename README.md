# open-encrypt-web

[![License: MIT](https://img.shields.io/badge/License-MIT-brightgreen.svg)](https://opensource.org/licenses/MIT)
![CI](https://github.com/open-encrypt/open-encrypt-web/actions/workflows/ci.yml/badge.svg)

Full-stack encrypted messaging application using lattice-based methods in Rust + PHP + SQL.

## iOS

[https://github.com/open-encrypt/open-encrypt-ios](https://github.com/open-encrypt/open-encrypt-ios)

Uses the API in `api` to interact with the database.

## Disclaimer

This app is meant for educational use, or as a demo.

The encryption methods used have not been hardened against timing attacks or other side-channel attacks. 

This code has not been audited for security.

## Encryption methods (Rust)

Rust binaries are executed directly using `shell_exec`. Uses both command line arguments and files as input.

Currently using Rust crates `ring-lwe` v0.1.8 and `module-lwe` v0.1.5. 

- https://crates.io/crates/ring-lwe
- https://crates.io/crates/module-lwe

## Database (SQL)

- The database and all tables can be initialized with the script `schema.sql`.
- Passwords are hashed using standard hashing. 
- Secure, random tokens stored for user sessions.
- Messages are stored encrypted on the server in a SQL database.
- For both ring-LWE and module-LWE, messages are stored as compressed and encoded base64 strings.

## Backend (PHP)

Used to handle basic account creation, login, and SQL insertions/lookups.

## Copyright

© 2025 Jackson Walters · MIT License
