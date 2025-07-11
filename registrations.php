<?php

/**
 * Registration Status Viewer
 *
 * Displays registration data parsed from orgaccept.pl HTML files
 */

require_once 'database/Database.php';

// Load configuration
$config = require 'config.php';

try {
    $db = new Database();
    $pdo = $db->getPDO();

    // Check if registrations table exists
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='registrations'");
    $tableExists = $stmt->fetch();

    if (!$tableExists) {
        $error = "Registrations table does not exist. Please run migrate_registrations.php first.";
    } else {
        // Get statistics
        $statsStmt = $pdo->query("
            SELECT status, COUNT(*) as count
            FROM registrations
            GROUP BY status
            ORDER BY status
        ");
        $stats = [];
        while ($row = $statsStmt->fetch()) {
            $stats[$row['status']] = $row['count'];
        }

        // Get all registrations
        $registrationsStmt = $pdo->query("
            SELECT * FROM registrations
            ORDER BY status, last_name, first_name
        ");
        $registrations = $registrationsStmt->fetchAll();
    }
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Status - <?= htmlspecialchars($config['conference_name'] ?? 'Conference') ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333;
            border-bottom: 2px solid #007cba;
            padding-bottom: 10px;
        }

        .stats {
            display: flex;
            gap: 20px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #007cba;
            min-width: 120px;
        }

        .stat-card.accepted {
            border-left-color: #28a745;
        }

        .stat-card.invited {
            border-left-color: #007bff;
        }

        .stat-card.cancelled {
            border-left-color: #ffc107;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
        }

        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status.accepted {
            background-color: #d4edda;
            color: #155724;
        }

        .status.invited {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status.cancelled {
            background-color: #fff3cd;
            color: #856404;
        }

        .email {
            color: #007cba;
            text-decoration: none;
        }

        .email:hover {
            text-decoration: underline;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }

        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }

        .filter-controls {
            margin: 20px 0;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-controls select,
        .filter-controls input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .last-updated {
            font-size: 12px;
            color: #666;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Registration Status</h1>

        <?php if (isset($error)): ?>
            <div class="error">
                <strong>Error:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php else: ?>

            <?php if (empty($registrations)): ?>
                <div class="info">
                    <strong>No registration data found.</strong><br>
                    Use the Python script to parse and import registration data:<br>
                    <code>uv run parse_registrations.py orgaccept.pl.html --update-remote --url https://<?= htmlspecialchars($_SERVER['HTTP_HOST']) ?></code>
                </div>
            <?php else: ?>

                <!-- Statistics -->
                <div class="stats">
                    <?php if (isset($stats['ACCEPTED'])): ?>
                        <div class="stat-card accepted">
                            <div class="stat-number"><?= $stats['ACCEPTED'] ?></div>
                            <div class="stat-label">Accepted</div>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($stats['INVITED'])): ?>
                        <div class="stat-card invited">
                            <div class="stat-number"><?= $stats['INVITED'] ?></div>
                            <div class="stat-label">Invited</div>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($stats['CANCELLED'])): ?>
                        <div class="stat-card cancelled">
                            <div class="stat-number"><?= $stats['CANCELLED'] ?></div>
                            <div class="stat-label">Cancelled</div>
                        </div>
                    <?php endif; ?>

                    <div class="stat-card">
                        <div class="stat-number"><?= array_sum($stats) ?></div>
                        <div class="stat-label">Total</div>
                    </div>
                </div>

                <!-- Filter Controls -->
                <div class="filter-controls">
                    <label for="statusFilter">Filter by status:</label>
                    <select id="statusFilter" onchange="filterTable()">
                        <option value="">All</option>
                        <option value="ACCEPTED">Accepted</option>
                        <option value="INVITED">Invited</option>
                        <option value="CANCELLED">Cancelled</option>
                    </select>

                    <label for="searchInput">Search:</label>
                    <input type="text" id="searchInput" placeholder="Name, email, or affiliation..." onkeyup="filterTable()" autocomplete="off">
                </div>

                <!-- Registration Table -->
                <div class="table-container">
                    <table id="registrationTable">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Affiliation</th>
                                <th>Dates</th>
                                <th>Last Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrations as $reg): ?>
                                <tr>
                                    <td>
                                        <span class="status <?= strtolower($reg['status']) ?>">
                                            <?= htmlspecialchars($reg['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars(trim(($reg['first_name'] ?? '') . ' ' . ($reg['last_name'] ?? ''))) ?></td>
                                    <td>
                                        <a href="mailto:<?= htmlspecialchars($reg['email']) ?>" class="email">
                                            <?= htmlspecialchars($reg['email']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($reg['affiliation'] ?? '') ?></td>
                                    <td>
                                        <?php if ($reg['start_date'] && $reg['end_date']): ?>
                                            <?= htmlspecialchars($reg['start_date']) ?> - <?= htmlspecialchars($reg['end_date']) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($reg['updated_at']): ?>
                                            <?= date('M j, Y H:i', strtotime($reg['updated_at'])) ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="last-updated">
                    Last updated: <?= date('Y-m-d H:i:s') ?>
                </div>

            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        function filterTable() {
            const statusFilter = document.getElementById('statusFilter').value;
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const table = document.getElementById('registrationTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const status = row.cells[0].textContent.trim();
                const name = row.cells[1].textContent.toLowerCase();
                const email = row.cells[2].textContent.toLowerCase();
                const affiliation = row.cells[3].textContent.toLowerCase();

                const statusMatch = !statusFilter || status === statusFilter;
                const searchMatch = !searchInput ||
                    name.includes(searchInput) ||
                    email.includes(searchInput) ||
                    affiliation.includes(searchInput);

                row.style.display = statusMatch && searchMatch ? '' : 'none';
            }
        }
    </script>
</body>

</html>