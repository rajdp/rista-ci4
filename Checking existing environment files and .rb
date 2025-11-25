Checking existing environment files and backend configuration:
Checking CodeIgniter 4 environment configuration:
Setting up environment files for test and production. Creating the necessary files and updating configurations.
Create .env files for CodeIgniter 4 backend (test and production)
Update CodeIgniter 4 Database.php to use environment variables
Update CodeIgniter 4 App.php to use environment variables
Create test environment file for Angular frontend
Update Angular build configurations for test environment
Create deployment documentation for GoDaddy
Creating environment files and updating configurations. Starting with the backend:
Database.php
+9
-9
        'DBPrefix'     => '',
        'hostname'     => env('database.default.hostname', '127.0.0.1'),        'username'     => env('database.default.username', 'root'),        'password'     => env('database.default.password', 'root'),        'database'     => env('database.default.database', 'edquill_production'),        'DBDriver'     => env('database.default.DBDriver', 'MySQLi'),
App.php
+1
-1
     */    public string $baseURL = 'http://localhost:8888/rista/public/';
baseURL
    public string $baseURL = 'http://localhost:8888/rista/public/';     */    public string $baseURL = env('app.baseURL', 'http://localhost:8888/rista_ci4/public/');
App.php
+1
-1
     */    public bool $forceGlobalSecureRequests = false;
    public bool $forceGlobalSecureRequests = false;     */    public bool $forceGlobalSecureRequests = env('app.forceGlobalSecureRequests', false);
Creating frontend environment files and updating the Angular configuration:
environment.test.ts
+19
// Test Environment Configuration// For GoDaddy test/staging deploymentexport const environment = {  production: false,  // Replace with your test domain backend API URL
Adding the test configuration to angular.json:
angular.json
+27
            "production": {
            "test": {              "fileReplacements": [                {                  "replace": "src/environments/environment.ts",                  "with": "src/environments/environment.test.ts"
angular.json
+3
            },            "production": {
            },            "test": {              "browserTarget": "multikart-backend:build:test"            },            "production": {
Creating deployment documentation and checking other Angular projects:
DEPLOYMENT_GUIDE.md
+387
# GoDaddy Deployment Guide - Test & Production EnvironmentsThis guide will help you deploy your frontend (Angular) and backend (CodeIgniter 4) applications to GoDaddy hosting for both test and production environments.## Table of Contents
ENV_SETUP_INSTRUCTIONS.md
+252
# Environment Files Setup InstructionsThis document explains how to create and configure environment files for test and production deployments.## Backend Environment Files (.env)
Checking .gitignore and creating a setup script:
Updating .gitignore and creating a summary document:
.gitignore
+7
# Environment files - DO NOT COMMIT.env.env.local.env.test.env.production
QUICK_START_DEPLOYMENT.md
+120
# Quick Start: GoDaddy DeploymentThis is a quick reference guide for deploying to GoDaddy. For detailed instructions, see `DEPLOYMENT_GUIDE.md`.## 🚀 Quick Setup Steps
Updating the .env template files to include CI_ENVIRONMENT and creating a summary:
ENVIRONMENT_SETUP_SUMMARY.md
+149
# Environment Setup Summary## ✅ What Has Been Configured### Backend (CodeIgniter 4)
Checking for linting errors and preparing a final summary:
Environment setup for test and production deployments is complete. Summary:
Completed setup
Backend (CodeIgniter 4)
Environment templates:
.env.test — template for test environment
.env.production — template for production environment
.env.example — example with all options
Configuration updates:
app/Config/Database.php — uses environment variables for database settings
app/Config/App.php — uses environment variables for baseURL and HTTPS
.gitignore — updated to exclude .env* files
Frontend (Angular)
Environment file:
web/src/environments/environment.test.ts — test environment configuration
Build configuration:
web/angular.json — added "test" configuration for building test environment
Documentation
DEPLOYMENT_GUIDE.md — deployment guide
ENV_SETUP_INSTRUCTIONS.md — environment file setup instructions
QUICK_START_DEPLOYMENT.md — quick reference
ENVIRONMENT_SETUP_SUMMARY.md — summary of what was configured
Next steps
For test environment
Create .env file:
)
   cd /Applications/MAMP/htdocs/rista_ci4   # Copy the template (you'll need to create .env manually from .env.test template)
Update .env with your GoDaddy test credentials:
app.baseURL = https://test.yourdomain.com/rista_ci4/public/
Database credentials from GoDaddy cPanel
CI_ENVIRONMENT = development
Generate encryption key:
generate
   php spark key:generate
Update frontend environment:
Edit web/src/environments/environment.test.ts
Update URLs to match your test domain
Build frontend:
   cd web   ng build --configuration=test
For production environment
Follow the same steps but:
Use .env.production template
Set CI_ENVIRONMENT = production
Set database.default.DBDebug = false
Use production domain URLs
Build with --configuration=production
Important notes
.env files are not created automatically (they're in .gitignore). Create them manually from the templates.
Update all placeholder values with your actual GoDaddy credentials.
Generate a unique encryption key for each environment.
Use HTTPS URLs for all environments.
Set DBDebug = false in production.
Documentation files
All documentation is in the rista_ci4/ directory:
DEPLOYMENT_GUIDE.md — complete guide
ENV_SETUP_INSTRUCTIONS.md — environment setup details
QUICK_START_DEPLOYMENT.md — quick reference
ENVIRONMENT_SETUP_SUMMARY.md — summary
The setup is ready. Follow the documentation to deploy to GoDaddy.