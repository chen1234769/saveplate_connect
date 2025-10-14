document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('forgotPasswordForm');
    const emailInput = document.getElementById('email');
    const emailError = document.getElementById('emailError');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.querySelector('#submitBtn .btn-text');
    const spinner = document.getElementById('spinner');
    const messageDiv = document.getElementById('message');

    let isSubmitting = false;

    // Email validation
    function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    // Real-time email validation
    emailInput.addEventListener('input', function() {
        const email = emailInput.value.trim();
        const isValid = email === '' || validateEmail(email);
        
        emailError.textContent = isValid ? '' : 'Please enter a valid email address';
        emailInput.style.borderColor = isValid ? 'rgba(255, 255, 255, 0.5)' : '#ff6b6b';
    });

    // Email form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (isSubmitting) return;

        const email = emailInput.value.trim();
        
        if (!validateEmail(email)) {
            emailError.textContent = 'Please enter a valid email address';
            emailInput.style.borderColor = '#ff6b6b';
            emailInput.focus();
            return;
        }

        isSubmitting = true;
        setLoadingState(true);

        try {
            const formData = new FormData();
            formData.append('email', email);

            const response = await fetch('forget_pass2.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

            const result = await response.json();

            if (result.success) {
                showMessage('Verification code sent! Redirecting...', 'success');
                
                // Redirect to OTP page after 2 seconds
                setTimeout(() => {
                    window.location.href = 'grab_otp.html?email=' + encodeURIComponent(email);
                }, 2000);
            } else {
                showMessage(result.message, 'error');
            }
        } catch (error) {
            console.error('Fetch Error:', error);
            showMessage('An error occurred. Please try again.', 'error');
        } finally {
            isSubmitting = false;
            setLoadingState(false);
        }
    });

    // Loading state for email form
    function setLoadingState(isLoading) {
        submitBtn.disabled = isLoading;
        btnText.textContent = isLoading ? 'Sending...' : 'Send OTP';
        spinner.classList.toggle('hidden', !isLoading);
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

    // Clear message on input
    emailInput.addEventListener('input', function() {
        messageDiv.classList.add('hidden');
    });
});