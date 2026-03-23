document.addEventListener('click', function (event) {
    if (event.target.matches('[data-confirm]')) {
        var message = event.target.getAttribute('data-confirm') || 'Are you sure?';
        if (!window.confirm(message)) {
            event.preventDefault();
        }
    }
});
