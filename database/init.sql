-- Benasque 25 Conference Participant Database Schema

CREATE TABLE IF NOT EXISTS participants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    interests TEXT, -- Comma-separated keywords
    description TEXT, -- General description/comments
    arxiv_links TEXT, -- JSON array of arXiv URLs
    photo_path TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Create index for faster searching
CREATE INDEX IF NOT EXISTS idx_participants_name ON participants(first_name, last_name);
CREATE INDEX IF NOT EXISTS idx_participants_email ON participants(email);

-- Trigger to update the updated_at timestamp
CREATE TRIGGER IF NOT EXISTS update_participants_timestamp
    AFTER UPDATE ON participants
BEGIN
    UPDATE participants SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;
