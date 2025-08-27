document.addEventListener('DOMContentLoaded', function() {
    const layout = document.querySelector('.admin-layout');
    const toggleButton = document.getElementById('sidebar-toggle');

    if (toggleButton && layout) {
        toggleButton.addEventListener('click', function() {
            layout.classList.toggle('sidebar-toggled');
        });
    }
});
