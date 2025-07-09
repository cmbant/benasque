// Conference Participants JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // DOM elements
    const addEditBtn = document.getElementById('addEditBtn');
    const modal = document.getElementById('addEditModal');
    const closeBtn = document.querySelector('.close');
    const cancelBtn = document.getElementById('cancelBtn');
    const deleteBtn = document.getElementById('deleteBtn');
    const participantForm = document.getElementById('participantForm');
    const recoverProfileLink = document.getElementById('recoverProfileLink');
    const sortSelect = document.getElementById('sortSelect');
    const filterInput = document.getElementById('filterInput');
    const interestFilter = document.getElementById('interestFilter');
    const dateFilter = document.getElementById('dateFilter');
    const participantsList = document.getElementById('participantsList');
    const modalTitle = document.getElementById('modalTitle');

    // Tab elements
    const participantsTab = document.getElementById('participantsTab');
    const blackboardTab = document.getElementById('blackboardTab');
    const participantsContent = document.getElementById('participantsContent');
    const blackboardContent = document.getElementById('blackboardContent');

    // Photo upload elements
    const photoDropZone = document.getElementById('photoDropZone');
    const photoInput = document.getElementById('photo');
    const photoPreview = document.getElementById('photoPreview');
    const previewImage = document.getElementById('previewImage');
    const removePhotoBtn = document.getElementById('removePhoto');

    let isEditMode = false;
    let currentEmail = null;

    // Tab functionality
    participantsTab.addEventListener('click', () => switchTab('participants'));
    blackboardTab.addEventListener('click', () => switchTab('blackboard'));

    // Modal functionality
    addEditBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    deleteBtn.addEventListener('click', handleDeleteProfile);
    recoverProfileLink.addEventListener('click', handleRecoverProfile);

    // Note: Removed click-outside-to-close behavior to prevent accidental closing
    // Modal now only closes via explicit button clicks (X, Cancel, or Save) or Escape key

    // Add Escape key support for closing modal
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && modal.style.display === 'block') {
            closeModal();
        }
    });

    // Sorting functionality
    sortSelect.addEventListener('change', sortParticipants);

    // Filtering functionality
    filterInput.addEventListener('input', filterParticipants);
    interestFilter.addEventListener('change', filterParticipants);
    dateFilter.addEventListener('change', filterParticipants);

    // Form submission
    participantForm.addEventListener('submit', handleFormSubmit);

    // Photo upload functionality
    setupPhotoUpload();

    // Multi-select interests functionality
    setupInterestsCombobox();

    // Talks section functionality
    setupTalksSection();

    function openModal() {
        // Check if user has an existing entry
        const userEmail = getUserEmail();

        if (userEmail) {
            console.log('Found stored email for editing:', userEmail);
            // Try to load existing data for editing
            loadExistingData(userEmail);
        } else {
            console.log('No stored email found, opening in add mode');
            // New entry mode - load empty by default
            isEditMode = false;
            modalTitle.textContent = 'Add Your Information';
            participantForm.reset();
            deleteBtn.style.display = 'none';
        }

        modal.style.display = 'block';
    }

    function getUserEmail() {
        try {
            const prefix = window.conferenceConfig?.localStoragePrefix;
            return localStorage.getItem(prefix + '_email');
        } catch (e) {
            console.warn('localStorage not available:', e);
            return null;
        }
    }

    function storeUserEmail(email) {
        try {
            const prefix = window.conferenceConfig?.localStoragePrefix;
            localStorage.setItem(prefix + '_email', email);
            console.log('Stored email for future editing:', email);
            return true;
        } catch (e) {
            console.warn('Failed to store email in localStorage:', e);
            return false;
        }
    }

    function clearUserEmail() {
        try {
            const prefix = window.conferenceConfig?.localStoragePrefix;
            localStorage.removeItem(prefix + '_email');
            console.log('Cleared stored email');
            return true;
        } catch (e) {
            console.warn('Failed to clear email from localStorage:', e);
            return false;
        }
    }

    function closeModal() {
        modal.style.display = 'none';
        participantForm.reset();
    }

    function handleRecoverProfile(event) {
        event.preventDefault();

        const email = prompt('Please enter the email address you used when creating your profile:');
        if (email && email.trim()) {
            const trimmedEmail = email.trim();
            console.log('User provided email for profile recovery:', trimmedEmail);
            loadExistingData(trimmedEmail);
        }
    }

    function loadExistingData(email) {
        fetch(`api/get_participant.php?email=${encodeURIComponent(email)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.participant) {
                    isEditMode = true;
                    currentEmail = email;
                    modalTitle.textContent = 'Edit Your Information';
                    deleteBtn.style.display = 'inline-block';

                    // Store email for future sessions (in case it was manually entered)
                    storeUserEmail(email);

                    // Populate form with existing data
                    document.getElementById('firstName').value = data.participant.first_name;
                    document.getElementById('lastName').value = data.participant.last_name;
                    document.getElementById('email').value = data.participant.email;
                    document.getElementById('emailPublic').checked = data.participant.email_public == 1;
                    document.getElementById('description').value = data.participant.description || '';

                    // Set interests using the new combobox
                    if (window.setSelectedInterests) {
                        window.setSelectedInterests(data.participant.interests || '');
                    }

                    // Handle arXiv links
                    if (data.participant.arxiv_links) {
                        const links = JSON.parse(data.participant.arxiv_links);
                        // Handle both old format (simple URLs) and new format (objects with url/title)
                        const urlList = links.map(link => {
                            if (typeof link === 'string') {
                                return link; // Old format: simple URL
                            } else if (link && link.url) {
                                return link.url; // New format: extract URL from object
                            }
                            return ''; // Invalid entry
                        }).filter(url => url.trim() !== '');
                        document.getElementById('arxivLinks').value = urlList.join('\n');
                    }

                    // Handle talks data
                    document.getElementById('talkFlash').checked = data.participant.talk_flash == 1;
                    document.getElementById('talkContributed').checked = data.participant.talk_contributed == 1;
                    document.getElementById('talkTitle').value = data.participant.talk_title || '';
                    document.getElementById('talkAbstract').value = data.participant.talk_abstract || '';

                    // Show/hide contributed talk details based on checkbox state
                    const contributedTalkDetails = document.getElementById('contributedTalkDetails');
                    if (data.participant.talk_contributed == 1) {
                        contributedTalkDetails.style.display = 'block';
                    } else {
                        contributedTalkDetails.style.display = 'none';
                    }

                    // Disable email field in edit mode
                    document.getElementById('email').disabled = true;
                } else {
                    // No existing entry found, switch to add mode
                    isEditMode = false;
                    modalTitle.textContent = 'Add Your Information';
                    document.getElementById('email').disabled = false;
                    deleteBtn.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error loading participant data:', error);
                isEditMode = false;
                modalTitle.textContent = 'Add Your Information';
                document.getElementById('email').disabled = false;
                deleteBtn.style.display = 'none';
            });
    }

    function handleFormSubmit(event) {
        event.preventDefault();

        // Get the submit button and show loading state
        const submitBtn = participantForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';

        // Temporarily enable email field if disabled for form submission
        const emailField = document.getElementById('email');
        const wasDisabled = emailField.disabled;
        if (wasDisabled) {
            emailField.disabled = false;
        }

        const formData = new FormData(participantForm);

        // Re-disable email field if it was disabled
        if (wasDisabled) {
            emailField.disabled = true;
        }

        // Process arXiv links
        const arxivText = formData.get('arxiv_links').trim();
        const arxivLinks = arxivText ? arxivText.split('\n').filter(link => link.trim()) : [];
        formData.set('arxiv_links', JSON.stringify(arxivLinks));

        // Add edit mode flag
        formData.set('is_edit', isEditMode ? '1' : '0');
        if (isEditMode && currentEmail) {
            formData.set('original_email', currentEmail);
        }

        fetch('api/save_participant.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            return response.text().then(text => {
                console.log('Raw response:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response text:', text);
                    throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                }
            });
        })
        .then(data => {
            if (data.success) {
                // Store email for future edits
                storeUserEmail(formData.get('email'));

                // Close modal and reload page
                closeModal();
                window.location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to save participant data'));
            }
        })
        .catch(error => {
            console.error('Error saving participant:', error);
            alert('Error saving participant data: ' + error.message);
        })
        .finally(() => {
            // Restore button state (only if page isn't reloading)
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    }

    // Simple date parsing for "Jul 20" format using config year
    function parseSimpleDate(dateStr) {
        if (!dateStr || !dateStr.trim()) return null;
        const months = {
            'Jan': 0, 'Feb': 1, 'Mar': 2, 'Apr': 3, 'May': 4, 'Jun': 5,
            'Jul': 6, 'Aug': 7, 'Sep': 8, 'Oct': 9, 'Nov': 10, 'Dec': 11
        };
        const parts = dateStr.trim().split(' ');
        if (parts.length === 2) {
            const month = months[parts[0]];
            const day = parseInt(parts[1]);
            if (month !== undefined && day && day >= 1 && day <= 31) {
                // Extract year from config start date
                const configStartDate = window.conferenceConfig?.startDate;
                const year = parseInt(configStartDate.split('-')[0]);
                return new Date(year, month, day);
            }
        }
        return null;
    }

    // Check if person is present on a specific date
    function isPersonPresentOnDate(startDate, endDate, targetDate) {
        const start = parseSimpleDate(startDate);
        const end = parseSimpleDate(endDate);
        const target = parseSimpleDate(targetDate);

        if (!target) return false; // Invalid target date

        // If no dates specified, assume present all days
        if (!start || !end) return true;

        return target >= start && target <= end;
    }

    // Check if person is present during a week (Mon-Fri)
    function isPersonPresentInWeek(startDate, endDate, weekNumber) {
        const start = parseSimpleDate(startDate);
        const end = parseSimpleDate(endDate);

        // If no dates specified, assume present all weeks
        if (!start || !end) return true;

        // Get conference start date from config
        const configStartDate = window.conferenceConfig?.startDate;
        const conferenceStart = new Date(configStartDate);

        // Find the first Monday of the conference (or the start date if it's already Monday)
        const firstMonday = new Date(conferenceStart);
        const dayOfWeek = conferenceStart.getDay(); // 0 = Sunday, 1 = Monday, etc.

        if (dayOfWeek === 0) {
            // If conference starts on Sunday, first Monday is the next day
            firstMonday.setDate(conferenceStart.getDate() + 1);
        } else if (dayOfWeek > 1) {
            // If conference starts Tue-Sat, find the next Monday
            firstMonday.setDate(conferenceStart.getDate() + (8 - dayOfWeek));
        }
        // If dayOfWeek === 1, it's already Monday, so no change needed

        // Calculate week start (Monday of week N)
        const weekStart = new Date(firstMonday);
        weekStart.setDate(firstMonday.getDate() + (weekNumber - 1) * 7);

        // Calculate week end (Friday of week N)
        const weekEnd = new Date(weekStart);
        weekEnd.setDate(weekStart.getDate() + 4); // Monday + 4 = Friday

        // Check if person's dates overlap with the week
        return start <= weekEnd && end >= weekStart;
    }

    // Check if person is present ONLY in a specific week (not in other weeks)
    function isPersonPresentOnlyInWeek(startDate, endDate, weekNumber) {
        const start = parseSimpleDate(startDate);
        const end = parseSimpleDate(endDate);

        // If no dates specified, they're present all weeks, so not "only" one week
        if (!start || !end) return false;

        // First check if they're present in the target week
        if (!isPersonPresentInWeek(startDate, endDate, weekNumber)) {
            return false;
        }

        // Get total number of weeks to check against
        const configStartDate = window.conferenceConfig?.startDate;
        const configEndDate = window.conferenceConfig?.endDate;
        const conferenceStart = new Date(configStartDate);
        const conferenceEnd = new Date(configEndDate);

        // Find the first Monday
        const firstMonday = new Date(conferenceStart);
        const dayOfWeek = conferenceStart.getDay();

        if (dayOfWeek === 0) {
            firstMonday.setDate(conferenceStart.getDate() + 1);
        } else if (dayOfWeek > 1) {
            firstMonday.setDate(conferenceStart.getDate() + (8 - dayOfWeek));
        }

        // Calculate total number of weeks
        let numWeeks = 0;
        const currentWeekStart = new Date(firstMonday);

        while (currentWeekStart <= conferenceEnd) {
            const weekEnd = new Date(currentWeekStart);
            weekEnd.setDate(currentWeekStart.getDate() + 4);

            if (currentWeekStart <= conferenceEnd && weekEnd >= conferenceStart) {
                numWeeks++;
            }

            currentWeekStart.setDate(currentWeekStart.getDate() + 7);
        }

        // Check if they're present in any other week
        for (let week = 1; week <= numWeeks; week++) {
            if (week !== weekNumber && isPersonPresentInWeek(startDate, endDate, week)) {
                return false; // Present in another week, so not "only" in target week
            }
        }

        return true; // Only present in the target week
    }

    // Populate date filter dropdown - improved approach
    function populateDateFilter() {
        const cards = Array.from(participantsList.children);
        let participantsWithDates = 0;

        // Count participants with dates
        cards.forEach(card => {
            const startDate = card.dataset.startDate;
            const endDate = card.dataset.endDate;

            if (startDate && endDate && startDate.trim() && endDate.trim()) {
                participantsWithDates++;
            }
        });

        // Clear existing options except "All dates"
        while (dateFilter.children.length > 1) {
            dateFilter.removeChild(dateFilter.lastChild);
        }

        // Generate week options and dates dynamically from config
        const configStartDate = window.conferenceConfig?.startDate;
        const configEndDate = window.conferenceConfig?.endDate;

        const conferenceStart = new Date(configStartDate);
        const conferenceEnd = new Date(configEndDate);

        // Find the first Monday of the conference (or the start date if it's already Monday)
        const firstMonday = new Date(conferenceStart);
        const startDayOfWeek = conferenceStart.getDay(); // 0 = Sunday, 1 = Monday, etc.

        if (startDayOfWeek === 0) {
            // If conference starts on Sunday, first Monday is the next day
            firstMonday.setDate(conferenceStart.getDate() + 1);
        } else if (startDayOfWeek > 1) {
            // If conference starts Tue-Sat, find the next Monday
            firstMonday.setDate(conferenceStart.getDate() + (8 - startDayOfWeek));
        }
        // If startDayOfWeek === 1, it's already Monday, so no change needed

        // Calculate number of weeks based on Monday-Friday periods
        let numWeeks = 0;
        const currentWeekStart = new Date(firstMonday);

        while (currentWeekStart <= conferenceEnd) {
            const weekEnd = new Date(currentWeekStart);
            weekEnd.setDate(currentWeekStart.getDate() + 4); // Friday

            // If this week overlaps with the conference period, count it
            if (currentWeekStart <= conferenceEnd && weekEnd >= conferenceStart) {
                numWeeks++;
            }

            // Move to next Monday
            currentWeekStart.setDate(currentWeekStart.getDate() + 7);
        }

        // Add week options with date ranges
        if (numWeeks > 0) {
            // Add a separator for weeks
            const weekSeparator = document.createElement('option');
            weekSeparator.disabled = true;
            weekSeparator.textContent = '--- Weeks ---';
            dateFilter.appendChild(weekSeparator);

            for (let week = 1; week <= numWeeks; week++) {
                // Calculate week start (Monday of week N)
                const weekStart = new Date(firstMonday);
                weekStart.setDate(firstMonday.getDate() + (week - 1) * 7);

                // Calculate week end (Friday of week N)
                const weekEnd = new Date(weekStart);
                weekEnd.setDate(weekStart.getDate() + 4); // Monday + 4 = Friday

                // Format dates for display
                const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                const startMonth = monthNames[weekStart.getMonth()];
                const startDay = weekStart.getDate();
                const endMonth = monthNames[weekEnd.getMonth()];
                const endDay = weekEnd.getDate();

                let weekLabel;
                if (startMonth === endMonth) {
                    weekLabel = `Week ${week} (${startMonth} ${startDay}-${endDay})`;
                } else {
                    weekLabel = `Week ${week} (${startMonth} ${startDay} - ${endMonth} ${endDay})`;
                }

                const option = document.createElement('option');
                option.value = `week${week}`;
                option.textContent = weekLabel;
                dateFilter.appendChild(option);
            }

            // Add "Only Week n" options if there are multiple weeks
            if (numWeeks > 1) {
                const onlyWeekSeparator = document.createElement('option');
                onlyWeekSeparator.disabled = true;
                onlyWeekSeparator.textContent = '--- Only Specific Week ---';
                dateFilter.appendChild(onlyWeekSeparator);

                for (let week = 1; week <= numWeeks; week++) {
                    const onlyOption = document.createElement('option');
                    onlyOption.value = `onlyweek${week}`;
                    onlyOption.textContent = `Only Week ${week}`;
                    dateFilter.appendChild(onlyOption);
                }
            }
        }

        // Generate individual weekday dates (Mon-Fri) between start and end
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const currentDate = new Date(conferenceStart);
        let hasIndividualDates = false;

        // First pass: check if we have any weekdays to show
        const tempDate = new Date(conferenceStart);
        while (tempDate <= conferenceEnd) {
            const dayOfWeek = tempDate.getDay();
            if (dayOfWeek >= 1 && dayOfWeek <= 5) {
                hasIndividualDates = true;
                break;
            }
            tempDate.setDate(tempDate.getDate() + 1);
        }

        if (hasIndividualDates) {
            // Add a separator for individual dates
            const dateSeparator = document.createElement('option');
            dateSeparator.disabled = true;
            dateSeparator.textContent = '--- Individual Days ---';
            dateFilter.appendChild(dateSeparator);

            while (currentDate <= conferenceEnd) {
                const dayOfWeek = currentDate.getDay(); // 0 = Sunday, 1 = Monday, ..., 6 = Saturday

                // Only include weekdays (Monday = 1 to Friday = 5)
                if (dayOfWeek >= 1 && dayOfWeek <= 5) {
                    const month = monthNames[currentDate.getMonth()];
                    const day = currentDate.getDate();
                    const dateStr = `${month} ${day}`;

                    const option = document.createElement('option');
                    option.value = dateStr;
                    option.textContent = dateStr;
                    dateFilter.appendChild(option);
                }

                // Move to next day
                currentDate.setDate(currentDate.getDate() + 1);
            }
        }

        // Log for debugging if needed
        if (participantsWithDates > 0) {
            console.log(`Date filter populated: ${participantsWithDates} participants with dates, ${numWeeks} weeks`);
        }
    }

    function sortParticipants() {
        const sortBy = sortSelect.value;
        const cards = Array.from(participantsList.children);

        if (sortBy === 'random') {
            // Fisher-Yates shuffle algorithm for true randomization
            for (let i = cards.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [cards[i], cards[j]] = [cards[j], cards[i]];
            }
        } else {
            // Alphabetical sorting
            cards.sort((a, b) => {
                const aValue = a.dataset[sortBy === 'first_name' ? 'firstName' : 'lastName'].toLowerCase();
                const bValue = b.dataset[sortBy === 'first_name' ? 'firstName' : 'lastName'].toLowerCase();
                return aValue.localeCompare(bValue);
            });
        }

        // Re-append sorted cards
        cards.forEach(card => participantsList.appendChild(card));
    }

    function filterParticipants() {
        const filterText = filterInput.value.toLowerCase();
        const selectedInterest = interestFilter.value.toLowerCase();
        const selectedDate = dateFilter.value.trim();
        const cards = participantsList.children;

        Array.from(cards).forEach(card => {
            const interests = card.dataset.interests.toLowerCase();
            const firstName = card.dataset.firstName.toLowerCase();
            const lastName = card.dataset.lastName.toLowerCase();
            const startDate = card.dataset.startDate;
            const endDate = card.dataset.endDate;

            let matches = true;

            // Filter by text input
            if (filterText) {
                matches = interests.includes(filterText) ||
                         firstName.includes(filterText) ||
                         lastName.includes(filterText);
            }

            // Filter by selected interest dropdown
            if (selectedInterest && matches) {
                matches = interests.includes(selectedInterest);
            }

            // Filter by date/week
            if (selectedDate && matches) {
                if (selectedDate.startsWith('onlyweek')) {
                    const weekNumber = parseInt(selectedDate.replace('onlyweek', ''));
                    matches = isPersonPresentOnlyInWeek(startDate, endDate, weekNumber);
                } else if (selectedDate.startsWith('week')) {
                    const weekNumber = parseInt(selectedDate.replace('week', ''));
                    matches = isPersonPresentInWeek(startDate, endDate, weekNumber);
                } else {
                    matches = isPersonPresentOnDate(startDate, endDate, selectedDate);
                }
            }

            card.style.display = matches ? 'block' : 'none';
        });
    }

    function setupPhotoUpload() {
        // Click to browse
        photoDropZone.addEventListener('click', () => {
            photoInput.click();
        });

        // File input change
        photoInput.addEventListener('change', handleFileSelect);

        // Drag and drop events
        photoDropZone.addEventListener('dragover', handleDragOver);
        photoDropZone.addEventListener('dragleave', handleDragLeave);
        photoDropZone.addEventListener('drop', handleDrop);

        // Remove photo
        removePhotoBtn.addEventListener('click', removePhoto);
    }

    function handleDragOver(e) {
        e.preventDefault();
        photoDropZone.classList.add('dragover');
    }

    function handleDragLeave(e) {
        e.preventDefault();
        photoDropZone.classList.remove('dragover');
    }

    function handleDrop(e) {
        e.preventDefault();
        photoDropZone.classList.remove('dragover');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFile(files[0]);
        }
    }

    function handleFileSelect(e) {
        const files = e.target.files;
        if (files.length > 0) {
            handleFile(files[0]);
        }
    }

    function handleFile(file) {
        if (!file.type.startsWith('image/')) {
            alert('Please select an image file.');
            return;
        }

        // Create a new FileList with the dropped file and assign it to the input
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        photoInput.files = dataTransfer.files;

        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            photoPreview.style.display = 'block';
            photoDropZone.style.display = 'none';
        };
        reader.readAsDataURL(file);
    }

    function removePhoto() {
        photoInput.value = '';
        photoPreview.style.display = 'none';
        photoDropZone.style.display = 'block';
        previewImage.src = '';
    }

    function switchTab(tabName) {
        // Remove active class from all tabs and content
        participantsTab.classList.remove('active');
        blackboardTab.classList.remove('active');
        participantsContent.classList.remove('active');
        blackboardContent.classList.remove('active');

        // Add active class to selected tab and content
        if (tabName === 'participants') {
            participantsTab.classList.add('active');
            participantsContent.classList.add('active');
            addEditBtn.style.display = 'inline-block';
        } else if (tabName === 'blackboard') {
            blackboardTab.classList.add('active');
            blackboardContent.classList.add('active');
            addEditBtn.style.display = 'none';

            // Lazy load the iframe content when first accessed
            const iframe = document.getElementById('blackboardIframe');
            if (iframe && !iframe.src && iframe.dataset.src) {
                iframe.src = iframe.dataset.src;
            }
        }
    }

    function handleDeleteProfile() {
        if (!isEditMode || !currentEmail) {
            alert('Error: No profile to delete');
            return;
        }

        const confirmMessage = `Are you sure you want to delete your profile?\n\nThis action cannot be undone and will permanently remove:\n- Your personal information\n- Your photo\n- Your research interests\n- Your arXiv links\n\nType "DELETE" to confirm:`;

        const confirmation = prompt(confirmMessage);

        if (confirmation !== 'DELETE') {
            return; // User cancelled or didn't type DELETE
        }

        // Show loading state
        deleteBtn.disabled = true;
        deleteBtn.textContent = 'Deleting...';

        const formData = new FormData();
        formData.append('email', currentEmail);

        fetch('api/delete_participant.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Clear stored email
                clearUserEmail();

                // Close modal and reload page
                closeModal();
                alert('Profile deleted successfully');
                window.location.reload();
            } else {
                alert('Error deleting profile: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error deleting profile:', error);
            alert('Error deleting profile: ' + error.message);
        })
        .finally(() => {
            // Reset button state
            deleteBtn.disabled = false;
            deleteBtn.textContent = 'Delete Profile';
        });
    }

    // Multi-select interests combobox functionality
    function setupInterestsCombobox() {
        const searchInput = document.getElementById('interestSearch');
        const dropdown = document.getElementById('interestDropdown');
        const selectedTags = document.getElementById('selectedTags');
        const hiddenInput = document.getElementById('interests');
        const dropdownItems = dropdown.querySelectorAll('.dropdown-item');

        let selectedInterests = [];

        let blurTimeout = null;

        // Show/hide dropdown
        searchInput.addEventListener('focus', () => {
            // Clear any pending blur timeout
            if (blurTimeout) {
                clearTimeout(blurTimeout);
                blurTimeout = null;
            }
            dropdown.style.display = 'block';
            filterDropdownItems();
        });

        searchInput.addEventListener('blur', () => {
            // Delay hiding to allow clicking on dropdown items
            blurTimeout = setTimeout(() => {
                dropdown.style.display = 'none';
                blurTimeout = null;
            }, 200);
        });

        // Filter dropdown items based on search
        searchInput.addEventListener('input', () => {
            dropdown.style.display = 'block';
            filterDropdownItems();
        });

        // Handle dropdown item clicks
        dropdownItems.forEach(item => {
            item.addEventListener('click', () => {
                // Clear blur timeout to prevent dropdown from hiding
                if (blurTimeout) {
                    clearTimeout(blurTimeout);
                    blurTimeout = null;
                }

                const value = item.dataset.value;
                addInterest(value);
                searchInput.value = '';

                // Keep dropdown open and focused for next selection
                dropdown.style.display = 'block';
                searchInput.focus();
                filterDropdownItems();
            });
        });

        // Handle Enter key to add custom interest
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const value = searchInput.value.trim();
                if (value) {
                    addInterest(value);
                    searchInput.value = '';
                    filterDropdownItems();
                }
            }
        });

        function filterDropdownItems() {
            const searchTerm = searchInput.value.toLowerCase();
            dropdownItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                const value = item.dataset.value;
                const isSelected = selectedInterests.includes(value);
                const matches = text.includes(searchTerm);

                if (isSelected) {
                    item.classList.add('selected');
                } else {
                    item.classList.remove('selected');
                }

                if (matches && !isSelected) {
                    item.classList.remove('hidden');
                } else {
                    item.classList.add('hidden');
                }
            });
        }

        function addInterest(value) {
            if (!selectedInterests.includes(value)) {
                selectedInterests.push(value);
                updateTagsDisplay();
                updateHiddenInput();
            }
        }

        function removeInterest(value) {
            selectedInterests = selectedInterests.filter(interest => interest !== value);
            updateTagsDisplay();
            updateHiddenInput();
            filterDropdownItems();
        }

        function updateTagsDisplay() {
            selectedTags.innerHTML = '';
            selectedInterests.forEach(interest => {
                const tag = document.createElement('div');
                tag.className = 'tag';
                tag.innerHTML = `
                    <span>${interest}</span>
                    <span class="remove" data-value="${interest}">&times;</span>
                `;

                tag.querySelector('.remove').addEventListener('click', () => {
                    removeInterest(interest);
                });

                selectedTags.appendChild(tag);
            });
        }

        function updateHiddenInput() {
            hiddenInput.value = selectedInterests.join(', ');
        }

        // Public method to set interests (for editing)
        window.setSelectedInterests = function(interestsString) {
            selectedInterests = interestsString ?
                interestsString.split(',').map(s => s.trim()).filter(s => s) : [];
            updateTagsDisplay();
            updateHiddenInput();
            filterDropdownItems();
        };
    }

    function setupTalksSection() {
        const talkContributedCheckbox = document.getElementById('talkContributed');
        const contributedTalkDetails = document.getElementById('contributedTalkDetails');

        if (talkContributedCheckbox && contributedTalkDetails) {
            talkContributedCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    contributedTalkDetails.style.display = 'block';
                } else {
                    contributedTalkDetails.style.display = 'none';
                    // Clear the fields when hiding
                    document.getElementById('talkTitle').value = '';
                    document.getElementById('talkAbstract').value = '';
                }
            });
        }
    }

    // Check for signup URL parameter
    function checkSignupParam() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('signup') === '1' || urlParams.get('add') === '1') {
            // Small delay to ensure DOM is ready
            setTimeout(() => {
                openModal();
                // Clean up URL without reloading page
                const newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
            }, 100);
        }
    }

    // Initialize date filter and sorting
    populateDateFilter();
    sortParticipants();

    // Check for signup parameter
    checkSignupParam();

    // Initialize MathJax rendering for arXiv titles
    initializeMathJax();
});

// Function to render MathJax in arXiv titles
function initializeMathJax() {
    // Wait for MathJax to be loaded
    if (typeof window.MathJax !== 'undefined' && window.MathJax.typesetPromise) {
        // Render LaTeX in arXiv titles
        window.MathJax.typesetPromise().then(() => {
            console.log('MathJax rendering complete');
        }).catch((err) => {
            console.log('MathJax rendering error:', err);
        });
    } else {
        // If MathJax isn't loaded yet, wait and try again
        setTimeout(initializeMathJax, 100);
    }
}
