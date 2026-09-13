<?php
/**
 * Advisory appointment-link display for the optional i693 intake form.
 * Query parameters are untrusted display hints, NOT appointment verification or
 * an authorization/expiry token. Public intake does not read an existing chart.
 */
function gcm_i693_query($key) {
    if (!array_key_exists($key, $_GET)) { return null; }
    $value = $_GET[$key];
    return is_string($value) && strlen($value) <= 256 ? trim(wp_unslash($value)) : false;
}

function gcm_i693_form_date() {
    $raw = gcm_i693_query('date1');
    if ($raw === null || $raw === '') { return ''; }
    if ($raw === 'Today') { return wp_date('m/d/Y'); }
    if ($raw === false) { return false; }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $raw, wp_timezone());
    return $date && $date->format('Y-m-d') === $raw ? $date->format('m/d/Y') : false;
}

function gcm_is_i693_link_expired(&$debug_message = '') {
    $expiration = gcm_i693_query('exp');
    $debug_message = '';
    if ($expiration === null || $expiration === '') { return false; }
    $date = false;
    if (is_string($expiration)) {
        foreach (array('Y-m-d-H:i:s', 'Y-m-d\TH:i:s\Z', 'Y-m-d\TH:i:sP') as $format) {
            $candidate = DateTimeImmutable::createFromFormat('!' . $format, $expiration, new DateTimeZone('UTC'));
            if ($candidate && $candidate->format($format) === $expiration) {
                $date = $candidate;
                break;
            }
        }
    }
    if (!$date) {
        $debug_message = '<p>Invalid link expiration.</p>';
        return true;
    }
    // Retain the display grace period. Removing/changing this unsigned query
    // parameter is possible; it is intentionally not used as an access control.
    $expired = time() > $date->getTimestamp() + 30 * MINUTE_IN_SECONDS;
    $debug_message = '<p>Link display window ' . ($expired ? 'expired' : 'has not expired') . '.</p>';
    return $expired;
}

function gcm_replace_i693_form_if_expired($html) {
    $form = function_exists('wpcf7_get_current_contact_form') ? wpcf7_get_current_contact_form() : null;
    if (GCM_Clinical_Form_Mail::kind($form) !== 'i693') { return $html; }
    $debug = '';
    if (gcm_is_i693_link_expired($debug)) { $html = get_i693_expired_message(); }
    if (isset($_GET['debug']) && current_user_can('manage_options')) {
        $html = $debug . $html;
    }
    return $html;
}
add_filter('wpcf7_form_elements', 'gcm_replace_i693_form_if_expired');

function get_i693_expired_message() {
    $message = function_exists('get_field') ? get_field('i639_expired_message', 'gcm_settings') : '';
    if (!is_string($message) || $message === '') {
        $message = "We're sorry, the link you followed has expired. Please contact the clinic.";
    }
    return wpautop(wp_kses_post($message));
}

/** Resolve only this form's operator-configured public location labels. */
function gcm_i693_location_label($form, $code) {
    if (!$form || GCM_Clinical_Form_Mail::kind($form) !== 'i693' || !is_string($code)) { return ''; }
    foreach ($form->additional_setting('gcm_i693_location', 0) as $location) {
        $parts = explode('|', $location, 2);
        if (count($parts) === 2 && preg_match('/^[A-Za-z0-9_-]{1,32}$/D', trim($parts[0])) &&
            trim($parts[0]) === $code && strlen($parts[1]) <= 512) {
            return trim($parts[1]);
        }
    }
    return '';
}

function get_i693_appointment_message($use_form_value = false, $form = null) {
    $date = gcm_i693_form_date();
    $time = gcm_i693_query('time');
    $location = gcm_i693_query('location');
    if ($date === false || $time === false || $location === false) { return false; }
    if (!$date && !$time && !$location) { return false; }
    $display_time = '';
    if ($time) {
        // Support both supplied-link formats, without accepting arbitrary HTML/date prose.
        $time = preg_replace('/^(\d{1,2})-(\d{2})-(AM|PM)$/i', '$1:$2 $3', $time);
        foreach (array('g:i A', 'h:i A', 'H:i') as $format) {
            $parsed = DateTimeImmutable::createFromFormat('!' . $format, strtoupper($time), wp_timezone());
            if ($parsed && $parsed->format($format) === strtoupper($time)) {
                $display_time = $parsed->format('g:i A');
                break;
            }
        }
        if ($display_time === '') { return false; }
    }
    if (!$form && function_exists('wpcf7_get_current_contact_form')) { $form = wpcf7_get_current_contact_form(); }
    $address = gcm_i693_location_label($form, $location);
    $key = $use_form_value ? 'i639_appointment_form_value' : 'i639_appointment_message';
    $template = function_exists('get_field') ? get_field($key, 'gcm_settings') : '';
    if (!is_string($template) || $template === '') {
        $template = 'Requested appointment details: [date] at [time]. [location]';
    }
    $values = array('[date1]' => $date, '[date]' => $date, '[time]' => $display_time,
        '[location]' => $address, '[address]' => $address);
    if ($use_form_value) {
        return str_replace(array_keys($values), array_values($values), wp_strip_all_tags($template));
    }
    return wp_kses_post(str_replace(array_keys($values), array_map('esc_html', array_values($values)), $template));
}

function shortcode_i693_appointment($atts, $content = '', $shortcode_name = 'i693_appointment') {
    $atts = shortcode_atts(array('form_id' => ''), $atts, $shortcode_name);
    $form = null;
    if ($atts['form_id'] !== '') {
        if (!is_string($atts['form_id']) || !ctype_digit($atts['form_id']) || !class_exists('WPCF7_ContactForm')) { return ''; }
        $form = WPCF7_ContactForm::get_instance((int) $atts['form_id']);
        if (GCM_Clinical_Form_Mail::kind($form) !== 'i693') { return ''; }
    }
    $message = get_i693_appointment_message(false, $form);
    if (!$message || gcm_is_i693_link_expired()) { return ''; }
    return '<div class="i693-appointment-card"><div class="wp-block-group container-style-card-x-small">' .
        '<div class="i693-content">' . $message .
        '<p>These link details do not confirm a booking. Contact the clinic to verify your appointment.</p></div></div></div>';
}
add_shortcode('i693_appointment', 'shortcode_i693_appointment');
