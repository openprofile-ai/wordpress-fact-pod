document.addEventListener('DOMContentLoaded', () => {
    const inputFields = document.querySelectorAll('.woocommerce-form .input-text');

    inputFields.forEach(input => {
        const parentRow = input.closest('.form-row');

        if (input.value) {
            parentRow.classList.add('has-value');
        }

        input.addEventListener('focus', () => {
            parentRow.classList.add('is-focused');
        });

        input.addEventListener('blur', () => {
            parentRow.classList.remove('is-focused');
            if (input.value) {
                parentRow.classList.add('has-value');
            } else {
                parentRow.classList.remove('has-value');
            }
        });

        input.addEventListener('input', () => {
            if (input.value) {
                parentRow.classList.add('has-value');
            } else {
                parentRow.classList.remove('has-value');
            }
        });
    });
});
