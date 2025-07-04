<?php

class Database
{
    private $pdo;
    private $dbPath;

    public function __construct($dbPath = null)
    {
        if ($dbPath === null) {
            // Use __DIR__ for reliable path resolution
            $this->dbPath = __DIR__ . '/benasque25.db';
        } else {
            $this->dbPath = $dbPath;
        }
        $this->connect();
        $this->initializeSchema();
    }

    private function connect()
    {
        try {
            // Ensure the database directory exists and is writable
            $dbDir = dirname($this->dbPath);
            if (!is_dir($dbDir)) {
                throw new Exception("Database directory does not exist: $dbDir");
            }

            if (!is_writable($dbDir)) {
                throw new Exception("Database directory is not writable: $dbDir (current permissions: " . substr(sprintf('%o', fileperms($dbDir)), -4) . ")");
            }

            $this->pdo = new PDO("sqlite:" . $this->dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage() . " (Path: " . $this->dbPath . ")");
        }
    }

    private function initializeSchema()
    {
        $sqlPath = __DIR__ . '/init.sql';

        if (!file_exists($sqlPath)) {
            throw new Exception('Could not find init.sql file: ' . $sqlPath);
        }

        $sql = file_get_contents($sqlPath);
        if ($sql === false) {
            throw new Exception('Could not read init.sql file: ' . $sqlPath);
        }

        $this->pdo->exec($sql);
    }

    public function getPDO()
    {
        return $this->pdo;
    }

    public function getAllParticipants()
    {
        $stmt = $this->pdo->query("SELECT * FROM participants ORDER BY first_name, last_name");
        return $stmt->fetchAll();
    }

    public function getParticipantByEmail($email)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM participants WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function addParticipant($data)
    {
        $sql = "INSERT INTO participants (first_name, last_name, email, email_public, interests, description, arxiv_links, photo_path, talk_flash, talk_contributed, talk_title, talk_abstract, talk_flash_accepted, talk_contributed_accepted)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['email_public'] ?? 0,
            $data['interests'],
            $data['description'],
            $data['arxiv_links'],
            $data['photo_path'] ?? null,
            $data['talk_flash'] ?? 0,
            $data['talk_contributed'] ?? 0,
            $data['talk_title'] ?? null,
            $data['talk_abstract'] ?? null,
            $data['talk_flash'] ?? 0 ? 1 : 0, // Flash talks are always accepted if submitted
            null  // Contributed talks start as pending
        ]);
    }

    public function updateParticipant($email, $data)
    {
        // Get current acceptance status to preserve admin decisions
        $current = $this->getParticipantByEmail($email);
        $currentContribAccepted = $current['talk_contributed_accepted'] ?? null;

        // Flash talks are always accepted if submitted, rejected if removed
        $flashAccepted = ($data['talk_flash'] ?? 0) ? 1 : 0;

        // Contributed talks preserve admin decisions, set to pending if newly added
        $contribAccepted = ($data['talk_contributed'] ?? 0) ? $currentContribAccepted : null;
        if (($data['talk_contributed'] ?? 0) && !($current['talk_contributed'] ?? 0)) {
            $contribAccepted = null; // New contributed talk starts as pending
        }

        $sql = "UPDATE participants SET first_name = ?, last_name = ?, email_public = ?, interests = ?,
                description = ?, arxiv_links = ?, photo_path = ?, talk_flash = ?, talk_contributed = ?, talk_title = ?, talk_abstract = ?, talk_flash_accepted = ?, talk_contributed_accepted = ? WHERE email = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['email_public'] ?? 0,
            $data['interests'],
            $data['description'],
            $data['arxiv_links'],
            $data['photo_path'],
            $data['talk_flash'] ?? 0,
            $data['talk_contributed'] ?? 0,
            $data['talk_title'] ?? null,
            $data['talk_abstract'] ?? null,
            $flashAccepted,
            $contribAccepted,
            $email
        ]);
    }

    public function deleteParticipant($email)
    {
        $stmt = $this->pdo->prepare("DELETE FROM participants WHERE email = ?");
        return $stmt->execute([$email]);
    }

    public function getAllInterests()
    {
        $stmt = $this->pdo->query("SELECT interests FROM participants WHERE interests IS NOT NULL AND interests != ''");
        $allInterests = [];
        while ($row = $stmt->fetch()) {
            $interests = explode(',', $row['interests']);
            foreach ($interests as $interest) {
                $interest = trim($interest);
                if ($interest && !in_array($interest, $allInterests)) {
                    $allInterests[] = $interest;
                }
            }
        }
        sort($allInterests);
        return $allInterests;
    }
}
