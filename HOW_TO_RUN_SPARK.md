# How to Run Spark Commands on GoDaddy

The error "Could not open input file: spark" means you're either:
1. Not in the correct directory
2. The spark file doesn't exist
3. Using wrong command syntax

## Solution 1: Navigate to Correct Directory First

**The `spark` file is in the `rista_ci4/` root directory.**

### Via SSH:
```bash
# First, navigate to your CodeIgniter root directory
cd /path/to/rista_ci4

# Verify spark file exists
ls -la spark

# Then run the command
php spark key:generate
```

### Via cPanel Terminal:
```bash
# Navigate to your directory (adjust path as needed)
cd ~/public_html/rista_ci4
# OR
cd ~/domains/yourdomain.com/public_html/rista_ci4

# Verify spark exists
ls spark

# Run command
php spark key:generate
```

## Solution 2: Use Full Path

If you can't navigate to the directory, use the full path:

```bash
php /path/to/rista_ci4/spark key:generate
```

**Find your path:**
```bash
# Find where you are
pwd

# Find spark file
find ~ -name "spark" -type f 2>/dev/null
```

## Solution 3: Verify Spark File Exists

**Check if spark file exists:**
```bash
ls -la /path/to/rista_ci4/spark
```

**If it doesn't exist:**
- The file might not have been uploaded
- Upload the `spark` file from your local `rista_ci4/` directory
- Make sure it has execute permissions: `chmod +x spark`

## Solution 4: Alternative - Generate Key Manually

If you can't run spark, you can generate an encryption key manually:

### Option A: Use Online Generator
1. Go to: https://randomkeygen.com/
2. Use "CodeIgniter Encryption Keys" section
3. Copy a 32-character hex key
4. Add to `.env`:
   ```env
   encryption.key = 'your-32-character-hex-key-here'
   ```

### Option B: Use PHP to Generate
Create a temporary file `generate_key.php` in `rista_ci4/` root:

```php
<?php
// Generate a random 32-byte key and encode as hex
$key = bin2hex(random_bytes(32));
echo "Generated encryption key:\n";
echo $key . "\n";
echo "\nAdd this to your .env file:\n";
echo "encryption.key = '" . $key . "'\n";
?>
```

Run it:
```bash
cd /path/to/rista_ci4
php generate_key.php
```

Then copy the output to your `.env` file.

### Option C: Use OpenSSL (if available)
```bash
openssl rand -hex 32
```

Copy the output to your `.env` file.

## Step-by-Step: Generate Encryption Key

### Method 1: Using Spark (Recommended)

```bash
# 1. Navigate to CodeIgniter root
cd /path/to/rista_ci4

# 2. Verify you're in the right place
ls -la | grep spark

# 3. Run the command
php spark key:generate

# 4. Copy the output key
# It will show something like:
# Encryption key: a1b2c3d4e5f6...
```

### Method 2: Manual Generation

1. **Generate key using PHP:**
   ```bash
   php -r "echo bin2hex(random_bytes(32));"
   ```

2. **Or create a file `generate_key.php`:**
   ```php
   <?php
   echo bin2hex(random_bytes(32));
   ?>
   ```
   ```bash
   php generate_key.php
   ```

3. **Add to `.env` file:**
   ```env
   encryption.key = 'your-generated-key-here'
   ```

## Common Mistakes

### ❌ Wrong: Running from wrong directory
```bash
cd public/
php spark key:generate  # ERROR: spark is not in public/
```

### ✅ Correct: Running from root directory
```bash
cd /path/to/rista_ci4
php spark key:generate  # SUCCESS
```

### ❌ Wrong: Missing php command
```bash
spark key:generate  # ERROR: spark is not executable directly
```

### ✅ Correct: Using php command
```bash
php spark key:generate  # SUCCESS
```

## Verify Your Setup

### Check 1: Find Your Directory Structure
```bash
# Find where you are
pwd

# List files in current directory
ls -la

# Look for these files:
# - spark (should be here)
# - app/ (directory)
# - public/ (directory)
# - .env (file)
```

### Check 2: Verify Spark File
```bash
# Check if spark exists
ls -la spark

# Check file permissions
file spark

# Should show: PHP script or executable
```

### Check 3: Test PHP
```bash
# Check PHP version
php -v

# Should show PHP 8.1 or higher
```

## Quick Reference

**Correct command format:**
```bash
cd /path/to/rista_ci4
php spark [command]
```

**Common commands:**
```bash
php spark key:generate          # Generate encryption key
php spark migrate               # Run database migrations
php spark cache:clear           # Clear cache
php spark routes                # List all routes
php spark env                   # Check environment
```

## If Spark Still Doesn't Work

1. **Check file exists:**
   ```bash
   find ~ -name "spark" -type f
   ```

2. **Check file permissions:**
   ```bash
   chmod +x spark
   ```

3. **Try full path:**
   ```bash
   php /full/path/to/rista_ci4/spark key:generate
   ```

4. **Use manual key generation** (see Solution 4 above)

## After Generating Key

1. **Copy the key** from the output
2. **Edit `.env` file:**
   ```env
   encryption.key = 'your-generated-key-here'
   ```
3. **Save the file**
4. **Test your API again**

---

**Most Common Issue:** Not being in the correct directory. Always `cd` to `rista_ci4/` root first!

