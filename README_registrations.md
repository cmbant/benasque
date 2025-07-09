# Registration Parser

This tool parses HTML files from the conference registration system (orgaccept.pl) and extracts participant registration data including status (ACC/VIP/CANC), names, emails, affiliations, and attendance dates.

## Features

- Parses HTML files from orgaccept.pl
- Extracts participant data (excluding rejected applications)
- Supports local JSON export
- Can update remote database via web API
- Web interface to view registration status

## Quick Start

1. **Save HTML file correctly**: ⚠️ **CRITICAL** - Right-click on orgaccept.pl page → "View Source" → Copy and save as .html file (default "Save As" will NOT work)
2. **Parse the file**: `uv run parse_registrations.py orgaccept.pl.html --verbose`
3. **Set up web database**: `php migrate_registrations.php`
4. **Update remote database**: `uv run parse_registrations.py orgaccept.pl.html --update-remote --url https://your-site.com`
5. **View results**: Navigate to `https://your-site.com/registrations.php`

## Setup

### Python Script Setup

1. Install uv (if not already installed):
```bash
curl -LsSf https://astral.sh/uv/install.sh | sh
```

2. The script includes dependencies in the header and will automatically install them when run with `uv run`

### Web Database Setup

1. Run the migration to create the registrations table:
```bash
php migrate_registrations.php
```

2. Ensure your web server has write permissions to the database directory.

## Usage

### Local Parsing Only

Parse an HTML file and save to JSON:
```bash
uv run parse_registrations.py orgaccept.pl.html
```

Save to a specific file:
```bash
uv run parse_registrations.py orgaccept.pl.html --output my_registrations.json
```

Verbose output:
```bash
uv run parse_registrations.py orgaccept.pl.html --verbose
```

### Update Remote Database

Parse and update the web database:
```bash
uv run parse_registrations.py orgaccept.pl.html --update-remote --url https://your-site.com
```

### View Registration Data

Access the web interface at:
```
https://your-site.com/registrations.php
```

## Data Structure

The parser extracts the following information for each participant:

- **Status**: ACCEPTED, INVITED, or CANCELLED (REJ entries are skipped)
- **First Name**: Extracted first name
- **Last Name**: Extracted last name
- **Email**: Email address
- **Affiliation**: Institution and country (empty parentheses automatically stripped)
- **Start Date**: Conference start date (e.g., "Jul 20")
- **End Date**: Conference end date (e.g., "Aug 02")

## Database Schema

The `registrations` table contains:

```sql
CREATE TABLE registrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    status TEXT NOT NULL CHECK (status IN ('ACCEPTED', 'INVITED', 'CANCELLED')),
    affiliation TEXT,
    start_date TEXT,
    end_date TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

## API Endpoints

### POST /api/update_registrations.php

Updates registration data from parsed HTML.

**Request Body:**
```json
{
    "registrations": [
        {
            "status": "ACCEPTED",
            "name": "Smith, John",
            "email": "john.smith@university.edu",
            "affiliation": "University of Example (United States)",
            "start_date": "Jul 20",
            "end_date": "Aug 02"
        }
    ]
}
```

**Response:**
```json
{
    "success": true,
    "updated": 1,
    "errors": [],
    "total_processed": 1,
    "stats": {
        "ACCEPTED": 65,
        "INVITED": 15,
        "CANCELLED": 13,
        "TOTAL": 93
    }
}
```

## HTML File Format

**IMPORTANT**: When saving the HTML file from orgaccept.pl, you must use "View Source" and then copy/save the source code. The default browser "Save As" creates a processed version that the parser cannot read properly.

The parser expects HTML files with the following structure:

```html
<tr>
    <td valign=top><b><font color=green>ACC</font></b></td>
    <td style="vertical-align: middle; width: 45%;">
        <a href=mailto:email@domain.com>
            <b>LASTNAME, Firstname:</b>
        </a>
        Institution Name (Country)
    </td>
    <td style="vertical-align: middle; width: 15%;">Jul 20/Aug 02</td>
    <td width=7%>&nbsp;</td>
</tr>
```

## Status Mapping

- **ACC** → ACCEPTED (green)
- **VIP** → INVITED (blue)
- **CANC** → CANCELLED (orange)
- **REJ** → Skipped (not imported)

## Error Handling

The parser handles various encoding issues and malformed HTML gracefully:

- Tries multiple character encodings (UTF-8, ISO-8859-1, Windows-1252, Latin1)
- Skips entries with missing required fields
- Validates email formats
- Reports errors without stopping the entire process

## Integration with Existing System

This registration system is designed to work alongside the existing participant database. The registration data is stored in a separate table and can be cross-referenced with the main participants table using email addresses.

The main participant directory automatically integrates registration dates for date-based filtering:
- **Week filters**: Show participants present during specific weeks
- **Only Week filters**: Show participants present exclusively in one week
- **Individual day filters**: Show participants present on specific dates
- **No dates**: Participants without registration dates appear in all week/day filters

To link registration status with participant profiles, you can join the tables:

```sql
SELECT p.*, r.status as registration_status, r.start_date, r.end_date
FROM participants p
LEFT JOIN registrations r ON p.email = r.email
WHERE r.status IN ('ACCEPTED', 'INVITED');
```
