#!/bin/bash

# EdQuill CI4 Backend Testing Script
# This script tests the CI4 backend endpoints

echo "=========================================="
echo "EdQuill CI4 Backend Testing"
echo "=========================================="
echo ""

BASE_URL="http://localhost:8888/rista_ci4/public"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test counter
TESTS_PASSED=0
TESTS_FAILED=0

# Function to test endpoint
test_endpoint() {
    local name=$1
    local method=$2
    local endpoint=$3
    local data=$4
    local token=$5
    
    echo -e "${BLUE}Testing: $name${NC}"
    
    if [ -z "$token" ]; then
        if [ "$method" = "GET" ]; then
            response=$(curl -s -w "\n%{http_code}" -X GET "$BASE_URL$endpoint")
        else
            response=$(curl -s -w "\n%{http_code}" -X POST "$BASE_URL$endpoint" \
                -H "Content-Type: application/json" \
                -d "$data")
        fi
    else
        response=$(curl -s -w "\n%{http_code}" -X POST "$BASE_URL$endpoint" \
            -H "Content-Type: application/json" \
            -H "Accesstoken: $token" \
            -d "$data")
    fi
    
    status_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | head -n-1)
    
    if [ "$status_code" = "200" ] || [ "$status_code" = "201" ]; then
        echo -e "${GREEN}✓ PASS${NC} (Status: $status_code)"
        TESTS_PASSED=$((TESTS_PASSED + 1))
    else
        echo -e "${RED}✗ FAIL${NC} (Status: $status_code)"
        echo "Response: $body"
        TESTS_FAILED=$((TESTS_FAILED + 1))
    fi
    echo ""
}

echo "1. Testing Public Endpoints (No Auth)"
echo "=========================================="

# Test admin token generation
test_endpoint "Admin Token Generation" "GET" "/auth/token"

# Save token for later tests
ADMIN_TOKEN=$(curl -s -X GET "$BASE_URL/auth/token" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

# Test common endpoints
test_endpoint "Get Countries" "GET" "/common/countries"
test_endpoint "Get States" "GET" "/common/states"

echo ""
echo "2. Testing Authentication"
echo "=========================================="

# Test user login (this will fail without valid credentials, but tests the endpoint)
test_endpoint "User Login (Invalid)" "POST" "/user/login" '{"email":"test@example.com","password":"test123"}'

echo ""
echo "3. Testing Protected Endpoints (With Token)"
echo "=========================================="

if [ ! -z "$ADMIN_TOKEN" ]; then
    echo "Using token: ${ADMIN_TOKEN:0:20}..."
    
    # Test settings list
    test_endpoint "Settings List" "POST" "/settings/list" '{"platform":"web","role_id":1,"user_id":1}' "$ADMIN_TOKEN"
    
    # Test school list
    test_endpoint "School List" "POST" "/school/list" '{}' "$ADMIN_TOKEN"
    
else
    echo -e "${RED}No admin token available - skipping protected endpoint tests${NC}"
fi

echo ""
echo "=========================================="
echo "Test Results"
echo "=========================================="
echo -e "${GREEN}Passed: $TESTS_PASSED${NC}"
echo -e "${RED}Failed: $TESTS_FAILED${NC}"
echo ""

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "${GREEN}All tests passed! ✓${NC}"
    exit 0
else
    echo -e "${RED}Some tests failed${NC}"
    exit 1
fi

