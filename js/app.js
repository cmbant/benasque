// Benasque 25 Conference Participants JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // DOM elements
    const addEditBtn = document.getElementById('addEditBtn');
    const modal = document.getElementById('addEditModal');
    const closeBtn = document.querySelector('.close');
    const cancelBtn = document.getElementById('cancelBtn');
    const deleteBtn = document.getElementById('deleteBtn');
    const participantForm = document.getElementById('participantForm');
    const sortSelect = document.getElementById('sortSelect');
    const filterInput = document.getElementById('filterInput');
    const interestFilter = document.getElementById('interestFilter');
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

    // Form submission
    participantForm.addEventListener('submit', handleFormSubmit);

    // Photo upload functionality
    setupPhotoUpload();

    // Multi-select interests functionality
    setupInterestsCombobox();

    function openModal() {
        // Check if user has an existing entry
        const userEmail = localStorage.getItem('benasque25_email');

        if (userEmail) {
            // Try to load existing data for editing
            loadExistingData(userEmail);
        } else {
            // New entry mode
            isEditMode = false;
            modalTitle.textContent = 'Add Your Information';
            participantForm.reset();
            deleteBtn.style.display = 'none';
        }

        modal.style.display = 'block';
    }

    function closeModal() {
        modal.style.display = 'none';
        participantForm.reset();
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
                // Store email in localStorage for future edits
                localStorage.setItem('benasque25_email', formData.get('email'));

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
        });
    }

    function sortParticipants() {
        const sortBy = sortSelect.value;
        const cards = Array.from(participantsList.children);

        cards.sort((a, b) => {
            const aValue = a.dataset[sortBy === 'first_name' ? 'firstName' : 'lastName'].toLowerCase();
            const bValue = b.dataset[sortBy === 'first_name' ? 'firstName' : 'lastName'].toLowerCase();
            return aValue.localeCompare(bValue);
        });

        // Re-append sorted cards
        cards.forEach(card => participantsList.appendChild(card));
    }

    function filterParticipants() {
        const filterText = filterInput.value.toLowerCase();
        const selectedInterest = interestFilter.value.toLowerCase();
        const cards = participantsList.children;

        Array.from(cards).forEach(card => {
            const interests = card.dataset.interests.toLowerCase();
            const firstName = card.dataset.firstName.toLowerCase();
            const lastName = card.dataset.lastName.toLowerCase();

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
                // Clear localStorage
                localStorage.removeItem('benasque25_email');

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

    // Initialize sorting
    sortParticipants();
});
