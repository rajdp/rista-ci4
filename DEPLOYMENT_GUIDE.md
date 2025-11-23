# GoDaddy Deployment Guide - Test & Production Environments

This guide will help you deploy your frontend (Angular) and backend (CodeIgniter 4) applications to GoDaddy hosting for both test and production environments.

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Backend Setup (CodeIgniter 4)](#backend-setup-codeigniter-4)
3. [Frontend Setup (Angular)](#frontend-setup-angular)
4. [Database Configuration](#database-configuration)
5. [Deployment Steps](#deployment-steps)
6. [Environment Files Reference](#environment-files-reference)
7. [Troubleshooting](#troubleshooting)

---

## Prerequisites

- GoDaddy hosting account with cPanel access
- FTP/SFTP access or File Manager access
- Database credentials from GoDaddy
- Domain name configured (for production)
- Test subdomain configured (for test environment, e.g., test.yourdomain.com)

---

## Backend Setup (CodeIgniter 4)

### Step 1: Prepare Environment Files

1. **Create `.env` file in the root of `rista_ci4/` directory**

   For **TEST environment**, copy the contents from `.env.test`:
   ```bash
   # In your local machine, create .env file with test configuration
   ```

   For **PRODUCTION environment**, copy the contents from `.env.production`:
   ```bash
   # In your local machine, create .env file with production configuration
   ```

2. **Update the `.env` file with your GoDaddy credentials:**

   ```env
   # TEST Environment Example
   app.baseURL = 'https://test.yourdomain.com/rista_ci4/public/'
   database.default.hostname = 'localhost'
   database.default.database = 'your_test_database_name'
   database.default.username = 'your_test_db_user'
   database.default.password = 'your_test_db_password'
   database.default.port = 3306
   database.default.DBDebug = true
   ```

   ```env
   # PRODUCTION Environment Example
   app.baseURL = 'https://yourdomain.com/rista_ci4/public/'
   database.default.hostname = 'localhost'
   database.default.database = 'your_production_database_name'
   database.default.username = 'your_production_db_user'
   database.default.password = 'your_production_db_password'
   database.default.port = 3306
   database.default.DBDebug = false
   ```

### Step 2: Set Environment in CodeIgniter

1. **Edit `public/index.php`** and set the environment:

   For TEST:
   ```php
   define('ENVIRONMENT', 'development'); // or 'testing'
   ```

   For PRODUCTION:
   ```php
   define('ENVIRONMENT', 'production');
   ```

### Step 3: Generate Encryption Key

1. **SSH into your GoDaddy server** (or use cPanel Terminal)
2. Navigate to your backend directory:
   ```bash
   cd /path/to/rista_ci4
   ```
3. Generate encryption key:
   ```bash
   php spark key:generate
   ```
4. Copy the generated key to your `.env` file:
   ```env
   encryption.key = 'your-generated-key-here'
   ```

### Step 4: Set File Permissions

Set proper permissions for writable directories:
```bash
chmod -R 755 writable/
chmod -R 755 public/uploads/
```

---

## Frontend Setup (Angular)

### Step 1: Update Environment Files

1. **For TEST environment**, edit `web/src/environments/environment.test.ts`:

   ```typescript
   export const environment = {
     production: false,
     apiHost: 'https://test.yourdomain.com/rista_ci4/public/',
     webHost: 'https://test.yourdomain.com/web',
     imgUrl: 'https://test.yourdomain.com/rista_ci4/public',
     version: '6.0.0',
     showStudent: true,
     sessionPrefix: 'rista_test_',
     envName: 'TEST - GoDaddy'
   };
   ```

2. **For PRODUCTION environment**, edit `web/src/environments/environment.prod.ts`:

   ```typescript
   export const environment = {
     production: true,
     apiHost: 'https://yourdomain.com/rista_ci4/public/',
     webHost: 'https://yourdomain.com/web',
     imgUrl: 'https://yourdomain.com/rista_ci4/public',
     version: '6.0.0',
     showStudent: true,
     sessionPrefix: 'rista_',
     envName: 'PRODUCTION'
   };
   ```

### Step 2: Build Angular Application

1. **For TEST environment:**
   ```bash
   cd web
   npm install
   ng build --configuration=test
   ```
   This will create the build in `dist/web/` directory.

2. **For PRODUCTION environment:**
   ```bash
   cd web
   npm install
   ng build --configuration=production
   ```

### Step 3: Upload Frontend Files

Upload the contents of `dist/web/` to your GoDaddy hosting:
- For TEST: Upload to `/public_html/test/web/` or your test subdomain directory
- For PRODUCTION: Upload to `/public_html/web/` or your production domain directory

---

## Database Configuration

### Getting Database Credentials from GoDaddy

1. Log in to **GoDaddy cPanel**
2. Navigate to **MySQL Databases** or **Databases** section
3. Create a new database (if not already created):
   - Database name: `yourdomain_testdb` (for test)
   - Database name: `yourdomain_proddb` (for production)
4. Create database user and assign privileges
5. Note down:
   - Database hostname (usually `localhost`)
   - Database name
   - Database username
   - Database password
   - Port (usually `3306`)

### Import Database

1. **Export your local database:**
   ```bash
   mysqldump -u root -p edquill_production > database_backup.sql
   ```

2. **Import to GoDaddy:**
   - Use **phpMyAdmin** in cPanel
   - Or use command line:
     ```bash
     mysql -u your_db_user -p your_database_name < database_backup.sql
     ```

---

## Deployment Steps

### Backend Deployment (CodeIgniter 4)

1. **Upload backend files** to GoDaddy:
   - Upload entire `rista_ci4/` folder to `/public_html/rista_ci4/` or your desired location

2. **Set up `.env` file:**
   - Create `.env` file in `rista_ci4/` root directory
   - Copy from `.env.test` or `.env.production` template
   - Update with your actual credentials

3. **Configure `.htaccess`** (if needed):
   - Ensure `public/.htaccess` is properly configured for CodeIgniter 4
   - Update rewrite rules if your base path is different

4. **Set permissions:**
   ```bash
   chmod 755 writable/
   chmod 755 writable/cache/
   chmod 755 writable/logs/
   chmod 755 writable/session/
   chmod 755 writable/uploads/
   ```

5. **Test backend API:**
   - Visit: `https://test.yourdomain.com/rista_ci4/public/`
   - Should see CodeIgniter welcome page or your API response

### Frontend Deployment (Angular)

1. **Build the application locally:**
   ```bash
   cd web
   ng build --configuration=test  # for test
   ng build --configuration=production  # for production
   ```

2. **Upload build files:**
   - Upload all files from `dist/web/` to your web directory on GoDaddy
   - For TEST: `/public_html/test/web/` or subdomain directory
   - For PRODUCTION: `/public_html/web/` or domain root

3. **Create/Update `.htaccess`** for Angular routing:
   ```apache
   <IfModule mod_rewrite.c>
     RewriteEngine On
     RewriteBase /
     RewriteRule ^index\.html$ - [L]
     RewriteCond %{REQUEST_FILENAME} !-f
     RewriteCond %{REQUEST_FILENAME} !-d
     RewriteRule . /index.html [L]
   </IfModule>
   ```

4. **Test frontend:**
   - Visit: `https://test.yourdomain.com/web/`
   - Should load your Angular application

---

## Environment Files Reference

### Backend Environment Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `app.baseURL` | Base URL of your application | `https://test.yourdomain.com/rista_ci4/public/` |
| `app.forceGlobalSecureRequests` | Force HTTPS | `true` for production |
| `database.default.hostname` | Database host | `localhost` |
| `database.default.database` | Database name | `your_database_name` |
| `database.default.username` | Database username | `your_db_user` |
| `database.default.password` | Database password | `your_db_password` |
| `database.default.port` | Database port | `3306` |
| `database.default.DBDebug` | Enable debug mode | `false` for production |
| `encryption.key` | Encryption key | Generated via `php spark key:generate` |

### Frontend Environment Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `apiHost` | Backend API URL | `https://test.yourdomain.com/rista_ci4/public/` |
| `webHost` | Frontend URL | `https://test.yourdomain.com/web` |
| `imgUrl` | Image assets URL | `https://test.yourdomain.com/rista_ci4/public` |
| `production` | Production flag | `false` for test, `true` for production |
| `sessionPrefix` | Session prefix | `rista_test_` for test, `rista_` for production |

---

## Troubleshooting

### Backend Issues

1. **500 Internal Server Error:**
   - Check file permissions: `chmod -R 755 writable/`
   - Verify `.env` file exists and has correct values
   - Check error logs in `writable/logs/`

2. **Database Connection Error:**
   - Verify database credentials in `.env`
   - Check if database host is `localhost` (not `127.0.0.1`)
   - Ensure database user has proper privileges

3. **CORS Errors:**
   - Update `cors.allowedOrigins` in `.env`
   - Check backend CORS configuration

### Frontend Issues

1. **404 Errors on Routes:**
   - Ensure `.htaccess` is configured for Angular routing
   - Check that `baseHref` is set correctly in `angular.json`

2. **API Connection Errors:**
   - Verify `apiHost` in environment file matches backend URL
   - Check CORS settings on backend
   - Verify SSL certificate is valid

3. **Build Errors:**
   - Run `npm install` to ensure all dependencies are installed
   - Check Node.js version compatibility
   - Clear `node_modules` and reinstall if needed

### General Issues

1. **SSL Certificate:**
   - Ensure SSL is enabled in GoDaddy cPanel
   - Use HTTPS URLs in all environment files

2. **File Upload Issues:**
   - Check `writable/uploads/` directory permissions
   - Verify upload size limits in PHP configuration

3. **Session Issues:**
   - Check `writable/session/` directory permissions
   - Verify session configuration in `.env`

---

## Quick Reference Commands

### Backend
```bash
# Generate encryption key
php spark key:generate

# Run migrations
php spark migrate

# Clear cache
php spark cache:clear
```

### Frontend
```bash
# Build for test
ng build --configuration=test

# Build for production
ng build --configuration=production

# Serve locally with test config
ng serve --configuration=test
```

---

## Security Checklist

- [ ] `.env` file is not accessible via web (should be outside `public/`)
- [ ] Database passwords are strong and unique
- [ ] Encryption key is generated and secure
- [ ] `DBDebug` is set to `false` in production
- [ ] HTTPS is enforced in production
- [ ] File permissions are set correctly
- [ ] Sensitive files are excluded from public access

---

## Support

For issues specific to:
- **CodeIgniter 4**: Check [CodeIgniter 4 Documentation](https://codeigniter.com/user_guide/)
- **Angular**: Check [Angular Documentation](https://angular.io/docs)
- **GoDaddy Hosting**: Contact GoDaddy Support

---

**Last Updated:** 2025-01-27

