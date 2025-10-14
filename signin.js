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

    // Form submission handling
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission
        
        // Clear any existing errors
        hideError(formError);
        hideError(usernameError);
        hideError(passwordError);
        
        // Validate inputs
        const isUsernameValid = validateUsername();
        const isPasswordValid = validatePassword();
        
        if (isUsernameValid && isPasswordValid) {
            // Show loading state
            signinBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
            signinBtn.disabled = true;
            
            // Submit form
            this.submit();
        }
    });

    // Real-time validation that shows errors when user enters invalid data
    usernameInput.addEventListener('input', function() {
        if (this.value.trim().length > 0) {
            validateUsername();
        } else {
            // Show error if field is empty
            showError(usernameError, 'Please enter your valid username or email');
        }
    });
    
    passwordInput.addEventListener('input', function() {
        if (this.value.length > 0) {
            validatePassword();
        } else {
            // Show error if field is empty
            showError(passwordError, 'Please enter your valid password');
        }
    });

    // Add blur event listeners for validation on field exit
    usernameInput.addEventListener('blur', function() {
        if (this.value.trim() === '') {
            showError(usernameError, 'Please enter your valid username or email');
        } else {
            hideError(usernameError);
        }
    });
    
    passwordInput.addEventListener('blur', function() {
        if (this.value === '') {
            showError(passwordError, 'Please enter your valid password');
        } else {
            hideError(passwordError);
        }
    });

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show validation for empty fields when user clicks Sign In
        let hasErrors = false;
        
        if (usernameInput.value.trim() === '') {
            showError(usernameError, 'Please enter your valid username or email');
            hasErrors = true;
        }
        
        if (passwordInput.value === '') {
            showError(passwordError, 'Please enter your valid password');
            hasErrors = true;
        }
        
        // If there are empty fields, don't submit
        if (hasErrors) {
            return;
        }
        
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
            showError(usernameError, 'Please enter your valid username or email');
            return false;
        }
        
        // Hide error if validation passes
        hideError(usernameError);
        return true;
    }

    function validatePassword() {
        const password = passwordInput.value;
        
        if (password === '') {
            showError(passwordError, 'Please enter your valid password');
            return false;
        }
        
        // Hide error if validation passes
        hideError(passwordError);
        return true;
    }

    function showError(element, message) {
        element.querySelector('span').textContent = message;
        element.style.display = 'flex';
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
    console.log('URL params:', window.location.search);
    console.log('Error param:', error);
    
    if (error) {
        console.log('Showing error:', decodeURIComponent(error));
        showError(formError, decodeURIComponent(error));
    }
    
    // Also check for common authentication error messages
    const commonErrors = [
        'Invalid username or password',
        'Please verify your email before signing in',
        'User not found',
        'Authentication failed'
    ];
    
    // Check if any common error messages are in the URL
    commonErrors.forEach(errorMsg => {
        if (window.location.href.includes(encodeURIComponent(errorMsg))) {
            showError(formError, errorMsg);
        }
    });

    // Check for success messages
    const success = urlParams.get('success');
    if (success) {
        showSuccess();
        setTimeout(() => {
            window.location.href = 'dashboard.php';
        }, 2000);
    }
    
    // Test function to manually show error (for debugging)
    window.testError = function() {
        showError(formError, 'Test error message');
    };
    
    // Test function to check if elements exist
    console.log('Form elements found:');
    console.log('form:', form);
    console.log('formError:', formError);
    console.log('usernameError:', usernameError);
    console.log('passwordError:', passwordError);
});
