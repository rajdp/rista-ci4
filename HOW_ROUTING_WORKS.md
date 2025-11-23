# How CodeIgniter 4 Routing Works (No Installation Required)

## Short Answer

**No, you don't need to install CodeIgniter 4.** It's a file-based framework that works immediately when files are uploaded. The routing happens automatically through the framework's bootstrap process.

---

## How `/public` Knows Where to Route API Calls

### The Request Flow

When you access: `https://edserver.edquillcrm.com/public/user/login`

Here's what happens step-by-step:

```
1. Web Server (Apache/Nginx)
   ↓ Receives request for /public/user/login
   ↓ Checks .htaccess in public/ directory
   ↓ Rewrites URL to: /public/index.php/user/login

2. public/index.php (Entry Point)
   ↓ This is the "front controller" - ALL requests go through here
   ↓ Defines FCPATH = /path/to/public/
   ↓ Loads: app/Config/Paths.php

3. app/Config/Paths.php (Path Configuration)
   ↓ Defines where everything is located:
   ↓ - appDirectory = ../app (relative to Config folder)
   ↓ - systemDirectory = ../../vendor/codeigniter4/framework/system
   ↓ - writableDirectory = ../../writable
   ↓ Creates Paths object

4. Framework Bootstrap (vendor/codeigniter4/framework/system/Boot.php)
   ↓ Loads the CodeIgniter framework
   ↓ Initializes services (database, session, etc.)
   ↓ Reads environment from .env file
   ↓ Loads: app/Config/Routes.php

5. app/Config/Routes.php (Route Definitions)
   ↓ Contains all your API route mappings:
   ↓ $routes->post('user/login', 'User::login');
   ↓ Matches: /user/login → App\Controllers\User::login
   ↓ Routes to: app/Controllers/User.php → login() method

6. Controller Execution
   ↓ app/Controllers/User.php
   ↓ public function login() { ... }
   ↓ Returns JSON response
```

---

## Key Files That Make Routing Work

### 1. `public/index.php` (Entry Point)

**Location:** `rista_ci4/public/index.php`

**What it does:**
- Defines the front controller path
- Loads `app/Config/Paths.php` to know where everything is
- Bootstraps the CodeIgniter framework

**Key line:**
```php
require FCPATH . '../app/Config/Paths.php';
// This tells CodeIgniter where the app folder is (one level up from public/)
```

### 2. `app/Config/Paths.php` (Path Definitions)

**Location:** `rista_ci4/app/Config/Paths.php`

**What it does:**
- Defines all directory paths using **relative paths**
- Uses `__DIR__` to calculate paths dynamically
- No hardcoded absolute paths needed

**Key paths defined:**
```php
public string $appDirectory = __DIR__ . '/..';
// Points to: rista_ci4/app/

public string $systemDirectory = __DIR__ . '/../../vendor/codeigniter4/framework/system';
// Points to: rista_ci4/vendor/codeigniter4/framework/system/

public string $writableDirectory = __DIR__ . '/../../writable';
// Points to: rista_ci4/writable/
```

**Why this works:**
- Uses relative paths (`../` means "go up one directory")
- Works regardless of where you upload the files
- No configuration needed - it automatically finds everything

### 3. `app/Config/Routes.php` (Route Mappings)

**Location:** `rista_ci4/app/Config/Routes.php`

**What it does:**
- Maps URLs to Controller methods
- Defines which routes require authentication
- Groups related routes together

**Example route:**
```php
$routes->post('user/login', 'User::login');
```

**This means:**
- URL: `POST /public/user/login`
- Maps to: `App\Controllers\User` class
- Calls: `login()` method
- File: `app/Controllers/User.php`

### 4. `public/.htaccess` (URL Rewriting)

**Location:** `rista_ci4/public/.htaccess`

**What it does:**
- Rewrites all requests to go through `index.php`
- Enables clean URLs (removes `index.php` from URL)

**Key rule:**
```apache
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php/$1 [L]
```

**This means:**
- If file doesn't exist (`!-f`)
- And directory doesn't exist (`!-d`)
- Route everything to `index.php` with the path appended

**Example:**
- Request: `/public/user/login`
- Rewritten to: `/public/index.php/user/login`
- CodeIgniter extracts: `user/login` and routes it

---

## Why No Installation is Needed

### 1. **File-Based Framework**
- CodeIgniter 4 is just PHP files
- No database tables to create (unless you use migrations)
- No system-level installation
- Works as soon as files are uploaded

### 2. **Automatic Path Discovery**
- Uses relative paths (`../`, `../../`)
- Automatically finds `app/`, `vendor/`, `writable/` folders
- Works regardless of server location

### 3. **Bootstrap Process**
- `index.php` automatically:
  - Finds all necessary files
  - Loads configuration
  - Initializes the framework
  - Routes requests

### 4. **Configuration via .env**
- Settings stored in `.env` file (not installed)
- Database credentials
- Environment settings
- All loaded at runtime

---

## Example: How a Request is Routed

### Request: `POST https://edserver.edquillcrm.com/public/user/login`

**Step 1: Web Server**
```
Apache receives: /public/user/login
.htaccess rewrites to: /public/index.php/user/login
```

**Step 2: index.php**
```php
// public/index.php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
// FCPATH = /path/to/rista_ci4/public/

require FCPATH . '../app/Config/Paths.php';
// Loads: /path/to/rista_ci4/app/Config/Paths.php
```

**Step 3: Paths.php**
```php
// app/Config/Paths.php
public string $appDirectory = __DIR__ . '/..';
// Resolves to: /path/to/rista_ci4/app/
```

**Step 4: Framework Bootstrap**
```php
// Framework loads:
// - app/Config/Routes.php
// - app/Config/Database.php
// - app/Config/App.php
// - All other config files
```

**Step 5: Routes.php**
```php
// app/Config/Routes.php
$routes->post('user/login', 'User::login');
// Matches: user/login
// Routes to: App\Controllers\User::login
```

**Step 6: Controller**
```php
// app/Controllers/User.php
namespace App\Controllers;

class User extends BaseController {
    public function login() {
        // Handle login logic
        return $this->response->setJSON(['status' => 'success']);
    }
}
```

**Result:** JSON response sent back to client

---

## Directory Structure (Why It Works)

```
rista_ci4/
├── app/                    ← Application code
│   ├── Config/
│   │   ├── Paths.php      ← Defines where everything is
│   │   ├── Routes.php     ← Defines URL → Controller mapping
│   │   ├── Database.php   ← Database config
│   │   └── App.php        ← App config
│   └── Controllers/        ← Your API controllers
│       └── User.php       ← Handles /user/login
│
├── public/                  ← Web-accessible directory
│   ├── index.php          ← Entry point (ALL requests go here)
│   └── .htaccess          ← URL rewriting rules
│
├── vendor/                  ← CodeIgniter framework
│   └── codeigniter4/
│       └── framework/
│           └── system/    ← Framework core files
│
├── writable/               ← Logs, cache, sessions
│
└── .env                    ← Environment configuration
```

**Key Point:** The relative paths in `Paths.php` automatically resolve based on where `public/index.php` is located.

---

## What You DO Need to Configure

### 1. `.env` File (Required)
- Database credentials
- Base URL
- Encryption key

### 2. File Permissions (Required)
- `writable/` directory must be writable
- `public/` directory must be readable

### 3. `.htaccess` (Required for clean URLs)
- Must exist in `public/` directory
- Enables URL rewriting

### 4. Routes (Already Defined)
- Your routes are already in `app/Config/Routes.php`
- No additional configuration needed

---

## Common Questions

### Q: Do I need to run `composer install`?

**A:** Only if you're missing the `vendor/` folder. If you upload the complete `rista_ci4/` folder including `vendor/`, you don't need to run it.

### Q: Do I need to run migrations?

**A:** Only if you have new database tables to create. If you've imported a complete database, you can skip migrations.

### Q: How does it find the Controllers?

**A:** Through the namespace and autoloading:
- Route: `'User::login'`
- Namespace: `App\Controllers` (from Routes.php)
- Resolves to: `App\Controllers\User`
- File: `app/Controllers/User.php`
- Method: `login()`

### Q: What if I change the folder structure?

**A:** Update `app/Config/Paths.php` to reflect the new structure. The relative paths will need adjustment.

---

## Summary

**CodeIgniter 4 routing works because:**

1. ✅ **No installation needed** - It's just PHP files
2. ✅ **Automatic path discovery** - Uses relative paths to find everything
3. ✅ **Single entry point** - All requests go through `public/index.php`
4. ✅ **Route mapping** - `Routes.php` maps URLs to Controllers
5. ✅ **Framework bootstrap** - Automatically loads and initializes everything

**The magic is in:**
- `public/index.php` → Entry point
- `app/Config/Paths.php` → Knows where everything is
- `app/Config/Routes.php` → Knows which URL goes to which Controller
- `.htaccess` → Routes all requests to `index.php`

That's it! No installation, no database setup (for routing), just upload and configure `.env`.

