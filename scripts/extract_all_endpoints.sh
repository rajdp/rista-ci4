#!/bin/bash
# Extract all API endpoints from frontend and CI4 routes

FRONTEND_DIR="/Applications/MAMP/htdocs/edquill-web_angupgrade/web"
CI4_DIR="/Applications/MAMP/htdocs/rista_ci4"
OUTPUT_DIR="/Applications/MAMP/htdocs/rista_ci4/tmp"

echo "Extracting frontend endpoints..."

# Extract from TypeScript files - multiple patterns
cd "$FRONTEND_DIR" || exit 1

# Pattern 1: postService/getService calls
grep -rohE "(postService|getService|putService|deleteService)\([^,]*,\s*['\"]([^'\"]+)['\"]" src --include="*.ts" | \
  sed -E "s/.*['\"]([^'\"]+)['\"].*/\1/" > "$OUTPUT_DIR/frontend_pattern1.txt"

# Pattern 2: Direct string literals that look like API endpoints
grep -rohE "['\"]([a-z]+/[a-zA-Z0-9\-/]+)['\"]" src --include="*.ts" | \
  sed -E "s/.*['\"]([^'\"]+)['\"].*/\1/" | \
  grep -E "^[a-z]+/" | \
  grep -vE "^(assets|http|https|#|/)" > "$OUTPUT_DIR/frontend_pattern2.txt"

# Pattern 3: Service method calls
grep -rohE "\.(post|get|put|delete)\(['\"]([^'\"]+)['\"]" src --include="*.ts" | \
  sed -E "s/.*['\"]([^'\"]+)['\"].*/\1/" > "$OUTPUT_DIR/frontend_pattern3.txt"

# Combine and deduplicate
cat "$OUTPUT_DIR/frontend_pattern1.txt" "$OUTPUT_DIR/frontend_pattern2.txt" "$OUTPUT_DIR/frontend_pattern3.txt" | \
  sort -u > "$OUTPUT_DIR/frontend_endpoints_complete.txt"

echo "Frontend endpoints found: $(wc -l < "$OUTPUT_DIR/frontend_endpoints_complete.txt")"

echo "Extracting CI4 routes..."

# Extract CI4 routes
cd "$CI4_DIR" || exit 1

# Extract route paths from Routes.php
grep -E "routes->(post|get|put|delete|match)\(" app/Config/Routes.php | \
  sed -E "s/.*['\"]([^'\"]+)['\"].*/\1/" | \
  sed -E "s/.*::([^:]+)$/\1/" | \
  sed -E "s/^([^:]+)::.*/\1/" | \
  grep -vE "^(Admin|Api|Appt|Corporate|Contentcreator|EssayGrader|Feedback|Mailbox|Report|Test)" | \
  sort -u > "$OUTPUT_DIR/ci4_routes_complete.txt"

echo "CI4 routes found: $(wc -l < "$OUTPUT_DIR/ci4_routes_complete.txt")"

# Find missing
comm -23 <(sort "$OUTPUT_DIR/frontend_endpoints_complete.txt") <(sort "$OUTPUT_DIR/ci4_routes_complete.txt") > "$OUTPUT_DIR/missing_endpoints_complete.txt"

echo "Missing endpoints: $(wc -l < "$OUTPUT_DIR/missing_endpoints_complete.txt")"
echo ""
echo "Missing endpoints list:"
cat "$OUTPUT_DIR/missing_endpoints_complete.txt"








