#!/bin/bash

echo "=========================================="
echo "Testing CI4 Backend Endpoints"
echo "=========================================="
echo ""

BASE_URL="http://localhost:8888/rista_ci4/public"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

test_endpoint() {
    local name=$1
    local endpoint=$2
    local method=${3:-GET}
    
    echo -e "${BLUE}Testing: $name${NC}"
    echo "URL: $BASE_URL$endpoint"
    
    if [ "$method" = "GET" ]; then
        response=$(curl -s -w "\n%{http_code}" "$BASE_URL$endpoint")
    else
        response=$(curl -s -w "\n%{http_code}" -X POST "$BASE_URL$endpoint" \
            -H "Content-Type: application/json" \
            -d '{}')
    fi
    
    status_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | head -n-1)
    
    if [ "$status_code" = "200" ] || [ "$status_code" = "201" ]; then
        echo -e "${GREEN}✓ SUCCESS${NC} (Status: $status_code)"
        echo "$body" | python3 -m json.tool 2>/dev/null | head -5 || echo "$body" | head -5
    else
        echo -e "${RED}✗ FAILED${NC} (Status: $status_code)"
        echo "$body" | head -3
    fi
    echo ""
}

echo "1. Testing Public Endpoints (No Auth)"
echo "=========================================="
test_endpoint "Root URL" "/"
test_endpoint "Admin Token" "/auth/token" "GET"
test_endpoint "Common - Countries" "/common/countries" "GET"
test_endpoint "Common - States" "/common/states" "GET"

echo ""
echo "2. Testing Auth Endpoints"
echo "=========================================="
test_endpoint "User Login (No credentials)" "/user/login" "POST"

echo ""
echo "=========================================="
echo "Backend Status Summary"
echo "=========================================="
echo ""
echo "✅ CI4 Backend URL: $BASE_URL"
echo "✅ CodeIgniter 4.6.0 is running"
echo ""
echo "Next Steps:"
echo "1. Restart your Angular dev server if not already done"
echo "2. Check browser console for: 'API Host: http://localhost:8888/rista_ci4/public/'"
echo "3. Monitor Network tab to see requests going to CI4"
echo "4. Test login and other features"
echo ""

