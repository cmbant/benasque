# Benasque 25 Conference Participant Directory

A web application for conference participants to share their information and submit talk proposals.

⚠️ **Security Notice**: This application has no authentication system. It should only be used with trusted participants via private links.

## Features

### Participant Directory
- Participant profiles with photos, research interests, and arXiv links
- Add/Edit functionality with local storage validation
- Sorting by name and filtering by interests
- Image upload with automatic resizing
- Direct signup links for easy registration

### Talk Submission System
- Flash talk submissions (2+1 min) - automatically accepted
- Contributed talk submissions (15+5 min) - require admin approval
- Talk title and abstract submission for contributed talks
- Admin interface for managing talk acceptance

### Technical Features
- No authentication required for participants
- SQLite database for easy deployment
- Responsive design for mobile and desktop

## Quick Start

### Local Development with Docker

**Start the server:**
```bash
docker-compose up -d
```

**Stop the server:**
```bash
docker-compose down
```

Access the application at http://localhost:8080

### Direct Signup Links

For easy participant registration, use these direct links:
- `http://yoursite.com/?signup=1`
- `http://yoursite.com/?add=1`

Both automatically open the registration form.

### Admin Features

- **Talks Management**: Access `talks.php` to review and approve contributed talk submissions

## Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for detailed deployment instructions.

## Structure

- `index.php` - Main participant directory
- `talks.php` - Admin interface for talk management
- `api/` - API endpoints for data operations
- `css/` - Stylesheets
- `js/` - JavaScript files
- `uploads/` - User uploaded images
- `database/` - SQLite database and migration scripts
- `utils/` - Utility classes (ArXiv API integration)
