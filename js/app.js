// Benasque 25 Conference Participants JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // DOM elements
    const addEditBtn = document.getElementById('addEditBtn');
    const modal = document.getElementById('addEditModal');
    const closeBtn = document.querySelector('.close');
    const cancelBtn = document.getElementById('cancelBtn');
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

    window.addEventListener('click', function(event) {
        if (event.target === modal) {
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

                    // Populate form with existing data
                    document.getElementById('firstName').value = data.participant.first_name;
                    document.getElementById('lastName').value = data.participant.last_name;
                    document.getElementById('email').value = data.participant.email;
                    document.getElementById('interests').value = data.participant.interests || '';
                    document.getElementById('description').value = data.participant.description || '';

                    // Handle arXiv links
                    if (data.participant.arxiv_links) {
                        const links = JSON.parse(data.participant.arxiv_links);
                        document.getElementById('arxivLinks').value = links.join('\n');
                    }

                    // Disable email field in edit mode
                    document.getElementById('email').disabled = true;
                } else {
                    // No existing entry found, switch to add mode
                    isEditMode = false;
                    modalTitle.textContent = 'Add Your Information';
                    document.getElementById('email').disabled = false;
                }
            })
            .catch(error => {
                console.error('Error loading participant data:', error);
                isEditMode = false;
                modalTitle.textContent = 'Add Your Information';
                document.getElementById('email').disabled = false;
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

    // Initialize sorting
    sortParticipants();
});
