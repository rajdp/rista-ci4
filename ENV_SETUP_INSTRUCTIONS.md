# Environment Files Setup Instructions

This document explains how to create and configure environment files for test and production deployments.

## Backend Environment Files (.env)

### Location
All `.env` files should be placed in the root directory of `rista_ci4/` (same level as `app/`, `public/`, etc.)

### Files to Create

1. **`.env`** - Active environment file (DO NOT commit to git)
2. **`.env.test`** - Template for test environment (already created)
3. **`.env.production`** - Template for production environment (already created)
4. **`.env.example`** - Example template (already created)

### Creating .env File

#### For TEST Environment:

1. Copy the contents from `.env.test` file
2. Create a new file named `.env` in `rista_ci4/` root
3. Paste the contents and update with your actual GoDaddy test credentials:

```env
# TEST Environment
app.baseURL = 'https://test.yourdomain.com/rista_ci4/public/'
app.forceGlobalSecureRequests = true

database.default.hostname = 'localhost'
database.default.database = 'your_test_database_name'
database.default.username = 'your_test_db_user'
database.default.password = 'your_test_db_password'
database.default.DBDriver = 'MySQLi'
database.default.port = 3306
database.default.DBDebug = true
database.default.charset = 'utf8mb4'
database.default.DBCollat = 'utf8mb4_general_ci'

# Generate key using: php spark key:generate
encryption.key = 'your-generated-key-here'

session.driver = 'CodeIgniter\Session\Handlers\FileHandler'
session.cookieName = 'edquill_test_session'
session.expiration = 7200
session.savePath = WRITEPATH . 'session'
session.matchIP = false
session.timeToUpdate = 300
session.regenerateDestroy = false

cors.allowedOrigins = 'https://test.yourdomain.com'
logger.threshold = 4
```

#### For PRODUCTION Environment:

1. Copy the contents from `.env.production` file
2. Create a new file named `.env` in `rista_ci4/` root
3. Paste the contents and update with your actual GoDaddy production credentials:

```env
# PRODUCTION Environment
app.baseURL = 'https://yourdomain.com/rista_ci4/public/'
app.forceGlobalSecureRequests = true

database.default.hostname = 'localhost'
database.default.database = 'your_production_database_name'
database.default.username = 'your_production_db_user'
database.default.password = 'your_production_db_password'
database.default.DBDriver = 'MySQLi'
database.default.port = 3306
database.default.DBDebug = false
database.default.charset = 'utf8mb4'
database.default.DBCollat = 'utf8mb4_general_ci'

# Generate key using: php spark key:generate
encryption.key = 'your-generated-key-here'

session.driver = 'CodeIgniter\Session\Handlers\FileHandler'
session.cookieName = 'edquill_prod_session'
session.expiration = 7200
session.savePath = WRITEPATH . 'session'
session.matchIP = false
session.timeToUpdate = 300
session.regenerateDestroy = false

cors.allowedOrigins = 'https://yourdomain.com'
logger.threshold = 1
```

### Important Notes:

1. **Never commit `.env` file to version control** - Add it to `.gitignore`
2. **Generate encryption key** using: `php spark key:generate`
3. **Update all placeholder values** with your actual GoDaddy credentials
4. **Use HTTPS URLs** for production and test environments
5. **Set `DBDebug = false`** for production to hide database errors

---

## Frontend Environment Files

### Location
Environment files are located in: `web/src/environments/`

### Files Available

1. **`environment.ts`** - Default/local development
2. **`environment.local.ts`** - Local development override
3. **`environment.dev.ts`** - Development environment
4. **`environment.test.ts`** - Test environment (NEW - for GoDaddy test)
5. **`environment.staging.ts`** - Staging environment
6. **`environment.prod.ts`** - Production environment

### Updating Environment Files

#### For TEST Environment:

Edit `web/src/environments/environment.test.ts`:

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

#### For PRODUCTION Environment:

Edit `web/src/environments/environment.prod.ts`:

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

### Building with Environment Files

#### For TEST:
```bash
cd web
ng build --configuration=test
```

#### For PRODUCTION:
```bash
cd web
ng build --configuration=production
```

---

## Quick Setup Checklist

### Backend Setup:
- [ ] Create `.env` file in `rista_ci4/` root
- [ ] Copy template from `.env.test` or `.env.production`
- [ ] Update `app.baseURL` with your domain
- [ ] Update database credentials
- [ ] Generate encryption key: `php spark key:generate`
- [ ] Set `DBDebug = false` for production
- [ ] Verify `.env` is in `.gitignore`

### Frontend Setup:
- [ ] Update `environment.test.ts` with test URLs
- [ ] Update `environment.prod.ts` with production URLs
- [ ] Verify `apiHost` matches backend URL
- [ ] Verify `webHost` matches frontend URL
- [ ] Build with correct configuration

### Deployment:
- [ ] Upload `.env` file to server (outside `public/`)
- [ ] Set file permissions: `chmod 644 .env`
- [ ] Upload built frontend files
- [ ] Test API connectivity
- [ ] Test frontend-backend communication
- [ ] Verify CORS settings

---

## Getting GoDaddy Credentials

### Database Credentials:

1. Log in to GoDaddy cPanel
2. Navigate to **MySQL Databases** or **Databases**
3. Find your database:
   - Database name: Usually `yourdomain_dbname`
   - Username: Usually `yourdomain_dbuser`
   - Hostname: Usually `localhost`
   - Port: Usually `3306`
4. Password: Set when creating database user

### Domain URLs:

- **Test URL**: `https://test.yourdomain.com` (if subdomain is set up)
- **Production URL**: `https://yourdomain.com`
- **Backend Path**: `/rista_ci4/public/`
- **Frontend Path**: `/web/` or root

---

## Security Best Practices

1. **Never commit `.env` files** - They contain sensitive credentials
2. **Use strong passwords** for database
3. **Generate unique encryption keys** for each environment
4. **Use HTTPS** for all production URLs
5. **Set `DBDebug = false`** in production
6. **Restrict file permissions**: `chmod 644 .env`
7. **Keep `.env` outside public directory**

---

## Troubleshooting

### Backend not reading .env file:
- Check file location (should be in `rista_ci4/` root)
- Verify file name is exactly `.env` (not `.env.txt`)
- Check file permissions
- Verify CodeIgniter 4 is reading environment variables

### Frontend using wrong environment:
- Check build command uses correct configuration
- Verify `fileReplacements` in `angular.json`
- Clear browser cache
- Check console logs for environment name

### Database connection errors:
- Verify credentials in `.env`
- Check database host (usually `localhost` on GoDaddy)
- Ensure database user has proper privileges
- Test connection using phpMyAdmin

---

**Need Help?** Refer to `DEPLOYMENT_GUIDE.md` for detailed deployment instructions.

