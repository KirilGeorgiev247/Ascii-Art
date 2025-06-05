document.getElementById('loginForm').addEventListener('submit', function (e) {
    if (!validateForm(this)) {
        e.preventDefault();
        showAlert('Please fill in all required fields', 'warning');
    }
});

document.querySelectorAll('.form-control').forEach(input => {
    input.addEventListener('focus', function () {
        this.style.borderColor = 'var(--primary-color)';
        this.style.boxShadow = '0 0 0 3px rgba(52, 152, 219, 0.1)';
    });

    input.addEventListener('blur', function () {
        this.style.borderColor = 'var(--border-color)';
        this.style.boxShadow = 'none';
    });
});

function validateForm(form) {
    return [...form.elements].every(el => !el.required || el.value.trim() !== '');
}

function showAlert(message, type) {
    alert(message); // TODO: nicer message display
}