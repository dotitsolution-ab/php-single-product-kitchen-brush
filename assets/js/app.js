document.addEventListener('submit', function (event) {
    var form = event.target;
    if (form && form.matches('[data-confirm]')) {
        var message = form.getAttribute('data-confirm') || 'Are you sure?';
        if (!window.confirm(message)) {
            event.preventDefault();
        }
    }
});

function normalizeBdPhone(value) {
    var digits = String(value || '').replace(/\D+/g, '');

    if (digits.indexOf('00880') === 0) {
        digits = '0' + digits.slice(5);
    } else if (digits.indexOf('880') === 0) {
        digits = '0' + digits.slice(3);
    } else if (digits.indexOf('88') === 0) {
        digits = digits.slice(2);
    } else if (digits.indexOf('1') === 0) {
        digits = '0' + digits;
    }

    return digits.slice(0, 11);
}

document.addEventListener('input', function (event) {
    var input = event.target;
    if (!input || !input.matches('[data-phone-input]')) {
        return;
    }

    var normalized = normalizeBdPhone(input.value);
    if (input.value !== normalized) {
        input.value = normalized;
    }
});

document.addEventListener('paste', function (event) {
    var input = event.target;
    if (!input || !input.matches('[data-phone-input]')) {
        return;
    }

    event.preventDefault();
    var text = (event.clipboardData || window.clipboardData).getData('text');
    input.value = normalizeBdPhone(text);
});
