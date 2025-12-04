#!/bin/bash
# Comprehensive Endpoint Migration Checker

FRONTEND_DIR="/Applications/MAMP/htdocs/edquill-web_angupgrade/web"
CI4_DIR="/Applications/MAMP/htdocs/rista_ci4"
OUTPUT_DIR="$CI4_DIR/tmp"

mkdir -p "$OUTPUT_DIR"

echo "=== COMPREHENSIVE ENDPOINT MIGRATION CHECK ==="
echo ""

# Extract frontend API calls
echo "1. Extracting frontend API calls..."
cd "$FRONTEND_DIR" || exit 1

# Extract from service calls
grep -r "postService\|getService\|putService\|deleteService" src --include="*.ts" -h | \
  grep -oE "['\"]([a-z]+/[a-zA-Z0-9\-/]+)['\"]" | \
  sed -E "s/['\"]//g" | \
  grep -E "^[a-z]+/" | \
  sort -u > "$OUTPUT_DIR/frontend_endpoints.txt"

FRONTEND_COUNT=$(wc -l < "$OUTPUT_DIR/frontend_endpoints.txt")
echo "   Found $FRONTEND_COUNT unique frontend API calls"

# Extract CI4 routes
echo "2. Extracting CI4 routes..."
cd "$CI4_DIR" || exit 1

grep -E "routes->(post|get|put|delete|match)\(" app/Config/Routes.php | \
  grep -oE "['\"]([a-z]+/[a-zA-Z0-9\-/]+)['\"]" | \
  sed -E "s/['\"]//g" | \
  sort -u > "$OUTPUT_DIR/ci4_routes.txt"

CI4_COUNT=$(wc -l < "$OUTPUT_DIR/ci4_routes.txt")
echo "   Found $CI4_COUNT CI4 routes"

# Find missing
echo "3. Comparing..."
comm -23 <(sort "$OUTPUT_DIR/frontend_endpoints.txt") <(sort "$OUTPUT_DIR/ci4_routes.txt") > "$OUTPUT_DIR/missing.txt"

MISSING_COUNT=$(wc -l < "$OUTPUT_DIR/missing.txt")
echo "   Found $MISSING_COUNT missing endpoints"

# Categorize
echo "4. Categorizing missing endpoints..."
echo ""

cat > "$OUTPUT_DIR/categorize.php" << 'PHP'
<?php
$missing = file("$OUTPUT_DIR/missing.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$categories = [];

foreach ($missing as $endpoint) {
    $parts = explode('/', $endpoint);
    $cat = $parts[0] ?? 'other';
    if (!isset($categories[$cat])) {
        $categories[$cat] = [];
    }
    $categories[$cat][] = $endpoint;
}

foreach ($categories as $cat => $endpoints) {
    echo "### $cat (" . count($endpoints) . ")\n";
    foreach ($endpoints as $ep) {
        echo "- $ep\n";
    }
    echo "\n";
}
PHP

echo "=== MISSING ENDPOINTS BY CATEGORY ==="
echo ""
cat "$OUTPUT_DIR/missing.txt" | awk -F'/' '{print $1}' | sort | uniq -c | sort -rn

echo ""
echo "=== ALL MISSING ENDPOINTS ==="
cat "$OUTPUT_DIR/missing.txt"

echo ""
echo "=== FILES CREATED ==="
echo "Frontend endpoints: $OUTPUT_DIR/frontend_endpoints.txt"
echo "CI4 routes: $OUTPUT_DIR/ci4_routes.txt"
echo "Missing endpoints: $OUTPUT_DIR/missing.txt"






