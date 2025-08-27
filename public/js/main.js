// --- Modal Handling ---
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// --- AJAX Form Submission ---
async function handleFormSubmit(form) {
    try {
        const formData = new FormData(form);

        // The form.action is now a full URL thanks to the site_url() helper.
        // No need to reconstruct it in JavaScript.
        const response = await fetch(form.action, {
            method: form.method,
            body: formData
        });

        if (!response.ok) {
            throw new Error('Network response was not ok.');
        }

        const result = await response.json();

        if (result.status === 'success') {
            if (result.redirect) {
                window.location.href = result.redirect;
            } else {
                // Or maybe show a success message in the modal
                alert(result.message || 'Success!');
                location.reload(); // Simple way to reflect changes
            }
        } else {
            // Display error message
            const errorElement = form.querySelector('.form-error');
            if (errorElement) {
                errorElement.textContent = result.message || 'An unknown error occurred.';
                errorElement.style.display = 'block';
            } else {
                alert(result.message || 'An unknown error occurred.');
            }
        }
    } catch (error) {
        console.error('Form submission error:', error);
        alert('A network error occurred. Please try again.');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // General setup can go here, like attaching listeners to modal-trigger buttons
});
