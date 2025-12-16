// assets/script.js - Dynamic Form Logic

document.addEventListener('DOMContentLoaded', () => {
    // ===== State Management =====
    let highlights = [];
    let reviews = [];
    let departments = [];
    let specialties = [];
    let equipment = [];
    let doctors = [];
    let treatments = [];
    let packages = [];
    let selectedFiles = []; // Hospital photos
    let doctorPhotos = {};  // Maps doctor index to photo URL
    let currentTab = 0;
    const totalTabs = 5;

    // ===== Helper: Get Image Dimensions =====
    function getImageDimensions(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    resolve({ width: img.width, height: img.height });
                };
                img.onerror = () => {
                    reject(new Error('Failed to load image'));
                };
                img.src = e.target.result;
            };
            reader.onerror = () => {
                reject(new Error('Failed to read file'));
            };
            reader.readAsDataURL(file);
        });
    }

    // ===== Tab Navigation =====
    const tabs = document.querySelectorAll('.tab');
    const panels = document.querySelectorAll('.tab-panel');
    const progressBar = document.getElementById('progress-bar');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    const submitBtn = document.getElementById('submit-btn');

    function updateProgress() {
        const progress = ((currentTab + 1) / totalTabs) * 100;
        progressBar.style.width = progress + '%';
    }

    function updateNavigationButtons() {
        prevBtn.classList.toggle('hidden', currentTab === 0);
        nextBtn.classList.toggle('hidden', currentTab === totalTabs - 1);
        submitBtn.classList.toggle('hidden', currentTab !== totalTabs - 1);
    }

    function switchTab(index) {
        if (index < 0 || index >= totalTabs) return;
        
        // Remove active from all tabs
        tabs.forEach(t => {
            t.classList.remove('active', 'border-b-2', 'border-blue-600');
            t.classList.add('font-bold');
        });
        
        // Add active to current tab
        tabs[index].classList.add('active', 'border-b-2', 'border-blue-600');

        // Hide all panels
        panels.forEach(p => p.classList.add('hidden'));
        
        // Show target panel
        panels[index].classList.remove('hidden');
        
        currentTab = index;
        updateProgress();
        updateNavigationButtons();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // ===== Validation Logic =====
    function validateInput(input) {
        const value = input.value.trim();
        const label = input.closest('div').querySelector('label')?.innerText.replace('*', '').trim() || 'Field';
        const parent = input.parentElement;
        
        // Remove existing error
        const existingError = parent.querySelector('.error-message');
        if (existingError) existingError.remove();
        input.classList.remove('error', 'success');

        let isValid = true;
        let errorMessage = '';

        // Required check
        if (input.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = `${label} is required`;
        }
        
        // Email check
        if (isValid && input.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address';
            }
        }
        
        // URL check
        if (isValid && input.type === 'url' && value) {
            try {
                new URL(value);
            } catch (_) {
                isValid = false;
                errorMessage = 'Please enter a valid URL (e.g., https://example.com)';
            }
        }
        
        // Number range check
        if (isValid && input.type === 'number' && value) {
            const num = parseFloat(value);
            if (input.min && num < parseFloat(input.min)) {
                isValid = false;
                errorMessage = `Minimum value is ${input.min}`;
            }
            if (input.max && num > parseFloat(input.max)) {
                isValid = false;
                errorMessage = `Maximum value is ${input.max}`;
            }
        }

        if (!isValid) {
            input.classList.add('error');
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.textContent = errorMessage;
            parent.appendChild(errorDiv);
        } else if (value) {
            input.classList.add('success');
        }

        return isValid;
    }

    function validateTab(tabIndex) {
        const panel = panels[tabIndex];
        const inputs = panel.querySelectorAll('input, select, textarea');
        let isValid = true;

        inputs.forEach(input => {
            if (input.offsetParent !== null) { // Check if visible
                if (!validateInput(input)) isValid = false;
            }
        });

        // Specific Tab Validations
        if (tabIndex === 0) { // Basic Info
            const name = document.querySelector('input[name="name"]');
            if (!name.value.trim()) isValid = false;
        }

        return isValid;
    }

    // Real-time validation
    document.querySelectorAll('input, select, textarea').forEach(input => {
        input.addEventListener('blur', () => validateInput(input));
        input.addEventListener('input', () => {
            if (input.classList.contains('error')) validateInput(input);
        });
    });

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => {
            if (index > currentTab && !validateTab(currentTab)) {
                showToast('Please fix errors before proceeding', 'error');
                return;
            }
            switchTab(index);
        });
    });

    prevBtn.addEventListener('click', () => switchTab(currentTab - 1));
    nextBtn.addEventListener('click', () => {
        if (validateTab(currentTab)) {
            switchTab(currentTab + 1);
        } else {
            showToast('Please fill in all required fields', 'error');
        }
    });

    updateProgress();
    updateNavigationButtons();

    // ===== Tag Input System =====
    function createTagInput(containerId, inputId, dataArray) {
        const container = document.getElementById(containerId);
        const input = document.getElementById(inputId);

        if (!container || !input) return;

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const value = input.value.trim();
                if (value && !dataArray.includes(value)) {
                    dataArray.push(value);
                    renderTags(containerId, dataArray);
                    input.value = '';
                }
            }
        });

        input.addEventListener('focus', () => {
            container.classList.add('focus');
        });

        input.addEventListener('blur', () => {
            container.classList.remove('focus');
        });
    }

    function renderTags(containerId, dataArray) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = '';
        dataArray.forEach((item, index) => {
            const tag = document.createElement('div');
            tag.className = 'tag';
            tag.innerHTML = `
                ${item}
                <span class="tag-remove" onclick="removeTag('${containerId}', ${index})">×</span>
            `;
            container.appendChild(tag);
        });
    }

    window.removeTag = (containerId, index) => {
        let dataArray;
        if (containerId === 'departments-list') dataArray = departments;
        else if (containerId === 'specialties-list') dataArray = specialties;
        else if (containerId === 'equipment-list') dataArray = equipment;
        else if (containerId === 'highlights-list') dataArray = highlights;
        
        if (dataArray) {
            dataArray.splice(index, 1);
            renderTags(containerId, dataArray);
        }
    };

    // Initialize tag inputs (using list container IDs)
    createTagInput('departments-list', 'department-input', departments);
    createTagInput('specialties-list', 'specialty-input', specialties);
    createTagInput('equipment-list', 'equipment-input', equipment);

    // Global functions for Add buttons
    window.addDepartment = () => {
        const input = document.getElementById('department-input');
        const value = input.value.trim();
        if (value && !departments.includes(value)) {
            departments.push(value);
            renderTags('departments-list', departments);
            input.value = '';
        }
    };

    window.addSpecialty = () => {
        const input = document.getElementById('specialty-input');
        const value = input.value.trim();
        if (value && !specialties.includes(value)) {
            specialties.push(value);
            renderTags('specialties-list', specialties);
            input.value = '';
        }
    };

    window.addEquipment = () => {
        const input = document.getElementById('equipment-input');
        const value = input.value.trim();
        if (value && !equipment.includes(value)) {
            equipment.push(value);
            renderTags('equipment-list', equipment);
            input.value = '';
        }
    };

    // ===== Highlights Management =====
    window.addHighlight = () => {
        const input = document.getElementById('highlight-input');
        const value = input.value.trim();
        if (!value) return;
        if (highlights.length >= 10) {
            showToast('Maximum 10 highlights allowed', 'error');
            return;
        }
        highlights.push(value);
        renderTags('highlights-list', highlights);
        input.value = '';
    };

    // Handle Enter key on highlight input
    document.getElementById('highlight-input')?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            addHighlight();
        }
    });

    // ===== Reviews Management =====
    window.addReview = () => {
        if (reviews.length >= 20) {
            showToast('Maximum 20 reviews allowed', 'error');
            return;
        }
        reviews.push({ text: '', rating: 5 });
        renderReviews();
    };

    window.removeReview = async (index) => {
        const items = document.getElementById('reviews-container').children;
        if (items[index]) {
            items[index].classList.add('removing');
            await new Promise(r => setTimeout(r, 400));
        }
        reviews.splice(index, 1);
        renderReviews();
    };

    window.updateReview = (index, field, value) => {
        reviews[index][field] = value;
    };

    window.setRating = (reviewIndex, rating) => {
        reviews[reviewIndex].rating = rating;
        renderReviews();
    };

    function renderReviews() {
        const container = document.getElementById('reviews-container');
        if (!container) return;

        container.innerHTML = '';
        reviews.forEach((review, index) => {
            const div = document.createElement('div');
            div.className = 'field-card';
            div.innerHTML = `
                <button type="button" onclick="removeReview(${index})" class="remove-btn">×</button>
                <div class="mb-3">
                    <label class="form-label">Rating</label>
                    <div class="star-rating">
                        ${[1, 2, 3, 4, 5].map(star => `
                            <span class="star ${star <= review.rating ? 'active' : ''}" 
                                onclick="setRating(${index}, ${star})">★</span>
                        `).join('')}
                    </div>
                </div>
                <div>
                    <label class="form-label">Review Text</label>
                    <textarea onchange="updateReview(${index}, 'text', this.value)" 
                        class="form-input" rows="3" 
                        placeholder="Enter patient review...">${escapeHtml(review.text)}</textarea>
                </div>
            `;
            container.appendChild(div);
        });
    }

    // ===== Doctors Management =====
    window.addDoctor = () => {
        if (doctors.length >= 50) {
            showToast('Maximum 50 doctors allowed', 'error');
            return;
        }
        doctors.push({
            name: '',
            title: '',
            qualification: '',
            education: '',
            experience: '',
            languages: '',
            expertise: '',
            procedures: '',
            patientsCount: '',
            about: '',
            timing: '',
            awards: '',
            publications: ''
        });
        renderDoctors();
    };

    window.removeDoctor = async (index) => {
        const items = document.getElementById('doctors-container').children;
        if (items[index]) {
            items[index].classList.add('removing');
            await new Promise(r => setTimeout(r, 400));
        }
        doctors.splice(index, 1);
        renderDoctors();
    };

    window.updateDoctor = (index, field, value) => {
        doctors[index][field] = value;
    };

    function renderDoctors() {
        const container = document.getElementById('doctors-container');
        if (!container) return;

        container.innerHTML = '';
        doctors.forEach((doc, index) => {
            const div = document.createElement('div');
            div.className = 'field-card';
            div.innerHTML = `
                <button type="button" onclick="removeDoctor(${index})" class="remove-btn">×</button>
                <div class="flex items-center gap-4 mb-4">
                    <div id="doctor-photo-${index}" class="doctor-photo-container w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center overflow-hidden border border-gray-200 relative group">
                        ${doc.photoUrl 
                            ? `<img src="${doc.photoUrl}" class="w-full h-full object-cover">` 
                            : doc.photoPreview 
                                ? `<img src="${doc.photoPreview}" class="w-full h-full object-cover"><span class="absolute bottom-0 left-0 right-0 bg-blue-500 text-white text-[8px] text-center py-0.5">Pending</span>`
                                : `<svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>`
                        }
                        <label class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                            <span class="text-white text-xs text-center">Change Photo</span>
                            <input type="file" class="hidden" accept="image/*" onchange="uploadDoctorPhoto(${index}, this)">
                        </label>
                    </div>
                    <div>
                        <h3 class="font-semibold text-lg text-gray-800">Doctor #${index + 1}</h3>
                        <p class="text-xs text-gray-500">Click photo to upload</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Full Name *</label>
                        <input type="text" value="${escapeHtml(doc.name)}" 
                            onchange="updateDoctor(${index}, 'name', this.value)" 
                            class="form-input" placeholder="Dr. John Smith" required />
                    </div>
                    
                    <div>
                        <label class="form-label">Specialist Title *</label>
                        <input type="text" value="${escapeHtml(doc.title)}" 
                            onchange="updateDoctor(${index}, 'title', this.value)" 
                            class="form-input" placeholder="Cardiologist" />
                    </div>
                    
                    <div>
                        <label class="form-label">Qualification</label>
                        <input type="text" value="${escapeHtml(doc.qualification)}" 
                            onchange="updateDoctor(${index}, 'qualification', this.value)" 
                            class="form-input" placeholder="MBBS, MD, DM" />
                    </div>
                    
                    <div>
                        <label class="form-label">Experience (years)</label>
                        <input type="number" value="${escapeHtml(doc.experience)}" 
                            onchange="updateDoctor(${index}, 'experience', this.value)" 
                            class="form-input" placeholder="15" min="0" />
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="form-label">Education & Training</label>
                        <textarea onchange="updateDoctor(${index}, 'education', this.value)" 
                            class="form-input" rows="2" 
                            placeholder="Detailed academic background...">${escapeHtml(doc.education)}</textarea>
                    </div>
                    
                    <div>
                        <label class="form-label">Languages Spoken</label>
                        <input type="text" value="${escapeHtml(doc.languages)}" 
                            onchange="updateDoctor(${index}, 'languages', this.value)" 
                            class="form-input" placeholder="English, Hindi, Tamil" />
                    </div>
                    
                    <div>
                        <label class="form-label">Patients Treated</label>
                        <input type="number" value="${escapeHtml(doc.patientsCount)}" 
                            onchange="updateDoctor(${index}, 'patientsCount', this.value)" 
                            class="form-input" placeholder="5000" min="0" />
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="form-label">Areas of Expertise</label>
                        <input type="text" value="${escapeHtml(doc.expertise)}" 
                            onchange="updateDoctor(${index}, 'expertise', this.value)" 
                            class="form-input" placeholder="Heart Surgery, Angioplasty (comma-separated)" />
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="form-label">Procedures Performed</label>
                        <input type="text" value="${escapeHtml(doc.procedures)}" 
                            onchange="updateDoctor(${index}, 'procedures', this.value)" 
                            class="form-input" placeholder="CABG, Valve Replacement (comma-separated)" />
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="form-label">About/Bio (approx. 500 words)</label>
                        <textarea onchange="updateDoctor(${index}, 'about', this.value)" 
                            class="form-input" rows="4" maxlength="3000"
                            placeholder="Detailed biography...">${escapeHtml(doc.about)}</textarea>
                    </div>
                    
                    <div>
                        <label class="form-label">Consultation Timing</label>
                        <input type="text" value="${escapeHtml(doc.timing)}" 
                            onchange="updateDoctor(${index}, 'timing', this.value)" 
                            class="form-input" placeholder="Mon-Fri: 9AM-5PM" />
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="form-label">Awards & Recognitions</label>
                        <textarea onchange="updateDoctor(${index}, 'awards', this.value)" 
                            class="form-input" rows="2" 
                            placeholder="Awards, one per line...">${escapeHtml(doc.awards)}</textarea>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="form-label">Publications</label>
                        <textarea onchange="updateDoctor(${index}, 'publications', this.value)" 
                            class="form-input" rows="2" 
                            placeholder="Research papers/journals, one per line...">${escapeHtml(doc.publications)}</textarea>
                    </div>
                </div>
            `;
            container.appendChild(div);
        });
    }

    // Doctor Photo Upload
    window.uploadDoctorPhoto = async (index, input) => {
        if (!input.files || !input.files[0]) return;
        
        const file = input.files[0];
        
        // ===== Comprehensive Image Validation =====
        
        // 1. Validate file extension
        const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        const fileName = file.name.toLowerCase();
        const fileExtension = fileName.split('.').pop();
        if (!allowedExtensions.includes(fileExtension)) {
            showToast(`Invalid file format. Allowed: ${allowedExtensions.join(', ').toUpperCase()}`, 'error');
            input.value = ''; // Clear the input
            return;
        }
        
        // 2. Validate MIME type
        const allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedMimeTypes.includes(file.type)) {
            showToast('Invalid image type. Please select a JPG, PNG, GIF, or WEBP image.', 'error');
            input.value = '';
            return;
        }
        
        // 3. Validate file size (max 5MB)
        const maxSizeMB = 5;
        const maxSizeBytes = maxSizeMB * 1024 * 1024;
        if (file.size > maxSizeBytes) {
            const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
            showToast(`Image too large (${fileSizeMB}MB). Maximum size is ${maxSizeMB}MB.`, 'error');
            input.value = '';
            return;
        }
        
        // 4. Validate minimum file size (at least 1KB - to avoid empty/corrupt files)
        if (file.size < 1024) {
            showToast('Image file appears to be empty or corrupted.', 'error');
            input.value = '';
            return;
        }
        
        // Find the doctor photo container using unique ID
        const photoContainer = document.getElementById(`doctor-photo-${index}`);
        
        // 5. Validate image dimensions (async check)
        try {
            const dimensions = await getImageDimensions(file);
            
            // Minimum dimensions (100x100 px for a profile photo)
            if (dimensions.width < 100 || dimensions.height < 100) {
                showToast('Image too small. Minimum size is 100x100 pixels.', 'error');
                input.value = '';
                return;
            }
            
            // Maximum dimensions (5000x5000 px to prevent huge files)
            if (dimensions.width > 5000 || dimensions.height > 5000) {
                showToast('Image too large. Maximum dimensions: 5000x5000 pixels.', 'error');
                input.value = '';
                return;
            }
        } catch (error) {
            console.error('Error validating image dimensions:', error);
            showToast('Could not read image. Please try a different file.', 'error');
            input.value = '';
            return;
        }
        
        // Store the file locally for later upload (deferred to form submission)
        doctorPhotos[index] = file;
        
        // Create local preview using FileReader
        const reader = new FileReader();
        reader.onload = (e) => {
            // Store preview URL in doctors array for display
            doctors[index].photoPreview = e.target.result;
            renderDoctors();
            showToast('Photo added! It will be uploaded when you submit the form.', 'success');
        };
        reader.onerror = () => {
            showToast('Could not preview image. Please try again.', 'error');
        };
        reader.readAsDataURL(file);
    };

    // ===== Treatments Management =====
    window.addTreatment = () => {
        if (treatments.length >= 50) {
            showToast('Maximum 50 treatments allowed', 'error');
            return;
        }
        treatments.push({
            domain: '',
            name: '',
            specialty: '',
            accreditations: '',
            price: '',
            description: '',
            recoveryTime: '',
            hospitalStay: ''
        });
        renderTreatments();
    };

    window.removeTreatment = async (index) => {
        const items = document.getElementById('treatments-container').children;
        if (items[index]) {
            items[index].classList.add('removing');
            await new Promise(r => setTimeout(r, 400));
        }
        treatments.splice(index, 1);
        renderTreatments();
    };

    window.updateTreatment = (index, field, value) => {
        treatments[index][field] = value;
    };

    function renderTreatments() {
        const container = document.getElementById('treatments-container');
        if (!container) return;

        container.innerHTML = '';
        treatments.forEach((item, index) => {
            const div = document.createElement('div');
            div.className = 'field-card';
            div.innerHTML = `
                <button type="button" onclick="removeTreatment(${index})" class="remove-btn">×</button>
                <h3 class="font-semibold text-lg text-gray-800 mb-4">Treatment #${index + 1}</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Treatment Domain *</label>
                        <select onchange="updateTreatment(${index}, 'domain', this.value)" class="form-input">
                            <option value="">Select domain</option>
                            <option value="Oncology" ${item.domain === 'Oncology' ? 'selected' : ''}>Oncology</option>
                            <option value="Cardiology" ${item.domain === 'Cardiology' ? 'selected' : ''}>Cardiology</option>
                            <option value="Orthopedics" ${item.domain === 'Orthopedics' ? 'selected' : ''}>Orthopedics</option>
                            <option value="Neurology" ${item.domain === 'Neurology' ? 'selected' : ''}>Neurology</option>
                            <option value="Gastroenterology" ${item.domain === 'Gastroenterology' ? 'selected' : ''}>Gastroenterology</option>
                            <option value="Urology" ${item.domain === 'Urology' ? 'selected' : ''}>Urology</option>
                            <option value="Other" ${item.domain === 'Other' ? 'selected' : ''}>Other</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="form-label">Treatment Name *</label>
                        <input type="text" value="${escapeHtml(item.name)}" 
                            onchange="updateTreatment(${index}, 'name', this.value)" 
                            class="form-input" placeholder="Knee Replacement" required />
                    </div>
                    
                    <div>
                        <label class="form-label">Specialty</label>
                        <input type="text" value="${escapeHtml(item.specialty)}" 
                            onchange="updateTreatment(${index}, 'specialty', this.value)" 
                            class="form-input" placeholder="Joint Replacement" />
                    </div>
                    
                    <div>
                        <label class="form-label">Price (Estimated)</label>
                        <input type="number" value="${escapeHtml(item.price)}" 
                            onchange="updateTreatment(${index}, 'price', this.value)" 
                            class="form-input" placeholder="50000" min="0" />
                    </div>
                    
                    <div>
                        <label class="form-label">Recovery Time</label>
                        <input type="text" value="${escapeHtml(item.recoveryTime)}" 
                            onchange="updateTreatment(${index}, 'recoveryTime', this.value)" 
                            class="form-input" placeholder="4-6 weeks" />
                    </div>
                    
                    <div>
                        <label class="form-label">Hospital Stay</label>
                        <input type="text" value="${escapeHtml(item.hospitalStay)}" 
                            onchange="updateTreatment(${index}, 'hospitalStay', this.value)" 
                            class="form-input" placeholder="3-5 days" />
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="form-label">Accreditations</label>
                        <input type="text" value="${escapeHtml(item.accreditations)}" 
                            onchange="updateTreatment(${index}, 'accreditations', this.value)" 
                            class="form-input" placeholder="Specific to this treatment" />
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="form-label">Description</label>
                        <textarea onchange="updateTreatment(${index}, 'description', this.value)" 
                            class="form-input" rows="4" 
                            placeholder="Detailed description of the procedure...">${escapeHtml(item.description)}</textarea>
                    </div>
                </div>
            `;
            container.appendChild(div);
        });
    }

    // ===== Packages Management =====
    window.addPackage = () => {
        if (packages.length >= 20) {
            showToast('Maximum 20 packages allowed', 'error');
            return;
        }
        packages.push({
            name: '',
            tagline: '',
            rate: '',
            duration: '',
            description: '',
            visaAssistance: '',
            flights: '',
            insurance: '',
            inclusions: '',
            addons: []
        });
        renderPackages();
    };

    window.removePackage = async (index) => {
        const items = document.getElementById('packages-container').children;
        if (items[index]) {
            items[index].classList.add('removing');
            await new Promise(r => setTimeout(r, 400));
        }
        packages.splice(index, 1);
        renderPackages();
    };

    window.updatePackage = (index, field, value) => {
        packages[index][field] = value;
    };

    window.addAddon = (pkgIndex) => {
        if (!packages[pkgIndex].addons) packages[pkgIndex].addons = [];
        if (packages[pkgIndex].addons.length >= 10) {
            showToast('Maximum 10 add-ons per package', 'error');
            return;
        }
        packages[pkgIndex].addons.push({ name: '', amount: '', description: '' });
        renderPackages();
    };

    window.removeAddon = (pkgIndex, addonIndex) => {
        packages[pkgIndex].addons.splice(addonIndex, 1);
        renderPackages();
    };

    window.updateAddon = (pkgIndex, addonIndex, field, value) => {
        packages[pkgIndex].addons[addonIndex][field] = value;
    };

    function renderPackages() {
        const container = document.getElementById('packages-container');
        if (!container) return;

        container.innerHTML = '';
        packages.forEach((pkg, index) => {
            const addonsHtml = (pkg.addons || []).map((addon, addonIndex) => `
                <div class="p-3 bg-white border border-gray-200 rounded-lg flex gap-2">
                    <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-2">
                        <input type="text" value="${escapeHtml(addon.name)}" 
                            onchange="updateAddon(${index}, ${addonIndex}, 'name', this.value)" 
                            class="form-input" placeholder="Add-on name" />
                        <input type="number" value="${escapeHtml(addon.amount)}" 
                            onchange="updateAddon(${index}, ${addonIndex}, 'amount', this.value)" 
                            class="form-input" placeholder="Amount" />
                        <input type="text" value="${escapeHtml(addon.description)}" 
                            onchange="updateAddon(${index}, ${addonIndex}, 'description', this.value)" 
                            class="form-input" placeholder="Description" />
                    </div>
                    <button type="button" onclick="removeAddon(${index}, ${addonIndex})" class="btn-danger">×</button>
                </div>
            `).join('');

            const div = document.createElement('div');
            div.className = 'field-card';
            div.innerHTML = `
                <button type="button" onclick="removePackage(${index})" class="remove-btn">×</button>
                <h3 class="font-semibold text-lg text-gray-800 mb-4">Package #${index + 1}</h3>
                
                <div class="grid grid-cols-1 gap-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Package Name *</label>
                            <input type="text" value="${escapeHtml(pkg.name)}" 
                                onchange="updatePackage(${index}, 'name', this.value)" 
                                class="form-input" placeholder="Cardiac Care Premium" required />
                        </div>
                        
                        <div>
                            <label class="form-label">Tagline</label>
                            <input type="text" value="${escapeHtml(pkg.tagline)}" 
                                onchange="updatePackage(${index}, 'tagline', this.value)" 
                                class="form-input" placeholder="Complete heart health solution" />
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Rate (Total Price)</label>
                            <input type="number" value="${escapeHtml(pkg.rate)}" 
                                onchange="updatePackage(${index}, 'rate', this.value)" 
                                class="form-input" placeholder="150000" min="0" />
                        </div>
                        
                        <div>
                            <label class="form-label">Duration/Validity</label>
                            <input type="text" value="${escapeHtml(pkg.duration)}" 
                                onchange="updatePackage(${index}, 'duration', this.value)" 
                                class="form-input" placeholder="2 weeks" />
                        </div>
                    </div>
                    
                    <div>
                        <label class="form-label">Description (9-10 lines)</label>
                        <textarea onchange="updatePackage(${index}, 'description', this.value)" 
                            class="form-input" rows="6" maxlength="1500"
                            placeholder="Detailed package overview...">${escapeHtml(pkg.description)}</textarea>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="form-label">Visa Assistance</label>
                            <select onchange="updatePackage(${index}, 'visaAssistance', this.value)" class="form-input">
                                <option value="No" ${pkg.visaAssistance === 'No' ? 'selected' : ''}>No</option>
                                <option value="Yes" ${pkg.visaAssistance === 'Yes' ? 'selected' : ''}>Yes</option>
                                <option value="On Request" ${pkg.visaAssistance === 'On Request' ? 'selected' : ''}>On Request</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="form-label">Flights</label>
                            <select onchange="updatePackage(${index}, 'flights', this.value)" class="form-input">
                                <option value="Excluded" ${pkg.flights === 'Excluded' ? 'selected' : ''}>Excluded</option>
                                <option value="Included" ${pkg.flights === 'Included' ? 'selected' : ''}>Included</option>
                                <option value="Discounted" ${pkg.flights === 'Discounted' ? 'selected' : ''}>Discounted</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="form-label">Insurance</label>
                            <input type="text" value="${escapeHtml(pkg.insurance)}" 
                                onchange="updatePackage(${index}, 'insurance', this.value)" 
                                class="form-input" placeholder="Coverage details" />
                        </div>
                    </div>
                    
                    <div>
                        <label class="form-label">General Inclusions</label>
                        <textarea onchange="updatePackage(${index}, 'inclusions', this.value)" 
                            class="form-input" rows="3" 
                            placeholder="List of included services (one per line or comma-separated)...">${escapeHtml(pkg.inclusions)}</textarea>
                    </div>
                    
                    <div>
                        <label class="form-label mb-2 block">Add-ons (Upsells)</label>
                        <div class="space-y-2">
                            ${addonsHtml}
                        </div>
                        <button type="button" onclick="addAddon(${index})" class="btn-secondary mt-2">+ Add Addon</button>
                    </div>
                </div>
            `;
            container.appendChild(div);
        });
    }

    // ===== File Upload =====
    const uploadArea = document.getElementById('photo-dropzone');
    const photosInput = document.getElementById('photo-input');
    const imagePreview = document.getElementById('photos-preview');

    if (uploadArea && photosInput) {
        uploadArea.addEventListener('click', () => photosInput.click());

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('border-blue-500', 'bg-blue-50');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('border-blue-500', 'bg-blue-50');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('border-blue-500', 'bg-blue-50');
            const files = e.dataTransfer.files;
            handleFiles(files);
        });

        photosInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });
    }

    function handleFiles(files) {
        const newFiles = Array.from(files);
        selectedFiles.push(...newFiles);
        renderImagePreviews();
    }

    function renderImagePreviews() {
        if (!imagePreview) return;
        imagePreview.innerHTML = '';
        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const div = document.createElement('div');
                div.className = 'relative group';
                div.innerHTML = `
                    <img src="${e.target.result}" alt="Preview" class="w-full h-32 object-cover rounded-lg" />
                    <button type="button" onclick="removeImage(${index})" 
                        class="absolute top-2 right-2 w-6 h-6 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition">×</button>
                `;
                imagePreview.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }

    window.removeImage = (index) => {
        selectedFiles.splice(index, 1);
        renderImagePreviews();
    };

    // ===== Star Rating for Reviews =====
    let currentRating = 0;
    document.querySelectorAll('#star-rating .star').forEach(star => {
        star.addEventListener('click', () => {
            currentRating = parseInt(star.dataset.star);
            updateStarDisplay();
        });
    });

    function updateStarDisplay() {
        document.querySelectorAll('#star-rating .star').forEach(star => {
            const starValue = parseInt(star.dataset.star);
            if (starValue <= currentRating) {
                star.classList.remove('text-gray-300');
                star.classList.add('text-yellow-400');
            } else {
                star.classList.remove('text-yellow-400');
                star.classList.add('text-gray-300');
            }
        });
    }

    // Review add handler
    window.addReview = () => {
        const author = document.getElementById('review-author')?.value.trim();
        const text = document.getElementById('review-text')?.value.trim();
        if (!text) {
            showToast('Please enter review text', 'error');
            return;
        }
        if (reviews.length >= 20) {
            showToast('Maximum 20 reviews allowed', 'error');
            return;
        }
        reviews.push({ author: author || 'Anonymous', text, rating: currentRating || 5 });
        renderReviewsList();
        // Clear inputs
        document.getElementById('review-author').value = '';
        document.getElementById('review-text').value = '';
        currentRating = 0;
        updateStarDisplay();
    };

    function renderReviewsList() {
        const container = document.getElementById('reviews-list');
        if (!container) return;
        container.innerHTML = '';
        reviews.forEach((review, index) => {
            const div = document.createElement('div');
            div.className = 'p-3 bg-white border border-gray-200 rounded-lg flex justify-between items-start';
            div.innerHTML = `
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-medium text-black">${escapeHtml(review.author)}</span>
                        <span class="text-yellow-400">${'★'.repeat(review.rating)}${'☆'.repeat(5-review.rating)}</span>
                    </div>
                    <p class="text-gray-700 text-sm">${escapeHtml(review.text)}</p>
                </div>
                <button type="button" onclick="removeReview(${index})" class="text-red-500 hover:text-red-700">×</button>
            `;
            container.appendChild(div);
        });
    }

    window.removeReview = (index) => {
        reviews.splice(index, 1);
        renderReviewsList();
    };

    // ===== Form Submission =====
    const form = document.getElementById('hospital-form');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const submitText = document.getElementById('submit-text');
        const submitLoading = document.getElementById('submit-loading');
        submitBtn.disabled = true;
        submitText.classList.add('hidden');
        submitLoading.classList.remove('hidden');

        try {
            // 1. Upload Images
            let uploadedUrls = [];
            if (selectedFiles.length > 0) {
                showUploadLoader(`Uploading ${selectedFiles.length} photo${selectedFiles.length > 1 ? 's' : ''}...`);
                
                // Upload each file directly to Cloudinary using signed uploads
                const API_BASE = window.API_BASE_URL || '';
                const sigUrl = API_BASE ? API_BASE + '/api/cloudinary_signature.php' : 'api/cloudinary_signature.php';
                const sigRes = await fetch(sigUrl);
                if (!sigRes.ok) {
                    const txt = await sigRes.text();
                    throw new Error('Signature request failed: ' + sigRes.status + ' ' + txt);
                }
                const ct = sigRes.headers.get('content-type') || '';
                if (!ct.includes('application/json')) {
                    const txt = await sigRes.text();
                    throw new Error('Signature endpoint returned non-JSON: ' + txt);
                }
                const sig = await sigRes.json();
                if (sig.error) throw new Error(sig.error || 'Signature error');

                const uploadPromises = selectedFiles.map(async (file) => {
                    const uploadForm = new FormData();
                    uploadForm.append('file', file);
                    uploadForm.append('api_key', sig.api_key);
                    uploadForm.append('timestamp', sig.timestamp);
                    uploadForm.append('signature', sig.signature);
                    uploadForm.append('folder', sig.folder);

                    const cloudRes = await fetch(sig.upload_url, {
                        method: 'POST',
                        body: uploadForm
                    });
                    return cloudRes.json();
                });

                const results = await Promise.all(uploadPromises);
                results.forEach(res => {
                    if (res.secure_url) {
                        uploadedUrls.push(res.secure_url);
                    } else {
                        console.error('Upload failed:', res);
                    }
                });
                
                hideUploadLoader();
            }

            // 1b. Upload Doctor Photos (if any pending)
            const pendingDoctorPhotos = Object.keys(doctorPhotos);
            if (pendingDoctorPhotos.length > 0) {
                showUploadLoader(`Uploading ${pendingDoctorPhotos.length} doctor photo${pendingDoctorPhotos.length > 1 ? 's' : ''}...`);
                
                const API_BASE = window.API_BASE_URL || '';
                const sigUrl = API_BASE ? API_BASE + '/api/cloudinary_signature.php' : 'api/cloudinary_signature.php';
                const sigRes = await fetch(sigUrl);
                if (!sigRes.ok) {
                    throw new Error('Signature request failed for doctor photos');
                }
                const sig = await sigRes.json();
                if (sig.error) throw new Error(sig.error || 'Signature error');

                // Upload each doctor photo
                for (const indexStr of pendingDoctorPhotos) {
                    const index = parseInt(indexStr);
                    const file = doctorPhotos[index];
                    
                    const uploadForm = new FormData();
                    uploadForm.append('file', file);
                    uploadForm.append('api_key', sig.api_key);
                    uploadForm.append('timestamp', sig.timestamp);
                    uploadForm.append('signature', sig.signature);
                    uploadForm.append('folder', sig.folder);

                    try {
                        const cloudRes = await fetch(sig.upload_url, {
                            method: 'POST',
                            body: uploadForm
                        });
                        const cloudData = await cloudRes.json();
                        
                        if (cloudData.secure_url) {
                            // Update doctor with uploaded URL
                            doctors[index].photoUrl = cloudData.secure_url;
                            delete doctors[index].photoPreview; // Remove preview
                        } else {
                            console.error('Doctor photo upload failed:', cloudData);
                        }
                    } catch (uploadErr) {
                        console.error('Error uploading doctor photo:', uploadErr);
                    }
                }
                
                // Clear the pending photos map
                Object.keys(doctorPhotos).forEach(key => delete doctorPhotos[key]);
                
                hideUploadLoader();
            }

            // 2. Populate Hidden Inputs
            document.getElementById('highlights_json').value = JSON.stringify(highlights);
            document.getElementById('reviews_json').value = JSON.stringify(reviews);
            document.getElementById('departments_json').value = JSON.stringify(departments);
            document.getElementById('specialties_json').value = JSON.stringify(specialties);
            document.getElementById('equipment_json').value = JSON.stringify(equipment);
            document.getElementById('doctors_json').value = JSON.stringify(doctors);
            document.getElementById('treatments_json').value = JSON.stringify(treatments);
            document.getElementById('packages_json').value = JSON.stringify(packages);
            document.getElementById('photos_json_input').value = JSON.stringify(uploadedUrls);

            // 3. Submit via API (JSON)
            const API_BASE = window.API_BASE_URL || '';
            const payload = {
                name: document.querySelector('input[name="name"]').value,
                type: document.querySelector('select[name="type"]').value,
                establishment_year: document.querySelector('input[name="establishment_year"]').value,
                beds: document.querySelector('input[name="beds"]').value,
                patient_count_total: document.querySelector('input[name="patient_count_total"]').value,
                patient_count_annual: document.querySelector('input[name="patient_count_annual"]').value,
                accreditations: Array.from(document.querySelectorAll('input[name="accreditations[]"]:checked')).map(i=>i.value),
                accreditations_other: document.querySelector('input[name="accreditations_other"]').value,
                address: document.querySelector('textarea[name="address"]').value,
                city: document.querySelector('input[name="city"]').value,
                state: document.querySelector('input[name="state"]').value,
                latitude: document.querySelector('input[name="latitude"]').value,
                longitude: document.querySelector('input[name="longitude"]').value,
                contact_general: document.querySelector('input[name="contact_general"]').value,
                contact_emergency: document.querySelector('input[name="contact_emergency"]').value,
                contact_email: document.querySelector('input[name="contact_email"]').value,
                contact_website: document.querySelector('input[name="contact_website"]').value,
                description_brief: document.querySelector('textarea[name="description_brief"]').value,
                description_detailed: document.querySelector('textarea[name="description_detailed"]').value,
                highlights: highlights,
                reviews: reviews,
                departments: departments,
                specialties: specialties,
                equipment: equipment,
                facilities: Array.from(document.querySelectorAll('input[name="facilities[]"]:checked')).map(i=>i.value),
                facilities_other: document.querySelector('input[name="facilities_other"]').value,
                doctors: doctors,
                treatments: treatments,
                packages: packages,
                photos: uploadedUrls
            };

            try {
                // Use relative path for local dev, full path for deployed API
                const submitUrl = API_BASE ? API_BASE + '/api/submit.php' : 'api/submit.php';
                const res = await fetch(submitUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const body = await res.json();
                if (res.ok && body.success) {
                    showToast('Hospital profile submitted successfully!', 'success');
                    setTimeout(() => { window.location.href = 'index.html?status=success'; }, 2000);
                } else {
                    console.error('Submit failed:', body);
                    showToast(body.error || 'Submission failed. Check console.', 'error');
                    submitBtn.disabled = false;
                    submitText.classList.remove('hidden');
                    submitLoading.classList.add('hidden');
                }
            } catch (err) {
                console.error('Submission error:', err);
                showToast('An error occurred during submission. Please try again.', 'error');
                submitBtn.disabled = false;
                submitText.classList.remove('hidden');
                submitLoading.classList.add('hidden');
            }

        } catch (err) {
            console.error(err);
            showToast('An error occurred during submission. Please try again.', 'error');
            submitBtn.disabled = false;
            submitText.classList.remove('hidden');
            submitLoading.classList.add('hidden');
        }
    });

    // ===== Success Toast =====
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('status') === 'success') {
        showToast('Hospital profile submitted successfully!', 'success');
        setTimeout(() => {
            window.location.href = 'index.html';
        }, 3000);
    }

    // ===== Helper Functions =====
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    function showUploadLoader(message = 'Uploading...') {
        const overlay = document.createElement('div');
        overlay.className = 'upload-loader-overlay';
        overlay.id = 'upload-loader-overlay';
        overlay.innerHTML = `
            <div class="upload-loader-content">
                <div class="upload-loader-spinner"></div>
                <div class="upload-loader-text">${message}</div>
                <div class="upload-loader-subtext">Please wait...</div>
            </div>
        `;
        document.body.appendChild(overlay);
    }

    function hideUploadLoader() {
        const overlay = document.getElementById('upload-loader-overlay');
        if (overlay) {
            overlay.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => overlay.remove(), 300);
        }
    }
});
