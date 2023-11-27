<?php
/*
Plugin Name: Partial Contact Info
Description: Wtyczka obsługuje pola adresu e-mail i numeru telefonu, aby wyświetlić je zakodowane.
Version: 1.1
Author: Mateusz Turek
Author URI: https://mateuszturek.pl/
License: GPLv2 or later
Text Domain: partial-contact-info
*/

// Add settings fields for email, phone number, and number of visible characters
function partial_contact_info_settings() {
    add_settings_section('partial-contact-info', 'Informacje kontaktowe', '', 'general');

    add_settings_field('partial_contact_email', 'Adres email', 'partial_contact_email_callback', 'general', 'partial-contact-info');
    register_setting('general', 'partial_contact_email');

    add_settings_field('partial_contact_phone', 'Numer telefonu', 'partial_contact_phone_callback', 'general', 'partial-contact-info');
    register_setting('general', 'partial_contact_phone');

    add_settings_field('partial_contact_email_visible_chars', 'Znaki w adresie email', 'partial_contact_email_visible_chars_callback', 'general', 'partial-contact-info');
    register_setting('general', 'partial_contact_email_visible_chars');

    add_settings_field('partial_contact_phone_visible_chars', 'Znaki w numerze tel.', 'partial_contact_phone_visible_chars_callback', 'general', 'partial-contact-info');
    register_setting('general', 'partial_contact_phone_visible_chars');
	
	add_settings_field('partial_contact_email_reveal_text', 'Treść linku adres email', 'partial_contact_email_reveal_text_callback', 'general', 'partial-contact-info');
    register_setting('general', 'partial_contact_email_reveal_text');

    add_settings_field('partial_contact_phone_reveal_text', 'Treść linku numer teleofnu', 'partial_contact_phone_reveal_text_callback', 'general', 'partial-contact-info');
    register_setting('general', 'partial_contact_phone_reveal_text');
}

function partial_contact_email_callback() {
    echo '<input type="email" id="partial_contact_email" name="partial_contact_email" value="' . get_option('partial_contact_email') . '">';
}

function partial_contact_phone_callback() {
    echo '<input type="text" id="partial_contact_phone" name="partial_contact_phone" value="' . get_option('partial_contact_phone') . '">';
}

function partial_contact_email_visible_chars_callback() {
    echo '<input type="number" id="partial_contact_email_visible_chars" name="partial_contact_email_visible_chars" value="' . get_option('partial_contact_email_visible_chars', 3) . '" min="1">';
}

function partial_contact_phone_visible_chars_callback() {
    echo '<input type="number" id="partial_contact_phone_visible_chars" name="partial_contact_phone_visible_chars" value="' . get_option('partial_contact_phone_visible_chars', 3) . '" min="1">';
}

function partial_contact_email_reveal_text_callback() {
    echo '<input type="text" id="partial_contact_email_reveal_text" name="partial_contact_email_reveal_text" value="' . get_option('partial_contact_email_reveal_text', 'pokaż cały') . '">';
}

function partial_contact_phone_reveal_text_callback() {
    echo '<input type="text" id="partial_contact_phone_reveal_text" name="partial_contact_phone_reveal_text" value="' . get_option('partial_contact_phone_reveal_text', 'pokaż cały') . '">';
}

add_action('admin_init', 'partial_contact_info_settings');

// Shortcodes to display partial email and phone number
function partial_contact_email_shortcode() {
    $email = get_option('partial_contact_email');
    $visible_chars = intval(get_option('partial_contact_email_visible_chars', 3));
    $partial_email = substr($email, 0, $visible_chars);
    $reveal_text = get_option('partial_contact_email_reveal_text', 'pokaż adres email');
    return '<a href="#" class="partial-contact" data-type="email">' . $partial_email . ' ' . $reveal_text . '</a>';
}
add_shortcode('partial_contact_email', 'partial_contact_email_shortcode');

function partial_contact_phone_shortcode() {
    $phone = get_option('partial_contact_phone');
    $visible_chars = intval(get_option('partial_contact_phone_visible_chars', 3));
    $partial_phone = substr($phone, 0, $visible_chars);
    $reveal_text = get_option('partial_contact_phone_reveal_text', 'pokaż numer telefonu');
    return '<a href="#" class="partial-contact" data-type="phone">' . $partial_phone . ' ' . $reveal_text . '</a>';
}
add_shortcode('partial_contact_phone', 'partial_contact_phone_shortcode');

// AJAX action to get the full value
function partial_contact_info_ajax_handler() {
    $type = isset($_POST['type']) ? $_POST['type'] : '';

    if ($type === 'email') {
        echo get_option('partial_contact_email');
    } elseif ($type === 'phone') {
        echo get_option('partial_contact_phone');
    }

    wp_die();
}
add_action('wp_ajax_partial_contact_info', 'partial_contact_info_ajax_handler');
add_action('wp_ajax_nopriv_partial_contact_info', 'partial_contact_info_ajax_handler');

// Add JavaScript to handle the click event
function partial_contact_info_scripts() {
    $ajax_url = admin_url('admin-ajax.php');
    echo <<<EOT
<script>
document.addEventListener('DOMContentLoaded', function() {
    const partialContacts = document.querySelectorAll('.partial-contact');
    partialContacts.forEach(function(el) {
        el.addEventListener('click', function(event) {
            event.preventDefault();
            const type = el.dataset.type;

            fetch('{$ajax_url}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    'action': 'partial_contact_info',
                    'type': type
                })
            })
            .then(response => response.text())
            .then(text => {
                if (type === 'email') {
                    el.href = 'mailto:' + text;
                } else if (type === 'phone') {
                    el.href = 'tel:' + text;
                }
                el.innerText = text;
                el.classList.remove('partial-contact');
                el.removeEventListener('click', arguments.callee);
            })
            .catch(error => {
                console.error('Error fetching contact info:', error);
            });
        });
    });
});
</script>
EOT;
}
add_action('wp_footer', 'partial_contact_info_scripts');