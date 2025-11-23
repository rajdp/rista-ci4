# Check PHP Error Log

You found `php_error.log` - let's check it for errors.

## Step 1: View the Error Log

**Check the PHP error log:**
```bash
tail -100 php_error.log
```

**Or view the entire file:**
```bash
cat php_error.log
```

**Look for recent errors** - they'll be at the bottom of the file.

## Step 2: Check if CodeIgniter Logs Exist

**Check for CodeIgniter log files:**
```bash
ls -la *.log
```

**If no CodeIgniter logs exist**, it might mean:
- CodeIgniter hasn't written logs yet
- Log directory permissions issue
- CodeIgniter is failing before it can write logs

## Step 3: Check Log Directory Permissions

**Verify permissions:**
```bash
cd ..
ls -la
# Check if writable/ and writable/logs/ are writable

# Set permissions if needed
chmod -R 755 writable/
chmod -R 755 writable/logs/
```

## Step 4: Check CodeIgniter Logger Configuration

**Check if logger is configured:**
```bash
cd ../app/Config
grep -A 5 "Logger" Logger.php
```

## Step 5: Common Issues

### Issue 1: No CodeIgniter Logs

**If only `php_error.log` exists:**
- CodeIgniter might be failing before it can write logs
- Check `php_error.log` for the actual error
- This log contains PHP-level errors

### Issue 2: Permission Issues

**Fix:**
```bash
cd /home/gy8f5ipoapsn/public_html/edquillcrmlms/rista_ci4/
chmod -R 755 writable/
chmod -R 755 writable/logs/
```

### Issue 3: Logger Not Configured

**Check `app/Config/Logger.php`:**
- Should have threshold set
- Should have path configured

## Next Steps

1. **Check `php_error.log`** - This will show PHP-level errors
2. **Share the error message** from the log
3. **Check permissions** if logs aren't being written

---

**The `php_error.log` file should contain the error message you need!**

