document.addEventListener('DOMContentLoaded', function() {
    // Modal Elements
    const modal = document.getElementById("rca-booking-modal");
    const closeBtn = document.querySelector(".rca-close");
    const modalBody = document.getElementById("rca-modal-body");
    
    // Open Modal Logic
    document.querySelectorAll('.rca-open-modal').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const vehicleId = this.getAttribute('data-vehicle-id');
            
            if (modal) {
                modal.style.display = "block";
                document.body.style.overflow = "hidden"; // Prevent background scroll
                
                // Load the booking form via AJAX
                if(typeof rca_obj !== 'undefined' && rca_obj.ajax_url) {
                    modalBody.innerHTML = '<div class="rca-loading">Loading...</div>';
                    
                    const formData = new FormData();
                    formData.append('action', 'rca_load_booking_form');
                    formData.append('vehicle_id', vehicleId);
                    
                    fetch(rca_obj.ajax_url, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(html => {
                        modalBody.innerHTML = html;
                        
                        // Set up date validation after form loads
                        const startDateInput = document.getElementById('rca_start_date');
                        const endDateInput = document.getElementById('rca_end_date');
                        
                        if (startDateInput && endDateInput) {
                            // Set minimum dates to today
                            const today = new Date().toISOString().split('T')[0];
                            startDateInput.setAttribute('min', today);
                            endDateInput.setAttribute('min', today);
                            
                            // Update end date minimum when start date changes
                            startDateInput.addEventListener('change', function() {
                                if (this.value) {
                                    endDateInput.setAttribute('min', this.value);
                                    // If end date is before start date, clear it
                                    if (endDateInput.value && endDateInput.value < this.value) {
                                        endDateInput.value = '';
                                    }
                                } else {
                                    endDateInput.setAttribute('min', today);
                                }
                            });
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        modalBody.innerHTML = '<p>Error loading form.</p>';
                    });
                }
            }
        });
    });

    // Close Modal Function
    function closeModal() {
        if (modal) {
            modal.style.display = "none";
            document.body.style.overflow = "auto";
        }
    }

    // Close Modal Logic
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    if (modal) {
        window.addEventListener('click', function(event) {
            // Close if clicked on the modal background overlay or the inner scroll wrapper
            if (event.target === modal || event.target.classList.contains('rca-modal-inner')) {
                closeModal();
            }
        });
    }

    // Handle form submission via AJAX
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form && form.classList.contains('rca-booking-form')) {
            e.preventDefault();
            
            // Remove previous validation highlights
            form.querySelectorAll('.rca-invalid-field').forEach(field => {
                field.classList.remove('rca-invalid-field');
            });
            form.querySelectorAll('.rca-form-group').forEach(group => {
                group.classList.remove('rca-has-error');
            });
            form.querySelectorAll('.rca-insurance-option').forEach(option => {
                option.classList.remove('rca-invalid-field');
            });
            form.querySelectorAll('.rca-initial-field').forEach(field => {
                field.classList.remove('rca-invalid-field');
            });
            form.querySelectorAll('.rca-checkbox-field').forEach(field => {
                field.classList.remove('rca-invalid-field');
            });
            
            // Show loading state first (will be reset if validation fails)
            const submitBtn = form.querySelector('.rca-submit-btn');
            const originalText = submitBtn ? submitBtn.textContent : 'Submit';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Submitting...';
            }
            
            let isValid = true;
            let firstInvalidField = null;
            
            // Validate text/email/tel/date/textarea fields (skip readonly/disabled fields)
            const requiredFields = form.querySelectorAll('input[required]:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]), textarea[required], select[required]');
            requiredFields.forEach(field => {
                // Skip readonly or disabled fields
                if (field.readOnly || field.disabled || field.hasAttribute('readonly') || field.hasAttribute('disabled')) {
                    return;
                }
                
                // Trigger validation by checking validity
                const isEmpty = !field.value || (typeof field.value === 'string' && field.value.trim() === '');
                let isInvalid = false;
                
                // For date fields, check if value is set
                if (field.type === 'date') {
                    isInvalid = isEmpty || !field.value;
                } else {
                    // Use native HTML5 validation
                    isInvalid = isEmpty || !field.checkValidity();
                }
                
                if (isInvalid) {
                    isValid = false;
                    field.classList.add('rca-invalid-field');
                    const formGroup = field.closest('.rca-form-group');
                    if (formGroup) {
                        formGroup.classList.add('rca-has-error');
                    }
                    if (!firstInvalidField) {
                        firstInvalidField = field;
                    }
                    console.log('Invalid field:', field.name, 'Value:', field.value, 'Empty:', isEmpty);
                }
            });
            
            // Check if this is a simplified form (lead capture)
            const isSimpleForm = form.querySelector('input[name="rca_is_simple_form"]') && form.querySelector('input[name="rca_is_simple_form"]').value === '1';
            
            // Validate radio groups (insurance option) - only for full form
            if (!isSimpleForm) {
                const insuranceRadios = form.querySelectorAll('input[name="rca_insurance_option"]');
                const insuranceChecked = Array.from(insuranceRadios).some(radio => radio.checked);
                if (!insuranceChecked) {
                    isValid = false;
                    insuranceRadios.forEach(radio => {
                        const optionDiv = radio.closest('.rca-insurance-option');
                        if (optionDiv) {
                            optionDiv.classList.add('rca-invalid-field');
                        }
                    });
                    if (!firstInvalidField) {
                        firstInvalidField = insuranceRadios[0]?.closest('.rca-insurance-option') || insuranceRadios[0];
                    }
                }
            }
            
            // Validate checkboxes (initials) - only for full form
            if (!isSimpleForm) {
                const requiredCheckboxes = form.querySelectorAll('input[type="checkbox"][required]');
                requiredCheckboxes.forEach(checkbox => {
                    if (!checkbox.checked) {
                        isValid = false;
                        checkbox.classList.add('rca-invalid-field');
                        
                        // Highlight the label
                        const label = checkbox.closest('label');
                        if (label) {
                            label.classList.add('rca-invalid-field');
                        }
                        
                        // Highlight the container (rca-initial-field or rca-checkbox-field)
                        const initialField = checkbox.closest('.rca-initial-field');
                        const checkboxField = checkbox.closest('.rca-checkbox-field');
                        if (initialField) {
                            initialField.classList.add('rca-invalid-field');
                        }
                        if (checkboxField) {
                            checkboxField.classList.add('rca-invalid-field');
                        }
                        
                        // Also highlight parent form-group if exists
                        const formGroup = checkbox.closest('.rca-form-group');
                        if (formGroup) {
                            formGroup.classList.add('rca-has-error');
                        }
                        
                        if (!firstInvalidField) {
                            firstInvalidField = checkbox;
                        }
                    }
                });
            }
            
            if (!isValid) {
                // Reset submit button
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
                
                // Scroll to first invalid field
                if (firstInvalidField) {
                    setTimeout(() => {
                        const scrollTarget = firstInvalidField.closest('.rca-initial-field') || 
                                           firstInvalidField.closest('.rca-insurance-option') || 
                                           firstInvalidField.closest('.rca-form-group') || 
                                           firstInvalidField;
                        scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        if (firstInvalidField.focus && firstInvalidField.type !== 'checkbox' && firstInvalidField.type !== 'radio') {
                            firstInvalidField.focus();
                        }
                    }, 100);
                }
                console.error('Form validation failed. Invalid fields:', form.querySelectorAll('.rca-invalid-field'));
                alert('Please fill in all required fields correctly. Invalid fields are highlighted in red.');
                return;
            }
            
            console.log('Form validation passed. Submitting...');
            
            const formData = new FormData(form);
            formData.append('action', 'rca_submit_booking_ajax');
            
            // Check if this is a completion form and add token from URL if needed
            const isCompletionForm = form.querySelector('input[name="rca_complete_booking"]') && form.querySelector('input[name="rca_complete_booking"]').value === '1';
            if (isCompletionForm) {
                // Get token from URL query string
                const urlParams = new URLSearchParams(window.location.search);
                const token = urlParams.get('token');
                if (token && !formData.has('token')) {
                    formData.append('token', token);
                }
            }
            
            if(typeof rca_obj !== 'undefined' && rca_obj.ajax_url) {
                fetch(rca_obj.ajax_url, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(response => {
                    // Check content type
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        // Get text first to see what we're getting
                        return response.text().then(text => {
                            console.error('Non-JSON response received:', text.substring(0, 500));
                            throw new Error('Server returned HTML instead of JSON. Check console for details.');
                        });
                    }
                    if (!response.ok) {
                        throw new Error('HTTP error! status: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('AJAX Response:', data); // Debug log
                    if (data && data.success) {
                        // Check if this is a completion form (has redirect_url)
                        if (isCompletionForm) {
                            // For completion forms, always redirect
                            let redirectUrl = null;
                            
                            if (data.data && data.data.redirect_url) {
                                redirectUrl = data.data.redirect_url;
                            } else {
                                // Fallback: construct redirect URL from current page
                                const urlParams = new URLSearchParams(window.location.search);
                                const token = urlParams.get('token');
                                if (token) {
                                    const currentUrl = new URL(window.location.href);
                                    currentUrl.searchParams.set('completed', '1');
                                    redirectUrl = currentUrl.toString();
                                }
                            }
                            
                            if (redirectUrl) {
                                console.log('Redirecting to:', redirectUrl); // Debug log
                                // Use replace to prevent back button issues
                                window.location.replace(redirectUrl);
                            } else {
                                // Fallback: reload with completed parameter
                                const urlParams = new URLSearchParams(window.location.search);
                                urlParams.set('completed', '1');
                                window.location.replace(window.location.pathname + '?' + urlParams.toString());
                            }
                        } else {
                            // Show success message for modal forms
                            if (modalBody) {
                                modalBody.innerHTML = '<div class="rca-alert rca-alert-success" style="padding: 2rem; text-align: center;"><h3 style="color: #10b981; margin-bottom: 1rem;">Booking Request Submitted Successfully!</h3><p style="color: #cbd5e1; line-height: 1.6;">Your booking request has been received. We will contact you shortly to confirm your rental agreement.</p><button type="button" class="rca-btn" style="margin-top: 1.5rem; max-width: 200px; width: auto; padding: 0.875rem 2rem;" onclick="document.getElementById(\'rca-booking-modal\').style.display=\'none\'; document.body.style.overflow=\'auto\';">Close</button></div>';
                            } else {
                                // If not in modal, show alert and reload
                                alert('Booking completed successfully!');
                                window.location.reload();
                            }
                        }
                    } else {
                        const errorMsg = (data && data.data && data.data.message) ? data.data.message : (data && data.message) ? data.message : 'Failed to submit booking. Please try again.';
                        alert('Error: ' + errorMsg);
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                        }
                    }
                })
                .catch(err => {
                    console.error('Booking submission error:', err);
                    // Check if the form was actually submitted (booking might be completed)
                    if (isCompletionForm) {
                        // For completion forms, if there's an error but booking might be done, try redirecting
                        const urlParams = new URLSearchParams(window.location.search);
                        const token = urlParams.get('token');
                        if (token) {
                            urlParams.set('completed', '1');
                            console.log('Error occurred, but trying redirect anyway:', window.location.pathname + '?' + urlParams.toString());
                            window.location.replace(window.location.pathname + '?' + urlParams.toString());
                            return;
                        }
                    }
                    alert('Error: Failed to submit booking. Please try again. ' + (err.message || ''));
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }
                });
            } else {
                alert('Error: AJAX configuration not found. Please refresh the page.');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            }
        }
    });
});
