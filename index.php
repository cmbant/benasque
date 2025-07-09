<?php
require_once 'database/Database.php';

// Load configuration
$config = require 'config.php';

try {
    $db = new Database();
    $participants = $db->getAllParticipants();
    $allInterests = $db->getAllInterests();
} catch (Exception $e) {
    $participants = [];
    $allInterests = [];
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($config['page_title']) ?></title>
    <link rel="stylesheet" href="css/style.css?<?= htmlspecialchars($config['cache_version']) ?>">
</head>

<body>
    <header>
        <div class="container">
            <h1><?= htmlspecialchars($config['conference_name']) ?></h1>
            <nav class="tab-navigation">
                <button id="addEditBtn" class="btn btn-secondary">Add/Edit Entry</button>
                <div class="tab-buttons">
                    <button id="participantsTab" class="tab-btn active">Participants</button>
                    <button id="blackboardTab" class="tab-btn"><?= htmlspecialchars($config['virtual_blackboard_title']) ?></button>
                </div>
            </nav>
        </div>
    </header>

    <main class="container">
        <!-- Participants Tab Content -->
        <div id="participantsContent" class="tab-content active">
            <div class="controls">
                <div class="sort-controls">
                    <label>Sort by:</label>
                    <select id="sortSelect">
                        <option value="random" selected>Random</option>
                        <option value="first_name">First Name</option>
                        <option value="last_name">Last Name</option>
                    </select>
                </div>
                <div class="filter-controls">
                    <select id="dateFilter">
                        <option value="">All dates</option>
                    </select>
                    <select id="interestFilter">
                        <option value="">All interests</option>
                        <?php foreach ($allInterests as $interest): ?>
                            <option value="<?= htmlspecialchars($interest) ?>"><?= htmlspecialchars($interest) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" id="filterInput" placeholder="Or type keywords...">
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="error">Error: <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div id="participantsList" class="participants-grid">
                <?php foreach ($participants as $participant): ?>
                    <div class="participant-card"
                        data-first-name="<?= htmlspecialchars($participant['first_name']) ?>"
                        data-last-name="<?= htmlspecialchars($participant['last_name']) ?>"
                        data-interests="<?= htmlspecialchars($participant['interests'] ?? '') ?>"
                        data-start-date="<?= htmlspecialchars($participant['start_date'] ?? '') ?>"
                        data-end-date="<?= htmlspecialchars($participant['end_date'] ?? '') ?>">

                        <div class="participant-photo">
                            <?php if ($participant['photo_path']): ?>
                                <img src="<?= htmlspecialchars($participant['photo_path']) ?>"
                                    alt="<?= htmlspecialchars($participant['first_name'] . ' ' . $participant['last_name']) ?>">
                            <?php else: ?>
                                <div class="no-photo">No Photo</div>
                            <?php endif; ?>
                        </div>

                        <div class="participant-info">
                            <?php if ($participant['email_public'] == 1): ?>
                                <h3><a href="mailto:<?= htmlspecialchars($participant['email']) ?>" class="name-link"><?= htmlspecialchars($participant['first_name'] . ' ' . $participant['last_name']) ?></a></h3>
                            <?php else: ?>
                                <h3><?= htmlspecialchars($participant['first_name'] . ' ' . $participant['last_name']) ?></h3>
                            <?php endif; ?>

                            <?php if ($participant['registration_affiliation']): ?>
                                <div class="affiliation">
                                    <?= htmlspecialchars($participant['registration_affiliation']) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($participant['email_public'] == 1): ?>
                                <div class="registration-dates-only">
                                    <?php if ($participant['registration_status'] === 'CANCELLED'): ?>
                                        <span class="registration-status cancelled">Cancelled</span>
                                    <?php elseif ($participant['start_date'] && $participant['end_date']): ?>
                                        <span class="registration-dates"><?= htmlspecialchars($participant['start_date']) ?>-<?= htmlspecialchars($participant['end_date']) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($participant['interests']): ?>
                                <div class="interests">
                                    <strong>Interests:</strong>
                                    <div class="interest-tags">
                                        <?php
                                        $interests = explode(',', $participant['interests']);
                                        foreach ($interests as $interest):
                                            $interest = trim($interest);
                                            if ($interest): ?>
                                                <span class="interest-tag"><?= htmlspecialchars($interest) ?></span>
                                        <?php endif;
                                        endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($participant['description']): ?>
                                <div class="description">
                                    <strong>General info:</strong>
                                    <p><?= nl2br(htmlspecialchars($participant['description'])) ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if ($participant['arxiv_links']): ?>
                                <?php
                                $links = json_decode($participant['arxiv_links'], true);
                                if ($links && is_array($links)):
                                    // Check if there are any valid links to display
                                    $validLinks = [];
                                    foreach ($links as $link) {
                                        if (is_string($link)) {
                                            // Old format: simple URL string
                                            $url = $link;
                                            $title = $link; // Fallback to URL
                                            $validLinks[] = ['url' => $url, 'title' => $title];
                                        } else if (is_array($link) && isset($link['url'])) {
                                            // New format: object with url and title
                                            $url = $link['url'];
                                            $title = !empty($link['title']) ? $link['title'] : $link['url'];
                                            $validLinks[] = ['url' => $url, 'title' => $title];
                                        }
                                    }

                                    if (!empty($validLinks)): ?>
                                        <div class="arxiv-links">
                                            <strong>Recent Papers:</strong>
                                            <ul>
                                                <?php foreach ($validLinks as $link): ?>
                                                    <?php
                                                    // Replace -- and --- with proper Unicode em-dashes for better typography
                                                    $title = $link['title'];
                                                    $title = str_replace('---', '—', $title); // Replace triple dash with em-dash
                                                    $title = str_replace('--', '—', $title);  // Replace double dash with em-dash
                                                    ?>
                                                    <li><a href="<?= htmlspecialchars($link['url']) ?>" target="_blank" class="arxiv-title"><?= $title ?></a></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Virtual Blackboard Tab Content -->
        <div id="blackboardContent" class="tab-content">
            <div class="iframe-container">
                <iframe
                    id="blackboardIframe"
                    data-src="<?= htmlspecialchars($config['virtual_blackboard_url']) ?>"
                    frameborder="0"
                    allowfullscreen>
                </iframe>
            </div>
        </div>
    </main>

    <!-- Add/Edit Modal -->
    <div id="addEditModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modalTitle">Add Your Information</h2>
            <form id="participantForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="firstName">First Name *</label>
                    <input type="text" id="firstName" name="first_name" required>
                </div>

                <div class="form-group">
                    <label for="lastName">Last Name *</label>
                    <input type="text" id="lastName" name="last_name" required>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                    <div class="checkbox-group">
                        <input type="checkbox" id="emailPublic" name="email_public" value="1" checked>
                        <label for="emailPublic">Show on profile</label>
                    </div>
                    <small>Used to identify your entry for future edits. <a href="#" id="recoverProfileLink" class="recover-link">Have an existing profile?</a></small>
                </div>

                <div class="form-group">
                    <label for="interests">Research Interests</label>
                    <div class="multi-select-container">
                        <div class="selected-tags" id="selectedTags"></div>
                        <div class="combobox-wrapper">
                            <input type="text" id="interestSearch" placeholder="Type to search and add keywords..." autocomplete="off">
                            <div class="dropdown-list" id="interestDropdown">
                                <?php foreach ($allInterests as $interest): ?>
                                    <div class="dropdown-item" data-value="<?= htmlspecialchars($interest) ?>">
                                        <?= htmlspecialchars($interest) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="interests" name="interests">
                    <small>Click on suggestions or type new keywords. Selected keywords appear as tags above.</small>
                </div>

                <div class="form-group">
                    <label for="description">General info</label>
                    <textarea id="description" name="description" rows="3" maxlength="1000" placeholder="Tell us about your research, background, or anything else you'd like to share..."></textarea>
                </div>

                <div class="form-group">
                    <label for="arxivLinks">ArXiv Links (up to 3)</label>
                    <textarea id="arxivLinks" name="arxiv_links" rows="3" placeholder="One arXiv URL per line"></textarea>
                </div>

                <div class="form-group">
                    <label for="photo">Photo (optional, but encouraged)</label>
                    <div id="photoDropZone" class="photo-drop-zone">
                        <div class="drop-zone-content">
                            <i class="upload-icon">📷</i>
                            <p>Drag & drop your photo here or <span class="browse-link">browse</span></p>
                            <small>Will be automatically resized if too large</small>
                        </div>
                        <input type="file" id="photo" name="photo" accept="image/*" style="display: none;">
                    </div>
                    <div id="photoPreview" class="photo-preview" style="display: none;">
                        <img id="previewImage" src="" alt="Preview">
                        <button type="button" id="removePhoto" class="remove-photo">Remove</button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Talks</label>
                    <div class="talks-section">
                        <p class="info-text">There are flash talks (2+1 min) and a limited number of longer contributed talks (15+5 min). Please indicate the type(s) of talk you'd like to be considered for. If you select both, we'll consider your submission for a longer talk first, and flash talk as second choice.</p>

                        <div class="checkbox-group">
                            <input type="checkbox" id="talkFlash" name="talk_flash" value="1">
                            <label for="talkFlash">Flash talk (2+1 min)</label>
                        </div>

                        <div class="checkbox-group">
                            <input type="checkbox" id="talkContributed" name="talk_contributed" value="1">
                            <label for="talkContributed">Contributed talk (15+5 min)</label>
                        </div>

                        <div id="contributedTalkDetails" class="contributed-talk-details" style="display: none;">
                            <div class="form-group">
                                <label for="talkTitle">Talk Title</label>
                                <input type="text" id="talkTitle" name="talk_title" placeholder="Enter your talk title">
                            </div>

                            <div class="form-group">
                                <label for="talkAbstract">Short Abstract</label>
                                <textarea id="talkAbstract" name="talk_abstract" rows="4" placeholder="Enter a short abstract for your talk"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" id="cancelBtn" class="btn btn-secondary">Cancel</button>
                    <button type="button" id="deleteBtn" class="btn btn-danger" style="display: none;">Delete Profile</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MathJax for LaTeX rendering in arXiv titles -->
    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <script>
        window.MathJax = {
            tex: {
                inlineMath: [
                    ['$', '$'],
                    ['\\(', '\\)']
                ],
                displayMath: [
                    ['$$', '$$'],
                    ['\\[', '\\]']
                ]
            },
            options: {
                skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre']
            }
        };
    </script>

    <script>
        // Pass configuration to JavaScript
        window.conferenceConfig = {
            startDate: <?= json_encode($config['conference_start_date']) ?>,
            endDate: <?= json_encode($config['conference_end_date']) ?>,
            localStoragePrefix: <?= json_encode($config['local_storage_prefix']) ?>
        };
    </script>
    <script src="js/app.js?<?= htmlspecialchars($config['cache_version']) ?>"></script>
</body>

</html>