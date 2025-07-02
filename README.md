# Benasque 25 Conference Participant Directory

A simple web application for conference participants to share their information including photos, research interests, and recent papers.

## Features

- Participant directory with photo, interests, and arXiv links
- Add/Edit functionality with local storage validation
- Sorting by name and filtering by interests
- Image upload with automatic resizing
- No authentication required
- SQLite database for easy deployment

## Local Development with Docker

```bash
docker-compose up -d
```

Access the application at http://localhost:8080

## Deployment

Upload all files to your shared hosting server with PHP support. The SQLite database will be created automatically.

## Structure

- `index.php` - Main application file
- `api/` - API endpoints for data operations
- `css/` - Stylesheets
- `js/` - JavaScript files
- `uploads/` - User uploaded images
- `database/` - SQLite database file
