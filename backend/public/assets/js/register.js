const password = document.getElementById('password');
const confirm = document.getElementById('confirm');
const strengthFill = document.getElementById('strengthFill');
const submitBtn = document.getElementById('submitBtn');
const confirmMessage = document.getElementById('confirmMessage');

const requirements = {
    length: document.getElementById('req-length'),
    upper: document.getElementById('req-upper'),
    lower: document.getElementById('req-lower'),
    number: document.getElementById('req-number')
};

function updateRequirement(element, valid) {
    element.classList.toggle('valid', valid);
    element.classList.toggle('invalid', !valid);
    element.querySelector('i').className = valid ? 'fas fa-check' : 'fas fa-times';
}

function checkPasswordStrength(pwd) {
    const checks = {
        length: pwd.length >= 8,
        upper: /[A-Z]/.test(pwd),
        lower: /[a-z]/.test(pwd),
        number: /\d/.test(pwd)
    };

    Object.entries(checks).forEach(([key, passed]) => updateRequirement(requirements[key], passed));
    const score = Object.values(checks).filter(Boolean).length;
    const strengthClasses = ['', 'strength-weak', 'strength-fair', 'strength-good', 'strength-strong'];
    strengthFill.className = 'strength-fill ' + strengthClasses[score];

    return score === 4;
}

function checkPasswordMatch() {
    if (!confirm.value) {
        confirmMessage.textContent = '';
        return true;
    }

    const matched = password.value === confirm.value;
    confirmMessage.innerHTML = matched
        ? '<span style="color: #28a745;"><i class="fas fa-check"></i> Passwords match</span>'
        : '<span style="color: #dc3545;"><i class="fas fa-times"></i> Passwords do not match</span>';
    return matched;
}

password.addEventListener('input', () => {
    checkPasswordStrength(password.value);
    checkPasswordMatch();
});

confirm.addEventListener('input', checkPasswordMatch);

document.getElementById('registerForm').addEventListener('submit', function(e) {
    if (!checkPasswordStrength(password.value) || !checkPasswordMatch()) {
        e.preventDefault();
        alert('Please ensure all password requirements are met and passwords match.');
    }
});