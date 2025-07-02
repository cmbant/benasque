# Deployment Guide for Benasque 25 Conference Website

## Local Testing with Docker

1. **Prerequisites**
   - Docker and Docker Compose installed
   - Git (to clone/download the project)

2. **Start the application**
   ```bash
   docker-compose up -d
   ```

3. **Access the application**
   - Main application: http://localhost:8080
   - Setup test: http://localhost:8080/test_setup.php

4. **Stop the application**
   ```bash
   docker-compose down
   ```

## Shared Hosting Deployment

### Requirements
- PHP 7.4 or higher
- SQLite support (usually enabled by default)
- GD extension for image processing
- Write permissions for uploads and database directories

### Deployment Steps

1. **Upload files**
   - Upload all files to your web hosting directory (usually `public_html` or `www`)
   - Ensure the directory structure is preserved

2. **Set permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 database/
   chmod 644 *.php
   chmod 644 css/*.css
   chmod 644 js/*.js
   ```

3. **Test the setup**
   - Visit `yoursite.com/test_setup.php` to verify everything is working
   - All tests should show ✓ (green checkmarks)

4. **Security considerations**
   - Remove `test_setup.php` after testing
   - Consider adding password protection to the admin area if needed
   - Regularly backup the SQLite database file

### File Structure on Server
```
your-domain.com/
├── index.php                 # Main application
├── .htaccess                # Apache configuration
├── api/
│   ├── get_participant.php  # API to get participant data
│   └── save_participant.php # API to save participant data
├── css/
│   └── style.css           # Styles
├── js/
│   └── app.js              # JavaScript functionality
├── database/
│   ├── Database.php        # Database class
│   ├── init.sql           # Database schema
│   └── benasque25.db      # SQLite database (created automatically)
└── uploads/               # User uploaded photos
```

### Troubleshooting

**Database issues:**
- Ensure the `database/` directory is writable
- Check that SQLite is enabled in PHP

**Image upload issues:**
- Verify `uploads/` directory is writable
- Check PHP upload limits in hosting control panel
- Ensure GD extension is enabled

**Permission errors:**
- Contact your hosting provider to set correct permissions
- Some hosts require 755 for directories and 644 for files

### Backup Strategy

1. **Database backup:**
   - Download `database/benasque25.db` regularly
   - This contains all participant data

2. **Image backup:**
   - Download the entire `uploads/` directory
   - Contains all participant photos

### Configuration Options

Edit these values in the relevant files if needed:

**Image upload limits** (in `.htaccess`):
- `upload_max_filesize`: Maximum file size
- `post_max_size`: Maximum POST data size

**Image resize dimensions** (in `api/save_participant.php`):
- `$maxWidth` and `$maxHeight` in the `resizeImage()` function

**Database location** (in `database/Database.php`):
- Change the `$dbPath` parameter if needed
