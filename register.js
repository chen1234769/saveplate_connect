document.addEventListener('DOMContentLoaded', function() {
    // Form elements
    const form = document.getElementById('registrationForm');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const household_sizeSelect = document.getElementById('household_size');
    const registerBtn = document.getElementById('registerBtn');
    const privacyToggle = document.getElementById('privacyToggle');
    const termsSection = document.getElementById('termsSection');
    const termsContent = document.getElementById('termsContent');
    
    // Error elements
    const usernameError = document.getElementById('usernameError');
    const emailError = document.getElementById('emailError');
    const phoneError = document.getElementById('phoneError');
    const passwordError = document.getElementById('passwordError');
    const confirmPasswordError = document.getElementById('confirmPasswordError');
    const household_sizeError = document.getElementById('household_sizeError');
    
    // Password strength elements
    const passwordStrength = document.getElementById('passwordStrength');
    const strengthText = document.getElementById('strengthText');
    
    // Password toggle buttons
    const togglePassword = document.getElementById('togglePassword');
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    
    // Username availability check
    let usernameAvailable = false;
    let usernameCheckTimeout;
    
    // Toggle privacy preferences section
    privacyToggle.addEventListener('change', function() {
        if (this.checked) {
            termsSection.classList.add('expanded');
        } else {
            termsSection.classList.remove('expanded');
        }
    });
    
    // Also allow clicking on the header to expand
    document.querySelector('.terms-section h3').addEventListener('click', function() {
        privacyToggle.checked = !privacyToggle.checked;
        if (privacyToggle.checked) {
            termsSection.classList.add('expanded');
        } else {
            termsSection.classList.remove('expanded');
        }
    });
    
    // Password toggle functionality
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
    });
    
    toggleConfirmPassword.addEventListener('click', function() {
        const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        confirmPasswordInput.setAttribute('type', type);
        this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
    });
    
    // Real-time validation
    usernameInput.addEventListener('input', function() {
        clearTimeout(usernameCheckTimeout);
        const username = this.value.trim();
        
        if (username.length < 3) {
            showError(usernameError, 'Username must be at least 3 characters');
            usernameAvailable = false;
            return;
        }
        
        if (username.length > 20) {
            showError(usernameError, 'Username must be less than 20 characters');
            usernameAvailable = false;
            return;
        }
        
        if (!/^[a-zA-Z0-9_]+$/.test(username)) {
            showError(usernameError, 'Username can only contain letters, numbers, and underscores');
            usernameAvailable = false;
            return;
        }
        
        // Simulate checking username availability
        usernameCheckTimeout = setTimeout(() => {
            checkUsernameAvailability(username);
        }, 500);
    });
    
    emailInput.addEventListener('blur', validateEmail);
    phoneInput.addEventListener('blur', validatePhone);
    passwordInput.addEventListener('input', validatePassword);
    confirmPasswordInput.addEventListener('blur', validateConfirmPassword);
    household_sizeSelect.addEventListener('change', validateHouseholdSize);
    
    // Form submission
    form.addEventListener('submit', function(e) {
        const isUsernameValid = validateUsername();
        const isEmailValid = validateEmail();
        const isPhoneValid = validatePhone();
        const isPasswordValid = validatePassword();
        const isConfirmPasswordValid = validateConfirmPassword();
        const isHouseholdSizeValid = validateHouseholdSize();
        
        if (!isUsernameValid || !isEmailValid || !isPhoneValid || !isPasswordValid || !isConfirmPasswordValid || !isHouseholdSizeValid) {
            e.preventDefault();
            // Scroll to first error
            const firstError = document.querySelector('.error-message[style="display: flex;"]');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
    
    // Validation functions
    function validateUsername() {
        const username = usernameInput.value.trim();
        
        if (username === '') {
            showError(usernameError, 'Username is required');
            return false;
        }
        
        if (username.length < 3) {
            showError(usernameError, 'Username must be at least 3 characters');
            return false;
        }
        
        if (username.length > 20) {
            showError(usernameError, 'Username must be less than 20 characters');
            return false;
        }
        
        if (!/^[a-zA-Z0-9_]+$/.test(username)) {
            showError(usernameError, 'Username can only contain letters, numbers, and underscores');
            return false;
        }
        
        if (!usernameAvailable) {
            showError(usernameError, 'Username is not available. Please choose another one.');
            return false;
        }
        
        hideError(usernameError);
        return true;
    }
    
    function validateEmail() {
        const email = emailInput.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email === '') {
            showError(emailError, 'Email address is required');
            return false;
        }
        
        if (!emailRegex.test(email)) {
            showError(emailError, 'Please enter a valid email address');
            return false;
        }
        
        hideError(emailError);
        return true;
    }

    function validatePhone() {
        const phone = phoneInput.value.trim();
        // Basic phone validation - accepts numbers, spaces, hyphens, parentheses
        const phoneRegex = /^[\+]?[1-9][\d]{0,15}$|^[0-9]{10,15}$/;
        
        if (phone === '') {
            showError(phoneError, 'Phone number is required');
            return false;
        }
        
        // Remove any non-digit characters for validation
        const cleanPhone = phone.replace(/\D/g, '');
        
        if (cleanPhone.length < 10) {
            showError(phoneError, 'Phone number must be at least 10 digits');
            return false;
        }
        
        if (cleanPhone.length > 15) {
            showError(phoneError, 'Phone number is too long');
            return false;
        }
        
        hideError(phoneError);
        return true;
    }
    
    function validatePassword() {
        const password = passwordInput.value;
        
        if (password === '') {
            showError(passwordError, 'Password is required');
            updatePasswordStrength(0, 'None');
            return false;
        }
        
        if (password.length < 8) {
            showError(passwordError, 'Password must be at least 8 characters');
            updatePasswordStrength(1, 'Weak');
            return false;
        }
        
        // Check password strength
        let strength = 0;
        
        // Length check
        if (password.length >= 8) strength++;
        
        // Contains lowercase
        if (/[a-z]/.test(password)) strength++;
        
        // Contains uppercase
        if (/[A-Z]/.test(password)) strength++;
        
        // Contains numbers or special characters
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        
        // Determine strength level
        if (strength < 3) {
            updatePasswordStrength(strength, 'Weak');
            showError(passwordError, 'Password is too weak. Include uppercase, lowercase, and numbers');
            return false;
        } else if (strength === 3) {
            updatePasswordStrength(strength, 'Medium');
        } else {
            updatePasswordStrength(strength, 'Strong');
        }
        
        hideError(passwordError);
        return true;
    }
    
    function validateConfirmPassword() {
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        if (confirmPassword === '') {
            showError(confirmPasswordError, 'Please confirm your password');
            return false;
        }
        
        if (password !== confirmPassword) {
            showError(confirmPasswordError, 'Passwords do not match');
            return false;
        }
        
        hideError(confirmPasswordError);
        return true;
    }
    
    function validateHouseholdSize() {
        const household_size = household_sizeSelect.value;
        
        if (household_size === '') {
            showError(household_sizeError, 'Please select your household size');
            return false;
        }
        
        hideError(household_sizeError);
        return true;
    }
    
    function checkUsernameAvailability(username) {
        // In a real application, this would be an AJAX call to the server
        // For demo purposes, we'll simulate a check with a timeout
        
        // Simulate taken usernames
        const takenUsernames = ['admin', 'user', 'test', 'demo', 'saveplate'];
        
        if (takenUsernames.includes(username.toLowerCase())) {
            showError(usernameError, 'Username is not available. Please choose another one.');
            usernameAvailable = false;
        } else {
            hideError(usernameError);
            usernameAvailable = true;
        }
    }
    
    function updatePasswordStrength(strength, text) {
        passwordStrength.className = `password-strength strength-${strength}`;
        strengthText.textContent = text;
    }
    
    function showError(element, message) {
        element.querySelector('span').textContent = message;
        element.style.display = 'flex';
    }
    
    function hideError(element) {
        element.style.display = 'none';
    }
});