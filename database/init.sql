-- Benasque 25 Conference Participant Database Schema

CREATE TABLE IF NOT EXISTS participants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    email_public INTEGER DEFAULT 0, -- 0 = private, 1 = public
    interests TEXT, -- Comma-separated keywords
    description TEXT, -- General description/comments
    arxiv_links TEXT, -- JSON array of arXiv objects with url and title fields
    photo_path TEXT,
    talk_flash INTEGER DEFAULT 0, -- 0 = no, 1 = yes (flash talk 2+1 min)
    talk_contributed INTEGER DEFAULT 0, -- 0 = no, 1 = yes (contributed talk 15+5 min)
    talk_title TEXT, -- Title for contributed talk
    talk_abstract TEXT, -- Abstract for contributed talk
    talk_flash_accepted INTEGER DEFAULT 1, -- Flash talks are always accepted
    talk_contributed_accepted INTEGER DEFAULT NULL, -- NULL = pending, 0 = rejected, 1 = accepted
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
