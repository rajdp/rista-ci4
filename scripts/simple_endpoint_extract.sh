#!/bin/bash
# Simple endpoint extraction

FRONTEND_DIR="/Applications/MAMP/htdocs/edquill-web_angupgrade/web"
CI4_DIR="/Applications/MAMP/htdocs/rista_ci4"
OUTPUT_DIR="$CI4_DIR/tmp"

mkdir -p "$OUTPUT_DIR"

echo "Extracting frontend endpoints..."
cd "$FRONTEND_DIR" || exit 1

# Method 1: Extract from service calls
grep -r "postService\|getService" src --include="*.ts" | \
  grep -o "'[^']*'" | \
  sed "s/'//g" | \
  grep "^[a-z]" | \
  grep "/" | \
  sort -u > "$OUTPUT_DIR/frontend1.txt"

# Method 2: Extract quoted strings that look like endpoints
grep -r "\.post\|\.get" src --include="*.ts" | \
  grep -o "'[^']*'" | \
  sed "s/'//g" | \
  grep "^[a-z]" | \
  grep "/" | \
  sort -u >> "$OUTPUT_DIR/frontend1.txt"

sort -u "$OUTPUT_DIR/frontend1.txt" > "$OUTPUT_DIR/frontend_endpoints.txt"
FRONTEND_COUNT=$(wc -l < "$OUTPUT_DIR/frontend_endpoints.txt")
echo "Frontend endpoints: $FRONTEND_COUNT"

echo "Extracting CI4 routes..."
cd "$CI4_DIR" || exit 1

grep "routes->" app/Config/Routes.php | \
  grep -o "'[^']*'" | \
  sed "s/'//g" | \
  grep "^[a-z]" | \
  grep "/" | \
  sort -u > "$OUTPUT_DIR/ci4_routes.txt"

CI4_COUNT=$(wc -l < "$OUTPUT_DIR/ci4_routes.txt")
echo "CI4 routes: $CI4_COUNT"

echo "Finding missing..."
comm -23 <(sort "$OUTPUT_DIR/frontend_endpoints.txt") <(sort "$OUTPUT_DIR/ci4_routes.txt") > "$OUTPUT_DIR/missing.txt"

MISSING_COUNT=$(wc -l < "$OUTPUT_DIR/missing.txt")
echo "Missing: $MISSING_COUNT"
echo ""
echo "Missing endpoints:"
cat "$OUTPUT_DIR/missing.txt"






