document.addEventListener('DOMContentLoaded', function() {
    const resetForm = document.getElementById('resetPasswordForm');
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const passwordError = document.getElementById('passwordError');
    const resetBtn = document.getElementById('resetBtn');
    const resetBtnText = document.querySelector('#resetBtn .btn-text');
    const resetSpinner = document.getElementById('resetSpinner');
    const messageDiv = document.getElementById('message');
    const toggleNewPassword = document.getElementById('toggleNewPassword');
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const strengthBar = document.getElementById('passwordStrength');

    let isResetting = false;
    let currentEmail = getEmailFromURL();

    // Get email from URL parameters
    function getEmailFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('email');
    }

    // Check if user is verified
    function checkVerification() {
        const urlParams = new URLSearchParams(window.location.search);
        const verified = urlParams.get('verified');
        
        if (verified !== 'true' || !currentEmail) {
            showMessage('Please verify your email first.', 'error');
            setTimeout(() => {
                window.location.href = 'forget_pass.html';
            }, 3000);
            return false;
        }
        return true;
    }

    // Password validation
    function validatePassword(password) {
        return password.length >= 6;
    }

    // Check password strength
    function checkPasswordStrength(password) {
        let strength = 0;
        
        if (password.length >= 6) strength += 1;
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 1;
        if (password.match(/\d/)) strength += 1;
        if (password.match(/[^a-zA-Z\d]/)) strength += 1;
        
        strengthBar.className = 'strength-bar';
        if (strength === 1) strengthBar.classList.add('strength-weak');
        else if (strength === 2) strengthBar.classList.add('strength-medium');
        else if (strength >= 3) strengthBar.classList.add('strength-strong');
    }

    // Toggle password visibility
    function setupPasswordToggle() {
        toggleNewPassword.addEventListener('click', function() {
            const type = newPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            newPasswordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }

    // Real-time password validation
    newPasswordInput.addEventListener('input', function() {
        const password = this.value;
        checkPasswordStrength(password);
        
        if (password && !validatePassword(password)) {
            passwordError.textContent = 'Password must be at least 6 characters long';
        } else {
            passwordError.textContent = '';
        }
        
        // Check if passwords match
        const confirmPassword = confirmPasswordInput.value;
        if (confirmPassword && password !== confirmPassword) {
            passwordError.textContent = 'Passwords do not match';
        }
    });

    confirmPasswordInput.addEventListener('input', function() {
        const password = newPasswordInput.value;
        const confirmPassword = this.value;
        
        if (confirmPassword && password !== confirmPassword) {
            passwordError.textContent = 'Passwords do not match';
            confirmPasswordInput.style.borderColor = '#ff6b6b';
        } else {
            passwordError.textContent = '';
            confirmPasswordInput.style.borderColor = 'rgba(255, 255, 255, 0.5)';
        }
    });

    // Reset password form submission
    resetForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (isResetting) return;

        const newPassword = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        // Validation
        if (!validatePassword(newPassword)) {
            passwordError.textContent = 'Password must be at least 6 characters long';
            newPasswordInput.style.borderColor = '#ff6b6b';
            newPasswordInput.focus();
            return;
        }

        if (newPassword !== confirmPassword) {
            passwordError.textContent = 'Passwords do not match';
            confirmPasswordInput.style.borderColor = '#ff6b6b';
            confirmPasswordInput.focus();
            return;
        }

        if (!checkVerification()) {
            return;
        }

        isResetting = true;
        setResetLoadingState(true);

        try {
            const formData = new FormData();
            formData.append('email', currentEmail);
            formData.append('newPassword', newPassword);

            const response = await fetch('reset_password.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

            const result = await response.json();

            if (result.success) {
                showMessage('Password reset successfully! Redirecting to login...', 'success');
                resetBtnText.textContent = 'Password Reset Successfully';
                resetBtn.style.background = 'rgba(46, 204, 113, 0.8)';
                resetBtn.disabled = true;
                
                // Redirect to login page after 3 seconds
                setTimeout(() => {
                    window.location.href = 'signin.html';
                }, 3000);
            } else {
                showMessage(result.message, 'error');
            }
        } catch (error) {
            console.error('Fetch Error:', error);
            showMessage('An error occurred. Please try again.', 'error');
        } finally {
            isResetting = false;
            setResetLoadingState(false);
        }
    });

    // Loading state for reset form
    function setResetLoadingState(isLoading) {
        resetBtn.disabled = isLoading;
        resetBtnText.textContent = isLoading ? 'Resetting...' : 'Reset Password';
        resetSpinner.classList.toggle('hidden', !isLoading);
    }

    // Show message
    function showMessage(message, type) {
        messageDiv.textContent = message;
        messageDiv.className = `message ${type}`;
        messageDiv.classList.remove('hidden');
        
        if (type === 'success') {
            setTimeout(() => messageDiv.classList.add('hidden'), 5000);
        }
    }

    // Initialize page
    function initializePage() {
        if (!checkVerification()) {
            return;
        }
        setupPasswordToggle();
    }

    // Initialize page when loaded
    initializePage();
});