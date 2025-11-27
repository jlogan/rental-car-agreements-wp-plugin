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
});
