<?php
require_once 'database/Database.php';

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

    // Get all participants who have submitted talks
    $sql = "SELECT first_name, last_name, email, talk_flash, talk_contributed, talk_title, talk_abstract,
                   talk_flash_accepted, talk_contributed_accepted
            FROM participants
            WHERE talk_flash = 1 OR talk_contributed = 1
            ORDER BY last_name, first_name";
    $stmt = $pdo->query($sql);
    $talks = $stmt->fetchAll();
} catch (Exception $e) {
    $error = $e->getMessage();
    $talks = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submitted Talks - Benasque 25</title>
    <link rel="stylesheet" href="css/style.css">
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

        .talks-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .talks-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .talks-table th {
            background: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #e9ecef;
            cursor: pointer;
            user-select: none;
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
            max-width: 300px;
            word-wrap: break-word;
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
            <p><a href="index.php" style="color: white; text-decoration: underline;">← Back to Main Page</a></p>
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
                        <strong><?= count($talks) ?></strong> Total Submissions
                    </div>
                    <div class="stat-item">
                        <strong><?= $flashCount ?></strong> Flash Only
                    </div>
                    <div class="stat-item">
                        <strong><?= $contributedCount ?></strong> Contributed Only
                    </div>
                    <div class="stat-item">
                        <strong><?= $bothCount ?></strong> Both Types
                    </div>
                    <div class="stat-item" id="flashAcceptedStat">
                        <strong><?= $flashAccepted ?></strong> Flash Accepted
                    </div>
                    <div class="stat-item" id="contributedAcceptedStat">
                        <strong><?= $contributedAccepted ?></strong> Contributed Accepted
                    </div>
                    <div class="stat-item" id="contributedPendingStat">
                        <strong><?= $contributedPending ?></strong> Contributed Pending
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

                    <label for="filterSelect">Filter by type:</label>
                    <select id="filterSelect">
                        <option value="all">All Submissions</option>
                        <option value="flash">Flash Talks</option>
                        <option value="contributed">Contributed Talks</option>
                        <option value="contrib-accepted">Contributed: Accepted</option>
                        <option value="contrib-pending">Contributed: Pending</option>
                        <option value="contrib-rejected">Contributed: Rejected</option>
                    </select>

                    <div class="admin-controls">
                        <label>
                            <input type="checkbox" id="adminMode"> Admin Mode
                        </label>
                        <button id="saveAllChanges" class="btn btn-primary" style="display: none;">Save All Changes</button>
                    </div>

                    <button id="downloadCsv" class="btn btn-secondary">Download CSV</button>
                </div>
            </div>

            <div class="talks-table">
                <table id="talksTable">
                    <thead>
                        <tr>
                            <th data-sort="name">Name</th>
                            <th data-sort="email">Email</th>
                            <th data-sort="type">Talk Type</th>
                            <th data-sort="title">Title</th>
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
                                data-email="<?= htmlspecialchars($talk['email']) ?>">
                                <td><?= htmlspecialchars($talk['last_name'] . ', ' . $talk['first_name']) ?></td>
                                <td><a href="mailto:<?= htmlspecialchars($talk['email']) ?>"><?= htmlspecialchars($talk['email']) ?></a></td>
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
                                <td class="talk-abstract"><?= htmlspecialchars($talk['talk_abstract'] ?: '-') ?></td>
                                <td class="admin-column" style="display: none;">
                                    <?php if ($talk['talk_contributed']): ?>
                                        <div>
                                            <label>Contributed:</label>
                                            <select class="admin-select contributed-select" data-type="contributed">
                                                <option value="">Pending</option>
                                                <option value="1" <?= $contribStatus === 1 ? 'selected' : '' ?>>Accept</option>
                                                <option value="0" <?= $contribStatus === 0 ? 'selected' : '' ?>>Reject</option>
                                            </select>
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
        document.addEventListener('DOMContentLoaded', function() {
            const sortSelect = document.getElementById('sortSelect');
            const filterSelect = document.getElementById('filterSelect');
            const adminModeCheckbox = document.getElementById('adminMode');
            const saveAllBtn = document.getElementById('saveAllChanges');
            const table = document.getElementById('talksTable');
            const tbody = table ? table.querySelector('tbody') : null;

            if (!tbody) return;

            let originalRows = Array.from(tbody.querySelectorAll('tr'));
            let changedRows = new Set();

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
                        case 'email':
                            aVal = a.cells[1].textContent.trim();
                            bVal = b.cells[1].textContent.trim();
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
                            aVal = a.cells[3].textContent.trim();
                            bVal = b.cells[3].textContent.trim();
                            break;
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
                const rows = tbody.querySelectorAll('tr');

                rows.forEach(row => {
                    const flash = row.dataset.talkFlash === '1';
                    const contrib = row.dataset.talkContributed === '1';
                    const contribAccepted = row.dataset.contributedAccepted === '1';
                    const contribPending = row.dataset.contributedAccepted === 'null';
                    const contribRejected = row.dataset.contributedAccepted === '0';
                    let show = true;

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

                    row.style.display = show ? '' : 'none';
                });
            }

            function toggleAdminMode() {
                const isAdmin = adminModeCheckbox.checked;
                const adminColumns = document.querySelectorAll('.admin-column');

                adminColumns.forEach(col => {
                    col.style.display = isAdmin ? '' : 'none';
                });

                saveAllBtn.style.display = isAdmin ? 'inline-block' : 'none';
            }

            function handleAdminChange(event) {
                const select = event.target;
                const row = select.closest('tr');
                const email = row.dataset.email;

                // Mark as changed
                select.classList.add('changed');
                changedRows.add(email);

                // Update the visual indicators in the talk type column
                updateTalkTypeDisplay(row);
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

                // CSV headers
                const headers = ['Last Name', 'First Name', 'Email', 'Flash Talk', 'Contributed Talk', 'Title', 'Abstract'];
                let csvContent = headers.join(',') + '\n';

                // CSV data
                rows.forEach(row => {
                    const cells = row.querySelectorAll('td');
                    const name = cells[0].textContent.trim();
                    const [lastName, firstName] = name.split(', ');
                    const email = cells[1].textContent.trim();
                    const flash = row.dataset.talkFlash === '1' ? 'Yes' : 'No';
                    const contributed = row.dataset.talkContributed === '1' ? 'Yes' : 'No';
                    const title = cells[3].textContent.trim();
                    const abstract = cells[4].textContent.trim();

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
                        escapeCSV(abstract)
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

            async function saveAllChanges() {
                if (changedRows.size === 0) {
                    alert('No changes to save');
                    return;
                }

                saveAllBtn.disabled = true;
                saveAllBtn.textContent = 'Saving...';

                const promises = [];

                changedRows.forEach(email => {
                    const row = document.querySelector(`tr[data-email="${email}"]`);
                    const contribSelect = row.querySelector('.contributed-select');

                    const formData = new FormData();
                    formData.append('email', email);
                    formData.append('talk_flash_accepted', '1'); // Flash talks are always accepted
                    formData.append('talk_contributed_accepted', contribSelect ? contribSelect.value : '');

                    promises.push(
                        fetch('api/update_talk_status.php', {
                            method: 'POST',
                            body: formData
                        }).then(response => response.json())
                    );
                });

                try {
                    const results = await Promise.all(promises);
                    const failed = results.filter(r => !r.success);

                    if (failed.length === 0) {
                        alert('All changes saved successfully!');
                        // Clear changed status
                        document.querySelectorAll('.admin-select.changed').forEach(select => {
                            select.classList.remove('changed');
                        });
                        changedRows.clear();
                        // Update statistics
                        updateStatistics();
                    } else {
                        alert(`Some changes failed to save: ${failed.map(f => f.message).join(', ')}`);
                    }
                } catch (error) {
                    alert('Error saving changes: ' + error.message);
                }

                saveAllBtn.disabled = false;
                saveAllBtn.textContent = 'Save All Changes';
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
                document.getElementById('flashAcceptedStat').innerHTML = `<strong>${flashAccepted}</strong> Flash Accepted`;
                document.getElementById('contributedAcceptedStat').innerHTML = `<strong>${contributedAccepted}</strong> Contributed Accepted`;
                document.getElementById('contributedPendingStat').innerHTML = `<strong>${contributedPending}</strong> Contributed Pending`;
            }

            // Event listeners
            sortSelect.addEventListener('change', sortTable);
            filterSelect.addEventListener('change', filterTable);
            adminModeCheckbox.addEventListener('change', toggleAdminMode);
            saveAllBtn.addEventListener('click', saveAllChanges);

            // Admin select change handlers
            tbody.addEventListener('change', function(event) {
                if (event.target.classList.contains('admin-select')) {
                    handleAdminChange(event);
                }
            });

            const downloadBtn = document.getElementById('downloadCsv');
            if (downloadBtn) {
                downloadBtn.addEventListener('click', downloadCsv);
            }

            // Initial setup
            sortTable();
            toggleAdminMode();
        });
    </script>
</body>

</html>