document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('signinForm');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const signinBtn = document.getElementById('signinBtn');
    const togglePassword = document.getElementById('togglePassword');
    const usernameError = document.getElementById('usernameError');
    const passwordError = document.getElementById('passwordError');
    const formError = document.getElementById('formError');
    const successMessage = document.getElementById('successMessage');

    // Password toggle functionality
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
    });

    // Real-time validation
    usernameInput.addEventListener('blur', validateUsername);
    passwordInput.addEventListener('blur', validatePassword);

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const isUsernameValid = validateUsername();
        const isPasswordValid = validatePassword();
        
        if (isUsernameValid && isPasswordValid) {
            // Show loading state
            signinBtn.disabled = true;
            signinBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
            
            // Submit the form
            form.submit();
        } else {
            // Scroll to first error
            const firstError = document.querySelector('.error-message[style="display: flex;"]');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });

    function validateUsername() {
        const username = usernameInput.value.trim();
        
        if (username === '') {
            showError(usernameError, 'Please enter your username or email');
            return false;
        }
        
        hideError(usernameError);
        return true;
    }

    function validatePassword() {
        const password = passwordInput.value;
        
        if (password === '') {
            showError(passwordError, 'Please enter your password');
            return false;
        }
        
        hideError(passwordError);
        return true;
    }

    function showError(element, message) {
        element.querySelector('span').textContent = message;
        element.style.display = 'flex';
        hideError(formError);
    }

    function hideError(element) {
        element.style.display = 'none';
    }

    function showSuccess() {
        successMessage.style.display = 'flex';
        formError.style.display = 'none';
    }

    // Check for error messages from PHP
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    if (error) {
        showError(formError, decodeURIComponent(error));
    }

    // Check for success messages
    const success = urlParams.get('success');
    if (success) {
        showSuccess();
        setTimeout(() => {
            window.location.href = 'dashboard.html';
        }, 2000);
    }
});