# test_api.sh
#!/bin/bash
set -e  # Exit on any error

API_BASE="https://open-encrypt.com/api"
API_KEY="open-encrypt-public-api-key"

echo "Testing API endpoints..."

# Test 1: Key Generation
echo "1. Testing keygen..."
curl -X POST "$API_BASE/keygen.php" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: $API_KEY" \
  -d '{"method": "ring_lwe"}' > keys.json

if [ $? -eq 0 ] && [ -f keys.json ]; then
    echo "✓ Keygen successful"
else
    echo "✗ Keygen failed"
    exit 1
fi

# Test 2: Encryption
echo "2. Testing encryption..."
PUBLIC_KEY=$(jq -r '.public_key' keys.json)
curl -X POST "$API_BASE/encrypt.php" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: $API_KEY" \
  -d "{\"method\": \"ring_lwe\", \"public_key\": \"$PUBLIC_KEY\", \"plaintext\": \"Test message\"}" > encrypted.json

if [ $? -eq 0 ] && [ -f encrypted.json ]; then
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

PLAINTEXT=$(echo "$RESULT" | jq -r '.plaintext')

# Trim quotes if present
PLAINTEXT=$(echo "$PLAINTEXT" | tr -d '"')

if [ "$PLAINTEXT" == "Test message" ]; then
    echo "✓ Decryption successful - message matches!"
else
    echo "✗ Decryption failed - got: $PLAINTEXT"
    exit 1
fi

# Cleanup
rm -f keys.json encrypted.json

echo "All tests passed! ✓"