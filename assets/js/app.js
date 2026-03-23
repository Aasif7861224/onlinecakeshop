(function () {
    function getToastContainer() {
        return document.getElementById('appToastContainer');
    }

    function showToast(message, variant) {
        var container = getToastContainer();
        if (!container || !window.bootstrap) {
            return;
        }

        var toast = document.createElement('div');
        toast.className = 'toast align-items-center text-bg-' + (variant || 'dark') + ' border-0';
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        toast.innerHTML = '<div class="d-flex"><div class="toast-body">' + message + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>';
        container.appendChild(toast);

        var toastInstance = new bootstrap.Toast(toast, { delay: 2600 });
        toastInstance.show();
        toast.addEventListener('hidden.bs.toast', function () {
            toast.remove();
        });
    }

    function formatCurrency(amount) {
        var value = Number(amount || 0);
        return 'Rs. ' + value.toFixed(2);
    }

    function updateCartCount(count) {
        document.querySelectorAll('[data-cart-count]').forEach(function (element) {
            element.textContent = count;
        });
        document.querySelectorAll('[data-cart-count-display]').forEach(function (element) {
            element.textContent = count;
        });
    }

    function updateCartSubtotal(subtotal) {
        document.querySelectorAll('[data-cart-subtotal]').forEach(function (element) {
            element.textContent = formatCurrency(subtotal);
        });
    }

    function sendCartRequest(formData) {
        return fetch((window.CAKE_SHOP_API_URL || '/onlinecakeshop') + '/cart_api.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json();
        });
    }

    document.addEventListener('click', function (event) {
        if (event.target.matches('[data-confirm]')) {
            var message = event.target.getAttribute('data-confirm') || 'Are you sure?';
            if (!window.confirm(message)) {
                event.preventDefault();
                return;
            }
        }

        if (event.target.matches('[data-cart-remove]')) {
            event.preventDefault();

            var button = event.target;
            var form = document.getElementById('cartUpdateForm');
            if (!form) {
                return;
            }

            var formData = new FormData();
            formData.append('_token', form.querySelector('input[name="_token"]').value);
            formData.append('cart_action', 'remove');
            formData.append('product_id', button.getAttribute('data-product-id'));

            sendCartRequest(formData).then(function (data) {
                if (!data.success) {
                    showToast(data.message || 'Unable to remove item.', 'danger');
                    return;
                }

                var row = button.closest('[data-cart-row]');
                if (row) {
                    row.remove();
                }
                updateCartCount(data.count);
                updateCartSubtotal(data.subtotal);
                showToast('Item removed from cart.', 'dark');

                if (!data.items_remaining) {
                    window.location.reload();
                }
            });
        }
    });

    document.addEventListener('submit', function (event) {
        var addForm = event.target.closest('[data-ajax-cart-form="add"]');
        if (addForm) {
            event.preventDefault();
            var formData = new FormData(addForm);
            formData.append('cart_action', 'add');

            sendCartRequest(formData).then(function (data) {
                if (!data.success) {
                    showToast(data.message || 'Unable to add product.', 'danger');
                    return;
                }

                updateCartCount(data.count);
                showToast('Product added to cart.', 'success');
            });
            return;
        }

        var clearForm = event.target.closest('[data-ajax-cart-clear]');
        if (clearForm) {
            event.preventDefault();
            var clearData = new FormData(clearForm);
            clearData.append('cart_action', 'clear');
            sendCartRequest(clearData).then(function (data) {
                if (!data.success) {
                    showToast(data.message || 'Unable to clear cart.', 'danger');
                    return;
                }

                updateCartCount(data.count);
                updateCartSubtotal(data.subtotal);
                showToast('Cart cleared.', 'dark');
                window.location.reload();
            });
        }
    });

    document.addEventListener('change', function (event) {
        if (!event.target.matches('[data-cart-quantity]')) {
            return;
        }

        var input = event.target;
        var form = document.getElementById('cartUpdateForm');
        if (!form) {
            return;
        }

        var formData = new FormData();
        formData.append('_token', form.querySelector('input[name="_token"]').value);
        formData.append('cart_action', 'update');
        formData.append('product_id', input.getAttribute('data-product-id'));
        formData.append('quantity', input.value);

        sendCartRequest(formData).then(function (data) {
            if (!data.success) {
                showToast(data.message || 'Unable to update cart.', 'danger');
                return;
            }

            var row = input.closest('[data-cart-row]');
            if (row && data.line_total !== null) {
                var lineTotal = row.querySelector('[data-line-total]');
                if (lineTotal) {
                    lineTotal.textContent = formatCurrency(data.line_total);
                }
            }

            updateCartCount(data.count);
            updateCartSubtotal(data.subtotal);
            showToast('Cart updated.', 'success');
        });
    });

    window.showToast = showToast;
}());
