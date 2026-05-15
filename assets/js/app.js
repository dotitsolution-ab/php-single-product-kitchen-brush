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

function formatTaka(amount) {
    return new Intl.NumberFormat('en-US', {
        maximumFractionDigits: 0
    }).format(Number(amount || 0)) + ' টাকা';
}

function updateOrderSummary(form) {
    if (!form || !form.matches('[data-order-form]')) {
        return;
    }

    var unitPrice = Number(form.getAttribute('data-unit-price') || 0);
    var insideCharge = Number(form.getAttribute('data-inside-charge') || 0);
    var outsideCharge = Number(form.getAttribute('data-outside-charge') || 0);
    var quantityInput = form.querySelector('input[name="quantity"]:checked');
    var deliveryInput = form.querySelector('input[name="delivery_area"]:checked');
    var quantity = quantityInput ? Number(quantityInput.value || 1) : 1;
    var deliveryCharge = deliveryInput && deliveryInput.value === 'outside_dhaka' ? outsideCharge : insideCharge;
    var subtotal = unitPrice * quantity;
    var root = form.closest('.funnel-order') || document;

    var qty = root.querySelector('[data-summary-qty]');
    var subtotalNode = root.querySelector('[data-summary-subtotal]');
    var deliveryNode = root.querySelector('[data-summary-delivery]');
    var totalNode = root.querySelector('[data-summary-total]');

    if (qty) {
        qty.textContent = quantity;
    }
    if (subtotalNode) {
        subtotalNode.textContent = formatTaka(subtotal);
    }
    if (deliveryNode) {
        deliveryNode.textContent = formatTaka(deliveryCharge);
    }
    if (totalNode) {
        totalNode.textContent = formatTaka(subtotal + deliveryCharge);
    }
}

document.addEventListener('change', function (event) {
    var input = event.target;
    if (!input || !input.matches('[data-order-form] input[type="radio"]')) {
        return;
    }

    updateOrderSummary(input.closest('[data-order-form]'));
});

document.querySelectorAll('[data-order-form]').forEach(function (form) {
    updateOrderSummary(form);
});

document.addEventListener('change', function (event) {
    var select = event.target;
    if (!select || !select.value) {
        return;
    }

    var target = null;
    if (select.matches('[data-media-select]')) {
        target = document.getElementById(select.getAttribute('data-media-select'));
    } else if (select.matches('[data-media-select-field]')) {
        var field = select.closest('.media-field');
        target = field ? field.querySelector('[data-media-input]') : null;
    } else {
        return;
    }

    if (target) {
        target.value = select.value;
        target.dispatchEvent(new Event('input', { bubbles: true }));
    }
    select.value = '';
});

document.addEventListener('click', function (event) {
    var addButton = event.target.closest('[data-add-row]');
    if (!addButton) {
        return;
    }

    var template = document.getElementById(addButton.getAttribute('data-add-row'));
    var target = document.querySelector(addButton.getAttribute('data-row-target'));
    if (!template || !target) {
        return;
    }

    target.insertAdjacentHTML('beforeend', template.innerHTML.trim());
});

document.addEventListener('click', function (event) {
    var removeButton = event.target.closest('[data-remove-row]');
    if (!removeButton) {
        return;
    }

    var row = removeButton.closest('[data-repeatable-row]');
    if (row) {
        row.remove();
    }
});

document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-copy-button]');
    if (!button) {
        return;
    }

    var card = button.closest('.media-card');
    var input = card ? card.querySelector('[data-copy-value]') : null;
    if (!input) {
        return;
    }

    input.select();
    input.setSelectionRange(0, input.value.length);
    navigator.clipboard && navigator.clipboard.writeText
        ? navigator.clipboard.writeText(input.value)
        : document.execCommand('copy');

    button.textContent = 'Copied';
    window.setTimeout(function () {
        button.textContent = 'Copy Path';
    }, 1200);
});
