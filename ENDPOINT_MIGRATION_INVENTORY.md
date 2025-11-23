# Endpoint Migration Inventory

Generated: $(date)

## Methodology

1. Extracted all API calls from frontend TypeScript files
2. Extracted all routes from CI4 Routes.php
3. Compared to find missing endpoints
4. Categorized by controller/domain

## Missing Endpoints

These endpoints are called by the frontend but don't exist in CI4 routes:

