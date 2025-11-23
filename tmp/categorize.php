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
