// Phone number formatting function
function formatPhoneNumber(input) {
    // Get the input value and remove all non-numeric characters
    let value = input.value.replace(/\D/g, '').replace(/^1/, '').slice(0, 10);
    
    // Apply formatting as user types (XXX-XXX-XXXX)
    if (value.length > 3 && value.length <= 6) {
        value = value.replace(/(\d{3})(\d+)/, '$1-$2');
    } else if (value.length > 6) {
        value = value.replace(/(\d{3})(\d{3})(\d+)/, '$1-$2-$3');
    }
    
    // Update the input field
    input.value = value;
}