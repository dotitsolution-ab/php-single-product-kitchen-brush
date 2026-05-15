document.addEventListener('submit', function (event) {
    var form = event.target;
    if (form && form.matches('[data-confirm]')) {
        var message = form.getAttribute('data-confirm') || 'Are you sure?';
        if (!window.confirm(message)) {
            event.preventDefault();
        }
    }
});

