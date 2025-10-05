// --- UPDATED PASSWORD VALIDATION ---
function validatePassword(textbox) {
    // MUST clear validity first to allow subsequent checks to pass or fail
    textbox.setCustomValidity(''); 

    const value = textbox.value;
    
    // Regular expression to check for minimum complexity:
    // ^: start of string
    // (?=.*[a-z]): Lookahead for at least one lowercase letter
    // (?=.*[A-Z]): Lookahead for at least one uppercase letter
    // (?=.*[0-9]): Lookahead for at least one number
    // [a-zA-Z0-9!@#$%^&*()_+={}\[\]:;<>,.?\/\\~-]{8,}: Matches any allowed character (including special), minimum 8 times
    // $: end of string
    const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])[a-zA-Z0-9!@#$%^&*()_+={}\[\]:;<>,.?\/\\~-]{8,}$/;

    if (value === '') {
        textbox.setCustomValidity('A password is required!');
    } 
    // Use the single pattern check
    else if (!passwordPattern.test(value)) {
        textbox.setCustomValidity('Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number. Special characters are allowed.');
    }
    
    // Returns true, but the setCustomValidity call handles the error message.
    return true; 
}
    
// --- UPDATED EMAIL VALIDATION ---
function validateEmail(textbox) {
    // MUST clear validity first to allow subsequent checks
    textbox.setCustomValidity('');

    if (textbox.validity.valueMissing) {
        textbox.setCustomValidity('Entering an email address is required!');
    } else if (textbox.validity.typeMismatch) {
        textbox.setCustomValidity('Please enter a valid email address (e.g., user@example.com).');
    }
    
    // Returns true, but the setCustomValidity call handles the error message.
    return true;
}  

