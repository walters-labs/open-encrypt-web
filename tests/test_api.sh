#!/bin/bash
set -e  # Exit on any error

API_BASE="https://open-encrypt.com/api"
API_KEY="open-encrypt-public-api-key"

echo "Testing API endpoints..."

# Helper function to POST JSON and check HTTP status and JSON validity
post_json() {
  local url=$1
  local data=$2
  local outfile=$3

  # Capture HTTP status and body separately
  response=$(curl -s -w "%{http_code}" -X POST "$url" \
    -H "Content-Type: application/json" \
    -H "X-API-Key: $API_KEY" \
    -d "$data")

  http_code="${response: -3}"
  body="${response::-3}"

  if [[ "$http_code" == "200" ]] && echo "$body" | jq empty >/dev/null 2>&1; then
    echo "$body" > "$outfile"
    return 0
  else
    echo "Request to $url failed with HTTP status $http_code or invalid JSON."
    return 1
  fi
}

# Test 1: Key Generation
echo "1. Testing keygen..."
if post_json "$API_BASE/keygen.php" '{"method": "ring_lwe"}' keys.json; then
  echo "✓ Keygen successful"
else
  echo "✗ Keygen failed"
  exit 1
fi

# Test 2: Encryption
echo "2. Testing encryption..."
PUBLIC_KEY=$(jq -r '.public_key' keys.json)
if post_json "$API_BASE/encrypt.php" "{\"method\": \"ring_lwe\", \"public_key\": \"$PUBLIC_KEY\", \"plaintext\": \"Test message\"}" encrypted.json; then
  echo "✓ Encryption successful"
else
  echo "✗ Encryption failed"
  exit 1
fi

# Test 3: Decryption
echo "3. Testing decryption..."
SECRET_KEY=$(jq -r '.secret_key' keys.json)
CIPHERTEXT=$(jq -r '.ciphertext' encrypted.json)

RESULT=$(curl -s -X POST "$API_BASE/decrypt.php" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: $API_KEY" \
  -d "{\"method\": \"ring_lwe\", \"secret_key\": \"$SECRET_KEY\", \"ciphertext\": \"$CIPHERTEXT\"}")

# Check that result is valid JSON and extract plaintext
if echo "$RESULT" | jq empty >/dev/null 2>&1; then
  PLAINTEXT=$(echo "$RESULT" | jq -r '.plaintext' | tr -d '"')
else
  echo "✗ Decryption response invalid JSON"
  exit 1
fi

if [[ "$PLAINTEXT" == "Test message" ]]; then
  echo "✓ Decryption successful - message matches!"
else
  echo "✗ Decryption failed - got: $PLAINTEXT"
  exit 1
fi

# Cleanup
rm -f keys.json encrypted.json

echo "All tests passed! ✓"
