// Form submission
document.getElementById('verificationForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    let enteredOtp = '';
    otpInputs.forEach(input => enteredOtp += input.value);
    
    if (enteredOtp.length !== 6) {
        showError('Please enter a complete 6-digit code');
        return;
    }
    
    try {
        verifyBtn.disabled = true;
        verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
        
        const formData = new FormData();
        formData.append('action', 'verify');
        formData.append('email', userEmail);
        formData.append('otp', enteredOtp);
        
        // FIX: Change 'verify.php' to 'verification.php'
        const response = await fetch('verification.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Server response:', data);
        
        if (data.success) {
            verifySuccess(data);
        } else {
            showError(data.message);
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = '<i class="fas fa-check-circle"></i> Verify and Complete';
        }
        
    } catch (error) {
    console.error('Detailed Fetch Error:', {
        name: error.name,
        message: error.message,
        stack: error.stack
    });
    
    // Check what type of error it is
    if (error.name === 'TypeError' && error.message.includes('Failed to fetch')) {
        showError('Cannot connect to server. Check if PHP file exists and server is running.');
    } else {
        showError('Server error: ' + error.message);
    }
    
    verifyBtn.disabled = false;
    verifyBtn.innerHTML = '<i class="fas fa-check-circle"></i> Verify and Complete';
}
});
