<?php
require_once 'database/Database.php';

// Load configuration
$config = require 'config.php';

// Helper functions for talk status display
function getStatusClass($status)
{
    if ($status === null) return 'pending';
    return $status ? 'accepted' : 'rejected';
}

function getStatusIcon($status)
{
    if ($status === null) return '⏳';
    return $status ? '✓' : '✗';
}

try {
    $db = new Database();
    $pdo = $db->getPDO();

    // Get count of all participants with profiles
    $countSql = "SELECT COUNT(*) as profile_count FROM participants";
    $countStmt = $pdo->query($countSql);
    $profileCount = $countStmt->fetch()['profile_count'];

    // Get all participants who have submitted talks with registration dates
    $sql = "SELECT p.first_name, p.last_name, p.email, p.talk_flash, p.talk_contributed, p.talk_title, p.talk_abstract,
                   p.talk_flash_accepted, p.talk_contributed_accepted,
                   r.start_date, r.end_date
            FROM participants p
            LEFT JOIN registrations r ON p.email = r.email
            WHERE p.talk_flash = 1 OR p.talk_contributed = 1
            ORDER BY p.last_name, p.first_name";
    $stmt = $pdo->query($sql);
    $talks = $stmt->fetchAll();
} catch (Exception $e) {
    $error = $e->getMessage();
    $talks = [];
    $profileCount = 0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submitted Talks</title>
    <link rel="stylesheet" href="css/style.css?<?= htmlspecialchars($config['cache_version']) ?>">
    <style>
        .talks-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
        }

        .talks-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .controls {
            background: white;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .sort-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .sort-controls label {
            font-weight: 500;
        }

        .sort-controls select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s ease;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a67d8;
        }

        .admin-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-controls label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        .admin-column {
            min-width: 200px;
        }

        .admin-select {
            padding: 0.25rem;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 0.9rem;
            margin: 0.1rem;
        }

        .admin-select.changed {
            background: #fff3cd;
            border-color: #ffc107;
        }

        .admin-select.saving {
            background: #e3f2fd;
            border-color: #2196f3;
        }

        .admin-select.saved {
            background: #e8f5e8;
            border-color: #4caf50;
        }

        .admin-select.error {
            background: #ffebee;
            border-color: #f44336;
        }

        .save-indicator {
            font-weight: 500;
        }

        .save-indicator.saving {
            color: #2196f3;
        }

        .save-indicator.saved {
            color: #4caf50;
        }

        .save-indicator.error {
            color: #f44336;
        }

        .talks-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        .talks-table table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .talks-table th {
            background: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #e9ecef;
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
        }

        .talks-table th:hover {
            background: #e9ecef;
        }

        .talks-table td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            vertical-align: top;
        }

        .talks-table tr:hover {
            background: #f8f9fa;
        }

        .talks-table a {
            color: #667eea;
            text-decoration: none;
        }

        .talks-table a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        /* Column width optimization */
        .talks-table th:nth-child(1),
        .talks-table td:nth-child(1) {
            width: 18%;
        }

        /* Talk Type */
        .talks-table th:nth-child(2),
        .talks-table td:nth-child(2) {
            width: 18%;
        }

        /* Title */
        .talks-table th:nth-child(3),
        .talks-table td:nth-child(3) {
            width: 25%;
        }

        /* Dates */
        .talks-table th:nth-child(4),
        .talks-table td:nth-child(4) {
            width: 10%;
        }

        /* Abstract */
        .talks-table th:nth-child(5),
        .talks-table td:nth-child(5) {
            width: 29%;
        }

        /* Abstract */

        .talk-type {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 500;
            margin: 0.1rem;
        }

        .talk-type.flash {
            background: #e3f2fd;
            color: #1976d2;
        }

        .talk-type.contributed {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .talk-type.accepted {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .talk-type.rejected {
            background: #ffebee;
            color: #c62828;
        }

        .talk-type.pending {
            background: #fff3e0;
            color: #f57c00;
        }

        .talk-abstract {
            word-wrap: break-word;
            line-height: 1.4;
            position: relative;
        }

        .abstract-content {
            display: block;
        }

        .abstract-content.truncated {
            max-height: 4.2em;
            /* Show ~3 lines */
            overflow: hidden;
            position: relative;
        }

        .abstract-content.truncated::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 50px;
            height: 1.4em;
            background: linear-gradient(to right, transparent, white 50%);
        }

        .abstract-toggle {
            background: none;
            border: none;
            color: #667eea;
            cursor: pointer;
            font-size: 0.9em;
            padding: 0.25rem 0;
            margin-top: 0.25rem;
            text-decoration: underline;
            display: block;
        }

        .abstract-toggle:hover {
            color: #764ba2;
        }

        .abstract-full {
            display: none;
        }

        .abstract-full.show {
            display: block;
        }

        /* Responsive design for mobile */
        @media (max-width: 768px) {
            .talks-table {
                font-size: 0.9em;
            }

            .talks-table th,
            .talks-table td {
                padding: 0.5rem;
            }

            .talks-table th:nth-child(1),
            .talks-table td:nth-child(1) {
                width: 20%;
            }

            .talks-table th:nth-child(2),
            .talks-table td:nth-child(2) {
                width: 20%;
            }

            .talks-table th:nth-child(3),
            .talks-table td:nth-child(3) {
                width: 20%;
            }

            .talks-table th:nth-child(4),
            .talks-table td:nth-child(4) {
                width: 15%;
            }

            .talks-table th:nth-child(5),
            .talks-table td:nth-child(5) {
                width: 25%;
            }
        }

        @media (max-width: 480px) {

            .talks-table th:nth-child(2),
            .talks-table td:nth-child(2) {
                display: none;
                /* Hide talk type on very small screens */
            }

            .talks-table th:nth-child(4),
            .talks-table td:nth-child(4) {
                display: none;
                /* Hide registration dates on very small screens */
            }

            .talks-table th:nth-child(1),
            .talks-table td:nth-child(1) {
                width: 35%;
            }

            .talks-table th:nth-child(3),
            .talks-table td:nth-child(3) {
                width: 35%;
            }

            .talks-table th:nth-child(5),
            .talks-table td:nth-child(5) {
                width: 30%;
            }
        }

        .no-talks {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .stats {
            display: flex;
            gap: 2rem;
            justify-content: center;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .stat-item {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <div class="talks-container">
        <div class="talks-header">
            <h1>Submitted Talks</h1>
            <p>Overview of all talk submissions for the conference</p>
            <p>
                <a href="index.php" style="color: white; text-decoration: underline;">← Back to Main Page</a> |
                <?= $profileCount ?> people have profiles |
                <a href="registrations.php" style="color: white; text-decoration: underline;">View Registrations</a>
            </p>
            <?php if (!empty($talks)): ?>
                <div class="stats">
                    <?php
                    $flashCount = 0;
                    $contributedCount = 0;
                    $bothCount = 0;
                    $flashAccepted = 0;
                    $contributedAccepted = 0;
                    $contributedPending = 0;

                    foreach ($talks as $talk) {
                        if ($talk['talk_flash'] && $talk['talk_contributed']) {
                            $bothCount++;
                        } elseif ($talk['talk_flash']) {
                            $flashCount++;
                        } elseif ($talk['talk_contributed']) {
                            $contributedCount++;
                        }

                        // Count accepted and pending talks
                        if ($talk['talk_flash']) {
                            $flashAccepted++; // Flash talks are always accepted
                        }
                        if ($talk['talk_contributed']) {
                            if ($talk['talk_contributed_accepted'] === 1) {
                                $contributedAccepted++;
                            } elseif ($talk['talk_contributed_accepted'] === null) {
                                $contributedPending++;
                            }
                        }
                    }
                    ?>
                    <div class="stat-item">
                        <strong><?= count($talks) ?></strong> Total
                    </div>
                    <div class="stat-item">
                        <strong><?= $flashCount ?></strong> Flash Only
                    </div>
                    <div class="stat-item">
                        <strong><?= $contributedCount ?></strong> Contrib Only
                    </div>
                    <div class="stat-item">
                        <strong><?= $bothCount ?></strong> Both
                    </div>
                    <div class="stat-item" id="flashAcceptedStat">
                        <strong><?= $flashAccepted ?></strong> Flash ✓
                    </div>
                    <div class="stat-item" id="contributedAcceptedStat">
                        <strong><?= $contributedAccepted ?></strong> Contrib ✓
                    </div>
                    <div class="stat-item" id="contributedPendingStat">
                        <strong><?= $contributedPending ?></strong> Contrib ⏳
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if (isset($error)): ?>
            <div class="error">Error: <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($talks)): ?>
            <div class="controls">
                <div class="sort-controls">
                    <label for="sortSelect">Sort by:</label>
                    <select id="sortSelect">
                        <option value="name">Name (Last, First)</option>
                        <option value="type">Talk Type</option>
                        <option value="title">Talk Title</option>
                    </select>

                    <select id="filterSelect">
                        <option value="all">All Submissions</option>
                        <option value="flash">Flash Talks</option>
                        <option value="contributed">Contributed Talks</option>
                        <option value="contrib-accepted">Contributed: Accepted</option>
                        <option value="contrib-pending">Contributed: Pending</option>
                        <option value="contrib-rejected">Contributed: Rejected</option>
                    </select>

                    <select id="dateFilter">
                        <option value="">All dates</option>
                    </select>

                    <div class="admin-controls">
                        <label>
                            <input type="checkbox" id="adminMode"> Admin Mode
                        </label>
                    </div>

                    <button id="downloadCsv" class="btn btn-secondary">Download CSV</button>
                </div>
            </div>

            <div class="talks-table">
                <table id="talksTable">
                    <thead>
                        <tr>
                            <th data-sort="name">Name</th>
                            <th data-sort="type">Talk Type</th>
                            <th data-sort="title">Title</th>
                            <th data-sort="dates">Dates</th>
                            <th>Abstract</th>
                            <th class="admin-column" style="display: none;">Admin Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($talks as $talk): ?>
                            <?php
                            $flashStatus = $talk['talk_flash'] ? 1 : 0; // Flash talks are always accepted if submitted
                            $contribStatus = $talk['talk_contributed_accepted'];
                            ?>
                            <tr data-talk-flash="<?= $talk['talk_flash'] ?>"
                                data-talk-contributed="<?= $talk['talk_contributed'] ?>"
                                data-flash-accepted="<?= $flashStatus ?>"
                                data-contributed-accepted="<?= $contribStatus ?? 'null' ?>"
                                data-email="<?= htmlspecialchars($talk['email']) ?>"
                                data-talk-title="<?= htmlspecialchars($talk['talk_title'] ?: '') ?>"
                                data-talk-abstract="<?= htmlspecialchars($talk['talk_abstract'] ?: '') ?>"
                                data-start-date="<?= htmlspecialchars($talk['start_date'] ?: '') ?>"
                                data-end-date="<?= htmlspecialchars($talk['end_date'] ?: '') ?>">
                                <td><a href="mailto:<?= htmlspecialchars($talk['email']) ?>"><?= htmlspecialchars($talk['last_name'] . ', ' . $talk['first_name']) ?></a></td>
                                <td>
                                    <?php if ($talk['talk_flash']): ?>
                                        <span class="talk-type flash <?= getStatusClass($flashStatus) ?>">
                                            Flash (2+1 min) <?= getStatusIcon($flashStatus) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($talk['talk_contributed']): ?>
                                        <span class="talk-type contributed <?= getStatusClass($contribStatus) ?>">
                                            Contributed (15+5 min) <?= getStatusIcon($contribStatus) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($talk['talk_title'] ?: '-') ?></td>
                                <td>
                                    <?php if ($talk['start_date'] && $talk['end_date']): ?>
                                        <?= htmlspecialchars($talk['start_date']) ?>-<?= htmlspecialchars($talk['end_date']) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="talk-abstract">
                                    <?php if (!empty($talk['talk_abstract'])): ?>
                                        <?php
                                        $abstract = htmlspecialchars($talk['talk_abstract']);
                                        $isLong = strlen($abstract) > 200; // Consider abstracts longer than 200 chars as long
                                        ?>
                                        <?php if ($isLong): ?>
                                            <div class="abstract-content truncated" data-full-text="<?= $abstract ?>">
                                                <?= substr($abstract, 0, 200) ?>...
                                            </div>
                                            <button class="abstract-toggle" onclick="toggleAbstract(this)">Show more</button>
                                        <?php else: ?>
                                            <div class="abstract-content">
                                                <?= $abstract ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="admin-column" style="display: none;">
                                    <?php if ($talk['talk_contributed']): ?>
                                        <div>
                                            <label>Contributed:</label>
                                            <select class="admin-select contributed-select" data-type="contributed"
                                                data-original-value="<?= $contribStatus === null ? 'null' : $contribStatus ?>">
                                                <option value="">Pending</option>
                                                <option value="1" <?= $contribStatus === 1 ? 'selected' : '' ?>>Accept</option>
                                                <option value="0" <?= $contribStatus === 0 ? 'selected' : '' ?>>Reject</option>
                                            </select>
                                            <div class="save-indicator" style="display: none; font-size: 0.8em; margin-top: 0.25rem;"></div>
                                        </div>
                                    <?php else: ?>
                                        <div style="color: #666; font-style: italic;">Flash talks are auto-accepted</div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-talks">
                <h2>No talks submitted yet</h2>
                <p>Talk submissions will appear here once participants start submitting them.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Function to toggle abstract display
        function toggleAbstract(button) {
            const abstractContent = button.previousElementSibling;
            const fullText = abstractContent.dataset.fullText;
            const isExpanded = abstractContent.classList.contains('expanded');

            if (isExpanded) {
                // Collapse
                abstractContent.textContent = fullText.substring(0, 200) + '...';
                abstractContent.classList.remove('expanded');
                abstractContent.classList.add('truncated');
                button.textContent = 'Show more';
            } else {
                // Expand
                abstractContent.textContent = fullText;
                abstractContent.classList.remove('truncated');
                abstractContent.classList.add('expanded');
                button.textContent = 'Show less';
            }
        }

        // Helper function to parse date strings like "Jul 20" to a comparable format
        function parseDate(dateStr) {
            if (!dateStr) return null;
            const months = {
                'Jan': 1,
                'Feb': 2,
                'Mar': 3,
                'Apr': 4,
                'May': 5,
                'Jun': 6,
                'Jul': 7,
                'Aug': 8,
                'Sep': 9,
                'Oct': 10,
                'Nov': 11,
                'Dec': 12
            };
            const parts = dateStr.trim().split(' ');
            if (parts.length === 2) {
                const month = months[parts[0]];
                const day = parseInt(parts[1]);
                if (month && day && day >= 1 && day <= 31) {
                    return month * 100 + day; // e.g., "Jul 20" becomes 720
                }
            }
            return null;
        }

        // Helper function to format a date consistently (always 2-digit day)
        function formatDate(month, day) {
            const monthNames = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            if (month >= 1 && month <= 12 && day >= 1 && day <= 31) {
                return `${monthNames[month]} ${day.toString().padStart(2, '0')}`;
            }
            return null;
        }

        // Helper function to normalize date string to consistent format
        function normalizeDate(dateStr) {
            if (!dateStr) return null;
            const parsed = parseDate(dateStr);
            if (!parsed) return null;
            const month = Math.floor(parsed / 100);
            const day = parsed % 100;
            return formatDate(month, day);
        }

        // Helper function to check if a specific date falls within a person's registration period
        function isDateInRange(startDate, endDate, filterDate) {
            if (!startDate || !endDate || !filterDate) return false;

            const start = parseDate(startDate);
            const end = parseDate(endDate);
            const filter = parseDate(filterDate);

            if (!start || !end || !filter) return false;

            return filter >= start && filter <= end;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const sortSelect = document.getElementById('sortSelect');
            const filterSelect = document.getElementById('filterSelect');
            const adminModeCheckbox = document.getElementById('adminMode');
            const dateFilter = document.getElementById('dateFilter');

            const table = document.getElementById('talksTable');
            const tbody = table ? table.querySelector('tbody') : null;

            if (!tbody) return;

            let originalRows = Array.from(tbody.querySelectorAll('tr'));
            let pageLoadTime = new Date().toISOString().slice(0, 19).replace('T', ' '); // MySQL datetime format

            // Populate date filter dropdown
            function populateDateFilter() {
                const allDates = new Set();

                originalRows.forEach(row => {
                    const startDate = row.dataset.startDate;
                    const endDate = row.dataset.endDate;

                    if (startDate && endDate) {
                        // Normalize the start and end dates first
                        const normalizedStart = normalizeDate(startDate);
                        const normalizedEnd = normalizeDate(endDate);

                        if (normalizedStart && normalizedEnd) {
                            const start = parseDate(normalizedStart);
                            const end = parseDate(normalizedEnd);

                            if (start && end) {
                                // Generate all dates between start and end (inclusive)
                                const startMonth = Math.floor(start / 100);
                                const startDay = start % 100;
                                const endMonth = Math.floor(end / 100);
                                const endDay = end % 100;

                                // Generate date range with proper month handling
                                let currentMonth = startMonth;
                                let currentDay = startDay;

                                const daysInMonth = [0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31]; // Simplified, not handling leap years

                                while (currentMonth < endMonth || (currentMonth === endMonth && currentDay <= endDay)) {
                                    if (currentMonth >= 1 && currentMonth <= 12 && currentDay >= 1 && currentDay <= daysInMonth[currentMonth]) {
                                        const dateStr = formatDate(currentMonth, currentDay);
                                        if (dateStr) {
                                            allDates.add(dateStr);
                                        }
                                    }

                                    currentDay++;
                                    // Handle month transitions properly
                                    if (currentDay > daysInMonth[currentMonth]) {
                                        currentDay = 1;
                                        currentMonth++;
                                    }

                                    // Safety break to avoid infinite loops
                                    if (currentMonth > 12) break;
                                }
                            }
                        }
                    }
                });

                // Sort dates and populate dropdown
                const sortedDates = Array.from(allDates).sort((a, b) => {
                    const aVal = parseDate(a) || 0;
                    const bVal = parseDate(b) || 0;
                    return aVal - bVal;
                });

                // Clear existing options except "All dates"
                while (dateFilter.children.length > 1) {
                    dateFilter.removeChild(dateFilter.lastChild);
                }

                // Add date options
                sortedDates.forEach(date => {
                    const option = document.createElement('option');
                    option.value = date;
                    option.textContent = date;
                    dateFilter.appendChild(option);
                });
            }

            function sortTable() {
                const sortBy = sortSelect.value;
                const rows = Array.from(tbody.querySelectorAll('tr'));

                rows.sort((a, b) => {
                    let aVal, bVal;

                    switch (sortBy) {
                        case 'name':
                            aVal = a.cells[0].textContent.trim();
                            bVal = b.cells[0].textContent.trim();
                            break;
                        case 'type':
                            // Sort by talk type priority: contributed > flash > both
                            const aFlash = a.dataset.talkFlash === '1';
                            const aContrib = a.dataset.talkContributed === '1';
                            const bFlash = b.dataset.talkFlash === '1';
                            const bContrib = b.dataset.talkContributed === '1';

                            const getTypePriority = (flash, contrib) => {
                                if (flash && contrib) return 3; // Both
                                if (contrib) return 2; // Contributed only
                                if (flash) return 1; // Flash only
                                return 0;
                            };

                            aVal = getTypePriority(aFlash, aContrib);
                            bVal = getTypePriority(bFlash, bContrib);
                            return bVal - aVal; // Descending order
                        case 'title':
                            aVal = a.cells[2].textContent.trim();
                            bVal = b.cells[2].textContent.trim();
                            break;
                        case 'dates':
                            // Sort by start date
                            const aStartDate = a.dataset.startDate;
                            const bStartDate = b.dataset.startDate;
                            aVal = parseDate(aStartDate) || 0;
                            bVal = parseDate(bStartDate) || 0;
                            return aVal - bVal; // Ascending order for dates
                        default:
                            return 0;
                    }

                    if (sortBy !== 'type') {
                        return aVal.localeCompare(bVal);
                    }
                });

                // Clear and re-append sorted rows
                tbody.innerHTML = '';
                rows.forEach(row => tbody.appendChild(row));
            }

            function filterTable() {
                const filterBy = filterSelect.value;
                const selectedDate = dateFilter.value.trim();
                const rows = tbody.querySelectorAll('tr');

                rows.forEach(row => {
                    const flash = row.dataset.talkFlash === '1';
                    const contrib = row.dataset.talkContributed === '1';
                    const contribAccepted = row.dataset.contributedAccepted === '1';
                    const contribPending = row.dataset.contributedAccepted === 'null';
                    const contribRejected = row.dataset.contributedAccepted === '0';
                    const startDate = row.dataset.startDate;
                    const endDate = row.dataset.endDate;

                    let show = true;

                    // Apply type filter
                    switch (filterBy) {
                        case 'flash':
                            show = flash; // Anyone with flash talk (includes both flash+contributed)
                            break;
                        case 'contributed':
                            show = contrib; // Anyone with contributed talk (includes both flash+contributed)
                            break;
                        case 'contrib-accepted':
                            show = contrib && contribAccepted; // Contributed talks that are accepted
                            break;
                        case 'contrib-pending':
                            show = contrib && contribPending; // Contributed talks that are pending
                            break;
                        case 'contrib-rejected':
                            show = contrib && contribRejected; // Contributed talks that are rejected
                            break;
                        case 'all':
                        default:
                            show = flash || contrib; // Any talk submission
                            break;
                    }

                    // Apply date filter if type filter passed and a date is selected
                    if (show && selectedDate) {
                        show = isDateInRange(startDate, endDate, selectedDate);
                    }

                    row.style.display = show ? '' : 'none';
                });
            }

            function toggleAdminMode() {
                const isAdmin = adminModeCheckbox.checked;
                const adminColumns = document.querySelectorAll('.admin-column');

                adminColumns.forEach(col => {
                    col.style.display = isAdmin ? '' : 'none';
                });

                // Save admin mode state to localStorage
                localStorage.setItem('adminMode', isAdmin ? '1' : '0');
            }

            async function handleAdminChange(event) {
                const select = event.target;
                const row = select.closest('tr');
                const email = row.dataset.email;
                const indicator = select.parentElement.querySelector('.save-indicator');

                // Show saving state
                select.classList.remove('changed', 'saved', 'error');
                select.classList.add('saving');
                indicator.style.display = 'block';
                indicator.textContent = 'Saving...';
                indicator.className = 'save-indicator saving';

                // Get current and expected values
                const originalValue = select.dataset.originalValue === 'null' ? '' : select.dataset.originalValue;
                const newValue = select.value;

                try {
                    const formData = new FormData();
                    formData.append('email', email);
                    formData.append('talk_contributed_accepted', newValue);
                    formData.append('expected_contributed_accepted', originalValue);
                    formData.append('page_load_time', pageLoadTime);

                    const response = await fetch('api/update_talk_status.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        // Success - change was saved
                        select.classList.remove('saving');
                        select.classList.add('saved');

                        // Update original value for next change
                        select.dataset.originalValue = newValue;

                        // Update the visual indicators in the talk type column
                        updateTalkTypeDisplay(row);

                        // Update statistics
                        updateStatistics();

                        if (result.reload_needed) {
                            // Other changes detected - reload to show current data
                            indicator.textContent = 'Saved ✓ - Refreshing page...';
                            indicator.className = 'save-indicator saved';
                            setTimeout(() => window.location.reload(), 1500);
                        } else {
                            // Normal save - update timestamp and show success
                            pageLoadTime = new Date().toISOString().slice(0, 19).replace('T', ' ');
                            indicator.textContent = 'Saved ✓';
                            indicator.className = 'save-indicator saved';
                            setTimeout(() => {
                                indicator.style.display = 'none';
                                select.classList.remove('saved');
                            }, 2000);
                        }

                    } else {
                        throw new Error(result.message);
                    }
                } catch (error) {
                    // Error
                    select.classList.remove('saving');
                    select.classList.add('error');

                    if (error.message.includes('CONFLICT')) {
                        indicator.textContent = 'Conflict! Refresh page';
                        indicator.className = 'save-indicator error';
                        // Show refresh prompt for specific conflicts
                        setTimeout(() => {
                            if (confirm('This talk status was changed by another admin. Refresh the page to see current values?')) {
                                window.location.reload();
                            }
                        }, 1000);
                    } else {
                        indicator.textContent = 'Error saving';
                        indicator.className = 'save-indicator error';
                    }
                }
            }

            function updateTalkTypeDisplay(row) {
                const contribSelect = row.querySelector('.contributed-select');
                const talkTypes = row.querySelectorAll('.talk-type');

                talkTypes.forEach(type => {
                    if (type.classList.contains('contributed') && contribSelect) {
                        const value = contribSelect.value;
                        let statusClass, icon;

                        if (value === '1') {
                            statusClass = 'accepted';
                            icon = '✓';
                        } else if (value === '0') {
                            statusClass = 'rejected';
                            icon = '✗';
                        } else {
                            statusClass = 'pending';
                            icon = '⏳';
                        }

                        type.className = `talk-type contributed ${statusClass}`;
                        type.textContent = `Contributed (15+5 min) ${icon}`;
                        row.dataset.contributedAccepted = value || 'null';
                    }
                    // Flash talks are always accepted - no need to update
                });
            }

            function downloadCsv() {
                const rows = Array.from(tbody.querySelectorAll('tr')).filter(row => row.style.display !== 'none');

                // CSV headers with UTF-8 BOM for Excel compatibility
                const headers = ['Last Name', 'First Name', 'Email', 'Flash Talk', 'Contributed Talk', 'Title', 'Dates', 'Abstract', 'Contributed Status'];
                let csvContent = '\uFEFF' + headers.join(',') + '\n'; // Add BOM for UTF-8

                // CSV data
                rows.forEach(row => {
                    const cells = row.querySelectorAll('td');
                    const name = cells[0].textContent.trim();
                    const [lastName, firstName] = name.split(', ');
                    const email = row.dataset.email; // Get email from data attribute
                    const flash = row.dataset.talkFlash === '1' ? 'Yes' : 'No';
                    const contributed = row.dataset.talkContributed === '1' ? 'Yes' : 'No';
                    const title = row.dataset.talkTitle || ''; // Get full title from data attribute
                    const startDate = row.dataset.startDate || '';
                    const endDate = row.dataset.endDate || '';
                    const registrationDates = (startDate && endDate) ? `${startDate}-${endDate}` : '';
                    const abstract = row.dataset.talkAbstract || ''; // Get full abstract from data attribute

                    // Determine contributed status (flash talks are always accepted)
                    let acceptedStatus = '';
                    if (row.dataset.talkContributed === '1') {
                        const contribAccepted = row.dataset.contributedAccepted === '1';
                        const contribPending = row.dataset.contributedAccepted === 'null';

                        if (contribAccepted) {
                            acceptedStatus = 'Accepted';
                        } else if (contribPending) {
                            acceptedStatus = 'Pending';
                        } else {
                            acceptedStatus = 'Rejected';
                        }
                    } else {
                        acceptedStatus = 'N/A'; // Flash only talks
                    }

                    // Escape CSV values (wrap in quotes if they contain commas, quotes, or newlines)
                    const escapeCSV = (value) => {
                        if (value.includes(',') || value.includes('"') || value.includes('\n')) {
                            return '"' + value.replace(/"/g, '""') + '"';
                        }
                        return value;
                    };

                    const rowData = [
                        escapeCSV(lastName || ''),
                        escapeCSV(firstName || ''),
                        escapeCSV(email),
                        flash,
                        contributed,
                        escapeCSV(title),
                        escapeCSV(registrationDates),
                        escapeCSV(abstract),
                        escapeCSV(acceptedStatus)
                    ];

                    csvContent += rowData.join(',') + '\n';
                });

                // Create and download file
                const blob = new Blob([csvContent], {
                    type: 'text/csv;charset=utf-8;'
                });
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', 'talks_submissions.csv');
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }



            function updateStatistics() {
                const rows = tbody.querySelectorAll('tr');
                let flashAccepted = 0;
                let contributedAccepted = 0;
                let contributedPending = 0;

                rows.forEach(row => {
                    const flash = row.dataset.talkFlash === '1';
                    const contrib = row.dataset.talkContributed === '1';
                    const contribStatus = row.dataset.contributedAccepted;

                    if (flash) {
                        flashAccepted++; // Flash talks are always accepted
                    }

                    if (contrib) {
                        if (contribStatus === '1') {
                            contributedAccepted++;
                        } else if (contribStatus === 'null') {
                            contributedPending++;
                        }
                    }
                });

                // Update the statistics display
                document.getElementById('flashAcceptedStat').innerHTML = `<strong>${flashAccepted}</strong> Flash ✓`;
                document.getElementById('contributedAcceptedStat').innerHTML = `<strong>${contributedAccepted}</strong> Contrib ✓`;
                document.getElementById('contributedPendingStat').innerHTML = `<strong>${contributedPending}</strong> Contrib ⏳`;
            }

            // Event listeners
            sortSelect.addEventListener('change', sortTable);
            filterSelect.addEventListener('change', filterTable);
            adminModeCheckbox.addEventListener('change', toggleAdminMode);

            // Date filter event listener
            dateFilter.addEventListener('change', filterTable);

            // Admin select change handlers - auto-save on change
            tbody.addEventListener('change', function(event) {
                if (event.target.classList.contains('admin-select')) {
                    handleAdminChange(event);
                }
            });

            const downloadBtn = document.getElementById('downloadCsv');
            if (downloadBtn) {
                downloadBtn.addEventListener('click', downloadCsv);
            }

            // Restore admin mode state from localStorage
            const savedAdminMode = localStorage.getItem('adminMode');
            if (savedAdminMode === '1') {
                adminModeCheckbox.checked = true;
            }

            // Initial setup
            populateDateFilter();
            sortTable();
            toggleAdminMode();
        });
    </script>
</body>

</html>