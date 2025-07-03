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
   - Direct signup: http://localhost:8080/?signup=1
   - Talks management: http://localhost:8080/talks.php

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
   - ⚠️ **No authentication**: This application has no login system - only share with trusted users
   - Remove `test_setup.php` after testing
   - Consider adding password protection to `talks.php` admin interface
   - Use private/unlisted URLs to prevent unauthorized access
   - Regularly backup the SQLite database file

### File Structure on Server
```
your-domain.com/
├── index.php                    # Main participant directory
├── talks.php                    # Admin interface for talk management

├── test_setup.php              # Setup verification (remove after testing)
├── config.php                  # Configuration file
├── .htaccess                   # Apache configuration
├── api/
│   ├── get_participant.php     # API to get participant data
│   ├── save_participant.php    # API to save participant data
│   └── update_talk_status.php  # API to update talk acceptance status
├── css/
│   └── style.css              # Styles
├── js/
│   └── app.js                 # JavaScript functionality
├── database/
│   ├── Database.php           # Database class
│   ├── init.sql              # Database schema
│   └── benasque25.db         # SQLite database (created automatically)
├── utils/
│   └── ArxivAPI.php          # ArXiv integration for paper titles
└── uploads/                  # User uploaded photos
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

**Image upload limits** (set in PHP configuration):
- `upload_max_filesize`: Maximum file size
- `post_max_size`: Maximum POST data size
- Contact hosting provider to adjust these limits if needed

**Image resize dimensions** (in `api/save_participant.php`):
- `$maxWidth` and `$maxHeight` in the `resizeImage()` function

**Database location** (in `database/Database.php`):
- Change the `$dbPath` parameter if needed

## Talk Management Features

### For Participants

**Direct signup links** for easy registration:
- `yoursite.com/?signup=1` - Opens registration form automatically
- `yoursite.com/?add=1` - Alternative signup link

**Talk submission types**:
- **Flash talks (2+1 min)**: Automatically accepted upon submission
- **Contributed talks (15+5 min)**: Require admin approval, include title and abstract

### For Administrators

**Admin interface**: Access `yoursite.com/talks.php` to:
- View all talk submissions with participant details
- Filter by talk type and acceptance status
- Approve/reject contributed talk submissions
- Export talk data to CSV
- View real-time statistics

**Admin workflow**:
1. Flash talks are automatically accepted (no action needed)
2. Contributed talks start as "Pending" and require admin review
3. Use Admin Mode toggle to show approval controls
4. Select Accept/Reject for contributed talks
5. Save all changes with bulk update

**Filtering options**:
- All Submissions
- Flash Talks
- Contributed Talks
- Contributed: Accepted
- Contributed: Pending
- Contributed: Rejected
