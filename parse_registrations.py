#!/usr/bin/env python3
# /// script
# requires-python = ">=3.10"
# dependencies = [
#     "beautifulsoup4>=4.12.0",
#     "requests>=2.31.0",
#     "lxml>=4.9.0"
# ]
# ///
"""
Benasque Registration Parser

Parses HTML file from orgaccept.pl to extract participant registration data.
Supports local parsing and remote database updates via web API.

Usage:
    uv run parse_registrations.py orgaccept.pl.html
    uv run parse_registrations.py orgaccept.pl.html --update-remote --url https://your-site.com
"""

import argparse
import json
import re
import sys
from datetime import datetime
from pathlib import Path
from typing import Dict, List, Optional, Tuple
import requests
from bs4 import BeautifulSoup


class RegistrationParser:
    """Parser for Benasque registration HTML files."""

    def __init__(self):
        self.current_year = datetime.now().year

    def parse_html_file(self, file_path: str) -> List[Dict]:
        """Parse HTML file and extract registration data."""
        try:
            with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
                content = f.read()
        except UnicodeDecodeError:
            # Try with different encodings
            for encoding in ['iso-8859-1', 'windows-1252', 'latin1']:
                try:
                    with open(file_path, 'r', encoding=encoding) as f:
                        content = f.read()
                    break
                except UnicodeDecodeError:
                    continue
            else:
                raise Exception("Could not decode file with any common encoding")

        return self._parse_html_content(content)

    def _parse_html_content(self, html_content: str) -> List[Dict]:
        """Parse HTML content and extract participant data."""
        soup = BeautifulSoup(html_content, 'html.parser')
        participants = []

        # Check if this is a browser-saved HTML file with embedded content
        if self._is_browser_saved_html(soup):
            return self._parse_browser_saved_html(soup)

        # Original parsing logic for direct HTML files
        return self._parse_direct_html(soup)

    def _is_browser_saved_html(self, soup: BeautifulSoup) -> bool:
        """Check if this is a browser-saved HTML file with embedded content."""
        return bool(soup.find('span', class_='html-tag') or soup.find('td', class_='line-content'))

    def _parse_browser_saved_html(self, soup: BeautifulSoup) -> List[Dict]:
        """Parse browser-saved HTML content that's embedded in spans."""
        participants = []

        # Extract the actual HTML content from the spans
        html_content_spans = soup.find_all('td', class_='line-content')
        if not html_content_spans:
            return participants

        # Reconstruct the original HTML from the spans
        reconstructed_html = ""
        for span in html_content_spans:
            # Get text content and decode HTML entities
            content = span.get_text()
            reconstructed_html += content + "\n"

        # Parse the reconstructed HTML
        reconstructed_soup = BeautifulSoup(reconstructed_html, 'html.parser')
        return self._parse_direct_html(reconstructed_soup)

    def _parse_direct_html(self, soup: BeautifulSoup) -> List[Dict]:
        """Parse direct HTML content for participant data."""
        participants = []

        # Find all table rows with participant data
        # Look for patterns like <td valign=top><b><font color=...>STATUS</font></b></td>
        rows = soup.find_all('tr')

        current_participant = None

        for row in rows:
            cells = row.find_all('td')
            if len(cells) < 2:
                continue

            # Check if this row starts a new participant entry
            first_cell = cells[0]
            status_element = first_cell.find('font')

            if status_element and status_element.get('color'):
                status_text = status_element.get_text(strip=True)

                # Skip rejected applications as requested
                if status_text == 'REJ':
                    current_participant = None
                    continue

                # Map status codes
                status_map = {
                    'ACC': 'ACCEPTED',
                    'VIP': 'INVITED',
                    'CANC': 'CANCELLED'
                }

                if status_text in status_map:
                    current_participant = {
                        'status': status_map[status_text],
                        'first_name': '',
                        'last_name': '',
                        'email': '',
                        'affiliation': '',
                        'start_date': '',
                        'end_date': ''
                    }

                    # Extract participant info from subsequent cells
                    if len(cells) >= 3:
                        self._extract_participant_info(cells[1:], current_participant)
                        if current_participant['email']:  # Only add if we have an email
                            participants.append(current_participant)

        return participants

    def _extract_participant_info(self, cells: List, participant: Dict):
        """Extract participant information from table cells."""
        # Second cell typically contains name, email, and affiliation
        if len(cells) >= 1:
            info_cell = cells[0]

            # Extract email from mailto link
            email_link = info_cell.find('a', href=re.compile(r'mailto:'))
            if email_link:
                href = email_link.get('href', '')
                email_match = re.search(r'mailto:([^"]+)', href)
                if email_match:
                    participant['email'] = email_match.group(1).strip()

            # Extract name and affiliation from text content
            text_content = info_cell.get_text()

            # Name is typically in bold tags or between <b> tags
            name_element = info_cell.find('b')
            if name_element:
                name_text = name_element.get_text(strip=True)
                # Remove trailing colon if present
                full_name = name_text.rstrip(':')

                # Parse first and last names (format: "LASTNAME, Firstname")
                if ',' in full_name:
                    name_parts = full_name.split(',', 1)
                    participant['last_name'] = name_parts[0].strip()
                    participant['first_name'] = name_parts[1].strip()
                else:
                    # Fallback: assume last word is last name
                    name_parts = full_name.strip().split()
                    if len(name_parts) >= 2:
                        participant['first_name'] = ' '.join(name_parts[:-1])
                        participant['last_name'] = name_parts[-1]
                    else:
                        participant['first_name'] = full_name
                        participant['last_name'] = ''

            # Affiliation is usually after the name, in parentheses or after the name
            # Look for pattern: NAME: affiliation (country)
            if ':' in text_content:
                parts = text_content.split(':', 1)
                if len(parts) > 1:
                    affiliation_part = parts[1].strip()
                    # Remove email if it appears in the text
                    affiliation_part = re.sub(r'\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b', '', affiliation_part)
                    # Strip empty parentheses from the end
                    affiliation_part = re.sub(r'\s*\(\s*\)\s*$', '', affiliation_part)
                    participant['affiliation'] = affiliation_part.strip()

        # Third cell typically contains dates
        if len(cells) >= 2:
            date_cell = cells[1]
            date_text = date_cell.get_text(strip=True)

            # Parse dates in format "Jul 20/Aug 02" or "Jul 28/Aug 01"
            date_match = re.search(r'([A-Za-z]{3})\s+(\d{1,2})/([A-Za-z]{3})\s+(\d{1,2})', date_text)
            if date_match:
                start_month, start_day, end_month, end_day = date_match.groups()
                participant['start_date'] = f"{start_month} {start_day}"
                participant['end_date'] = f"{end_month} {end_day}"

    def save_to_json(self, participants: List[Dict], output_file: str):
        """Save parsed data to JSON file."""
        with open(output_file, 'w', encoding='utf-8') as f:
            json.dump(participants, f, indent=2, ensure_ascii=False)

    def update_remote_database(self, participants: List[Dict], base_url: str) -> bool:
        """Update remote database via web API."""
        api_url = f"{base_url.rstrip('/')}/api/update_registrations.php"

        try:
            response = requests.post(api_url, json={
                'registrations': participants
            }, headers={
                'Content-Type': 'application/json'
            })

            if response.status_code == 200:
                result = response.json()
                if result.get('success'):
                    print(f"✓ Successfully updated {result.get('updated', 0)} registrations")
                    return True
                else:
                    print(f"✗ API error: {result.get('message', 'Unknown error')}")
                    return False
            else:
                print(f"✗ HTTP error: {response.status_code}")
                return False

        except requests.RequestException as e:
            print(f"✗ Network error: {e}")
            return False
        except json.JSONDecodeError as e:
            print(f"✗ JSON decode error: {e}")
            return False


def main():
    parser = argparse.ArgumentParser(description='Parse Benasque registration HTML file')
    parser.add_argument('html_file', help='Path to the HTML file to parse')
    parser.add_argument('--output', '-o', help='Output JSON file (default: registrations.json)',
                       default='registrations.json')
    parser.add_argument('--update-remote', action='store_true',
                       help='Update remote database via API')
    parser.add_argument('--url', help='Base URL for remote API (required with --update-remote)')
    parser.add_argument('--verbose', '-v', action='store_true', help='Verbose output')

    args = parser.parse_args()

    if args.update_remote and not args.url:
        print("Error: --url is required when using --update-remote")
        sys.exit(1)

    if not Path(args.html_file).exists():
        print(f"Error: File {args.html_file} not found")
        sys.exit(1)

    # Parse the HTML file
    print(f"Parsing {args.html_file}...")
    parser_instance = RegistrationParser()

    try:
        participants = parser_instance.parse_html_file(args.html_file)

        if args.verbose:
            print(f"Found {len(participants)} participants:")
            for p in participants:
                print(f"  {p['status']}: {p['name']} ({p['email']}) - {p['start_date']}/{p['end_date']}")
        else:
            print(f"Found {len(participants)} participants (excluding rejected applications)")

        # Save to JSON file
        parser_instance.save_to_json(participants, args.output)
        print(f"✓ Saved data to {args.output}")

        # Update remote database if requested
        if args.update_remote:
            print(f"Updating remote database at {args.url}...")
            success = parser_instance.update_remote_database(participants, args.url)
            if not success:
                sys.exit(1)

    except Exception as e:
        print(f"Error: {e}")
        sys.exit(1)


if __name__ == '__main__':
    main()
