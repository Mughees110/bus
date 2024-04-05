<?php
/*
Plugin Name: My Custom Plugin
Description: A simple WordPress plugin to display a form and submit data to an API.
Version: 1.0
*/

// Function to display the form
function custom_form_display() {
    ob_start();
    ?>
    <form id="custom-form">
        <label for="field1">Field 1:</label>
        <input type="text" id="field1" name="field1" required><br><br>
        
        <label for="field2">Field 2:</label>
        <input type="text" id="field2" name="field2" required><br><br>
        
        <button type="submit">Submit</button>
    </form>
    <div id="response-message"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('custom-form').addEventListener('submit', function (e) {
                e.preventDefault();
                var formData = new FormData(this);

                fetch('YOUR_API_ENDPOINT', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('response-message').innerHTML = data.message;
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        });
    </script>
    <?php
    return ob_get_clean();
}

// Shortcode to display the form
function custom_form_shortcode() {
    return custom_form_display();
}
add_shortcode('custom_form', 'custom_form_shortcode');
?>
