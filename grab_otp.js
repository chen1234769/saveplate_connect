document.addEventListener('DOMContentLoaded', function() {
    const otpForm = document.getElementById('otpForm');
    const otpInput = document.getElementById('otp');
    const otpError = document.getElementById('otpError');
    const verifyBtn = document.getElementById('verifyBtn');
    const verifyBtnText = document.querySelector('#verifyBtn .btn-text');
    const verifySpinner = document.getElementById('verifySpinner');
    const messageDiv = document.getElementById('message');
    const resendOtpLink = document.getElementById('resendOtp');
    const userEmailSpan = document.getElementById('userEmail');

    let isVerifying = false;
    let currentEmail = getEmailFromURL();
    let timerInterval;

    // Get email from URL parameters
    function getEmailFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('email');
    }

    // Setup OTP input handling
    function setupOtpInput() {
        const otpDigits = document.querySelectorAll('.otp-digit');
        
        otpDigits.forEach((digit, index) => {
            digit.addEventListener('input', (e) => {
                const value = e.target.value;
                
                if (value.length === 1 && /[0-9]/.test(value)) {
                    e.target.classList.add('filled');
                    
                    // Auto-focus next input
                    if (index < otpDigits.length - 1) {
                        otpDigits[index + 1].focus();
                    }
                } else {
                    e.target.classList.remove('filled');
                }
                
                updateOtpValue();
            });
            
            digit.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    otpDigits[index - 1].focus();
                }
            });
        });
        
        function updateOtpValue() {
            const otpValue = Array.from(document.querySelectorAll('.otp-digit'))
                .map(digit => digit.value).join('');
            otpInput.value = otpValue;
        }
    }

    // Timer function
    function startTimer(duration, display) {
        let timer = duration, minutes, seconds;
        clearInterval(timerInterval);
        
        timerInterval = setInterval(function () {
            minutes = parseInt(timer / 60, 10);
            seconds = parseInt(timer % 60, 10);

            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            display.textContent = minutes + ":" + seconds;

            if (--timer < 0) {
                clearInterval(timerInterval);
                showMessage('OTP has expired. Please request a new one.', 'error');
            }
        }, 1000);
    }

    // Initialize page
    function initializePage() {
        if (currentEmail) {
            userEmailSpan.textContent = currentEmail;
        } else {
            showMessage('Email not found. Please start over.', 'error');
        }
        
        // Start 5-minute timer
        const fiveMinutes = 60 * 5;
        const display = document.querySelector('#timer');
        startTimer(fiveMinutes, display);
        
        // Setup OTP inputs
        setupOtpInput();
    }

    // OTP form submission
    otpForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (isVerifying) return;

        const otp = otpInput.value;

        if (otp.length !== 6) {
            otpError.textContent = 'Please enter a valid 6-digit OTP';
            return;
        }

        if (!currentEmail) {
            showMessage('Email not found. Please start over.', 'error');
            return;
        }

        isVerifying = true;
        setVerifyLoadingState(true);

        try {
            const formData = new FormData();
            formData.append('email', currentEmail);
            formData.append('otp', otp);

            const response = await fetch('grab_otp.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

            const result = await response.json();

            if (result.success) {
                showMessage('Verification successful! Redirecting...', 'success');
                verifyBtnText.textContent = 'Verified';
                verifyBtn.style.background = 'rgba(46, 204, 113, 0.8)';
                verifyBtn.disabled = true;
                
                // Redirect to reset password page after 2 seconds
                setTimeout(() => {
                    window.location.href = 'reset_password.html?email=' + encodeURIComponent(currentEmail) + '&verified=true';
                }, 2000);
            } else {
                showMessage(result.message, 'error');
            }
        } catch (error) {
            console.error('Fetch Error:', error);
            showMessage('An error occurred. Please try again.', 'error');
        } finally {
            isVerifying = false;
            setVerifyLoadingState(false);
        }
    });

    // Resend OTP
    resendOtpLink.addEventListener('click', function(e) {
        e.preventDefault();
        
        if (!currentEmail) {
            showMessage('Email not found. Please start over.', 'error');
            return;
        }

        // Redirect back to email page to resend OTP
        window.location.href = 'forget_pass2.html';
    });

    // Loading state for OTP form
    function setVerifyLoadingState(isLoading) {
        verifyBtn.disabled = isLoading;
        verifyBtnText.textContent = isLoading ? 'Verifying...' : 'Verify Code';
        verifySpinner.classList.toggle('hidden', !isLoading);
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

    // Initialize page when loaded
    initializePage();
});