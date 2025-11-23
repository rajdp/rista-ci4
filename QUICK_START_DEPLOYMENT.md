# Quick Start: GoDaddy Deployment

This is a quick reference guide for deploying to GoDaddy. For detailed instructions, see `DEPLOYMENT_GUIDE.md`.

## 🚀 Quick Setup Steps

### 1. Backend (CodeIgniter 4) - 5 Minutes

1. **Create `.env` file** in `rista_ci4/` root:
   ```bash
   # Copy template
   cp .env.test .env  # for test environment
   # OR
   cp .env.production .env  # for production
   ```

2. **Edit `.env`** and update:
   - `app.baseURL` = Your GoDaddy URL
   - `database.default.*` = Your GoDaddy database credentials
   - `CI_ENVIRONMENT` = `development` (test) or `production`

3. **Generate encryption key:**
   ```bash
   php spark key:generate
   # Copy the key to .env file
   ```

4. **Upload to GoDaddy:**
   - Upload entire `rista_ci4/` folder
   - Ensure `.env` is uploaded (but not in public directory)

5. **Set permissions:**
   ```bash
   chmod -R 755 writable/
   ```

### 2. Frontend (Angular) - 3 Minutes

1. **Update environment file:**
   - Test: Edit `web/src/environments/environment.test.ts`
   - Production: Edit `web/src/environments/environment.prod.ts`
   
2. **Update URLs:**
   ```typescript
   apiHost: 'https://yourdomain.com/rista_ci4/public/',
   webHost: 'https://yourdomain.com/web',
   imgUrl: 'https://yourdomain.com/rista_ci4/public',
   ```

3. **Build:**
   ```bash
   cd web
   ng build --configuration=test      # for test
   ng build --configuration=production  # for production
   ```

4. **Upload:**
   - Upload contents of `dist/web/` to GoDaddy
   - Create `.htaccess` for Angular routing (see DEPLOYMENT_GUIDE.md)

## 📋 Environment Variables Checklist

### Backend (.env)
- [ ] `app.baseURL` - Your GoDaddy URL
- [ ] `CI_ENVIRONMENT` - `development` or `production`
- [ ] `database.default.hostname` - Usually `localhost`
- [ ] `database.default.database` - Your database name
- [ ] `database.default.username` - Your database user
- [ ] `database.default.password` - Your database password
- [ ] `encryption.key` - Generated key
- [ ] `database.default.DBDebug` - `false` for production

### Frontend (environment files)
- [ ] `apiHost` - Backend API URL
- [ ] `webHost` - Frontend URL
- [ ] `imgUrl` - Image assets URL
- [ ] `production` - `true` for production, `false` for test

## 🔑 Getting GoDaddy Credentials

1. **Login to cPanel**
2. **MySQL Databases** section
3. Find your database credentials:
   - Hostname: `localhost`
   - Database name: `yourdomain_dbname`
   - Username: `yourdomain_dbuser`
   - Password: (the one you set)

## ⚡ Build Commands

```bash
# Backend - Generate encryption key
php spark key:generate

# Frontend - Build for test
cd web && ng build --configuration=test

# Frontend - Build for production
cd web && ng build --configuration=production
```

## 🐛 Common Issues

| Issue | Solution |
|-------|----------|
| 500 Error | Check file permissions: `chmod -R 755 writable/` |
| Database Error | Verify credentials in `.env` |
| CORS Error | Update `cors.allowedOrigins` in `.env` |
| 404 on Routes | Add `.htaccess` for Angular routing |
| Wrong Environment | Check build command uses correct config |

## 📚 Full Documentation

- **Detailed Guide**: `DEPLOYMENT_GUIDE.md`
- **Environment Setup**: `ENV_SETUP_INSTRUCTIONS.md`

---

**Need Help?** Check the troubleshooting section in `DEPLOYMENT_GUIDE.md`

