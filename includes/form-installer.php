<?php
/**
 * Automatic Contact Form 7 Form Installer
 *
 * Creates developmental evaluation forms automatically on plugin activation
 * or when forms are missing. Stores form IDs in options for processors to use.
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Install all developmental evaluation forms
 *
 * @return array Results of installation
 */
function gcm_install_evaluation_forms() {
    // Check if Contact Form 7 is active
    if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
        return array(
            'success' => false,
            'message' => 'Contact Form 7 plugin is not active. Please activate it first.'
        );
    }

    $results = array(
        'forms_created' => array(),
        'forms_skipped' => array(),
        'errors' => array()
    );

    // Install each form
    $forms = array(
        'developmental_eval' => array(
            'title' => 'Developmental Evaluation Intake',
            'option_key' => 'gcm_developmental_eval_form_id',
            'template' => 'gcm_get_developmental_eval_form_template',
        ),
        'vanderbilt' => array(
            'title' => 'NICHQ Vanderbilt Assessment',
            'option_key' => 'gcm_vanderbilt_form_id',
            'template' => 'gcm_get_vanderbilt_form_template',
        ),
        'teacher_report' => array(
            'title' => 'Teacher Report Form',
            'option_key' => 'gcm_teacher_report_form_id',
            'template' => 'gcm_get_teacher_report_form_template',
        ),
        'pcp_referral' => array(
            'title' => 'PCP Referral Form',
            'option_key' => 'gcm_pcp_referral_form_id',
            'template' => 'gcm_get_pcp_referral_form_template',
        ),
    );

    foreach ( $forms as $form_key => $form_data ) {
        // Check if form already exists
        $existing_form_id = get_option( $form_data['option_key'] );
        $form_id = null;

        if ( $existing_form_id && get_post( $existing_form_id ) ) {
            $results['forms_skipped'][] = $form_data['title'] . ' (form already exists)';
            $form_id = $existing_form_id;
        } else {
            // Get form template
            $template_function = $form_data['template'];
            if ( ! function_exists( $template_function ) ) {
                $results['errors'][] = 'Template function not found: ' . $template_function;
                continue;
            }

            $template = call_user_func( $template_function );

            // Create the form
            $form_id = gcm_create_cf7_form( $form_data['title'], $template );

            if ( is_wp_error( $form_id ) ) {
                $results['errors'][] = $form_data['title'] . ': ' . $form_id->get_error_message();
                continue;
            } else {
                // Save form ID to options
                update_option( $form_data['option_key'], $form_id );
            }
        }

        // Create a page for this form (even if form already existed)
        if ( $form_id ) {
            $page_id = gcm_create_form_page( $form_data['title'], $form_id, $form_key );
            if ( is_wp_error( $page_id ) ) {
                $results['errors'][] = 'Failed to create page for ' . $form_data['title'] . ': ' . $page_id->get_error_message();
            } elseif ( $page_id ) {
                // Store page ID in options for linking
                update_option( $form_data['option_key'] . '_page', $page_id );
                $results['forms_created'][] = $form_data['title'] . ' (Form ID: ' . $form_id . ', Page ID: ' . $page_id . ')';
            } else {
                $results['errors'][] = 'Failed to create page for ' . $form_data['title'] . ' (unknown error)';
            }
        }
    }

    // Build success message
    $message = '';
    if ( ! empty( $results['forms_created'] ) ) {
        $message .= 'Created ' . count( $results['forms_created'] ) . ' forms. ';
    }
    if ( ! empty( $results['forms_skipped'] ) ) {
        $message .= 'Skipped ' . count( $results['forms_skipped'] ) . ' existing forms. ';
    }
    if ( ! empty( $results['errors'] ) ) {
        $message .= 'Errors: ' . implode( ', ', $results['errors'] );
    }

    return array(
        'success' => empty( $results['errors'] ),
        'message' => $message,
        'details' => $results
    );
}

/**
 * Create a Contact Form 7 form programmatically
 *
 * @param string $title Form title
 * @param string $template Form template (HTML content)
 * @return int|WP_Error Form ID or error
 */
function gcm_create_cf7_form( $title, $template ) {
    // Create the form post
    $form_id = wp_insert_post( array(
        'post_type' => 'wpcf7_contact_form',
        'post_title' => $title,
        'post_status' => 'publish',
        'post_content' => '', // CF7 stores form in post_content as serialized data
    ) );

    if ( is_wp_error( $form_id ) ) {
        return $form_id;
    }

    // Create CF7 form object and set properties
    $contact_form = WPCF7_ContactForm::get_instance( $form_id );

    if ( ! $contact_form ) {
        return new WP_Error( 'cf7_error', 'Failed to create Contact Form 7 object' );
    }

    // Set form template
    $contact_form->set_properties( array(
        'form' => $template,
        'mail' => array(
            'subject' => '[_site_title] "' . $title . '"',
            'sender' => '[_site_admin_email]',
            'body' => 'Form submission from ' . $title,
            'recipient' => '[_site_admin_email]',
            'additional_headers' => '',
            'attachments' => '',
            'use_html' => 0,
            'exclude_blank' => 0,
        ),
    ) );

    $contact_form->save();

    return $form_id;
}

/**
 * Create a page for a form
 *
 * @param string $title Page title
 * @param int $form_id CF7 form ID
 * @param string $form_key Form key for slug
 * @return int|WP_Error Page ID or error
 */
function gcm_create_form_page( $title, $form_id, $form_key ) {
    // Use a unique slug prefix to avoid conflicts with existing pages
    $page_slug = 'form-' . $form_key;

    // Check if page already exists by slug
    $existing_page = get_page_by_path( $page_slug );

    // Create page content with constrained container
    $page_content = '<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
[contact-form-7 id="' . $form_id . '"]
</div>
<!-- /wp:group -->';

    if ( $existing_page ) {
        // Update existing page content with current form ID
        wp_update_post( array(
            'ID' => $existing_page->ID,
            'post_content' => $page_content,
        ) );
        return $existing_page->ID;
    }

    // Create page with form shortcode
    $page_id = wp_insert_post( array(
        'post_type' => 'page',
        'post_title' => $title,
        'post_name' => $page_slug,
        'post_status' => 'publish',
        'post_content' => $page_content,
    ) );

    return $page_id;
}

/**
 * Get Developmental Evaluation form template
 * Note: This is a condensed version - full template is in DEVELOPMENTAL-EVAL-FORM-TEMPLATE.md
 */
function gcm_get_developmental_eval_form_template() {
    return '<div class="field-group-heading">
    <h3 class="title">Developmental Evaluation Intake</h3>
    <p>Please complete this form to request a developmental evaluation.</p>
</div>

<div class="field-group field-list">
    <div class="group-label">Child Information</div>
    <div class="group-fields group-columns-3">
        <div class="field type-text is-required">
            <div class="field-label">
                <label for="child-first-name">Child\'s First Name</label>
            </div>
            <div class="field-content">
                [text* child_first_name id:child-first-name class:letters_space]
            </div>
        </div>

        <div class="field type-text">
            <div class="field-label">
                <label for="child-middle-name">Middle Name</label>
            </div>
            <div class="field-content">
                [text child_middle_name id:child-middle-name class:letters_space]
            </div>
        </div>

        <div class="field type-text is-required">
            <div class="field-label">
                <label for="child-last-name">Child\'s Last Name</label>
            </div>
            <div class="field-content">
                [text* child_last_name id:child-last-name class:letters_space]
            </div>
        </div>

        <div class="field type-date is-required">
            <div class="field-label">
                <label for="child-dob">Child\'s Date of Birth</label>
            </div>
            <div class="field-content">
                [date* child_dob id:child-dob]
            </div>
        </div>

        <div class="field type-select is-required">
            <div class="field-label">
                <label for="child-gender">Gender</label>
            </div>
            <div class="field-content">
                [select* child_gender id:child-gender "Male" "Female" "Other" "Prefer not to say"]
            </div>
        </div>

        <div class="field type-text is-required">
            <div class="field-label">
                <label for="grade-level">Current Grade Level</label>
            </div>
            <div class="field-content">
                [text* grade_level id:grade-level placeholder "e.g., Kindergarten, 1st, 2nd"]
            </div>
        </div>

        <div class="field type-text is-required">
            <div class="field-label">
                <label for="school-name">School Name</label>
            </div>
            <div class="field-content">
                [text* school_name id:school-name]
            </div>
        </div>
    </div>
</div>

<div class="field-group field-list">
    <div class="group-label">Evaluation Type</div>
    <div class="group-fields group-columns-1">
        <div class="field type-dropdown is-required">
            <div class="field-label">
                <label for="evaluation-type">Type of Evaluation Requested</label>
            </div>
            <div class="field-content">
                [select* evaluation_type id:evaluation-type "ADHD Evaluation" "Autism Spectrum Evaluation" "Learning Disability Assessment" "Developmental Delay Evaluation" "Other"]
            </div>
        </div>

        <div class="field type-textarea is-required">
            <div class="field-label">
                <label for="primary-concerns">Primary Concerns</label>
            </div>
            <div class="field-content">
                [textarea* primary_concerns id:primary-concerns]
            </div>
        </div>
    </div>
</div>

<div class="field-group field-list">
    <div class="group-label">Parent/Guardian Information</div>
    <div class="group-fields group-columns-2">
        <div class="field type-text is-required">
            <div class="field-label">
                <label for="parent-first-name">Parent/Guardian First Name</label>
            </div>
            <div class="field-content">
                [text* parent_first_name id:parent-first-name class:letters_space]
            </div>
        </div>

        <div class="field type-text is-required">
            <div class="field-label">
                <label for="parent-last-name">Parent/Guardian Last Name</label>
            </div>
            <div class="field-content">
                [text* parent_last_name id:parent-last-name class:letters_space]
            </div>
        </div>

        <div class="field type-select is-required">
            <div class="field-label">
                <label for="relationship">Relationship to Child</label>
            </div>
            <div class="field-content">
                [select* relationship id:relationship "Mother" "Father" "Grandmother" "Grandfather" "Legal Guardian" "Other"]
            </div>
        </div>

        <div class="field type-email is-required">
            <div class="field-label">
                <label for="parent-email">Email Address</label>
            </div>
            <div class="field-content">
                [email* parent_email id:parent-email]
            </div>
        </div>

        <div class="field type-tel is-required">
            <div class="field-label">
                <label for="phone-primary">Primary Phone</label>
            </div>
            <div class="field-content">
                [tel* phone_primary id:phone-primary]
            </div>
        </div>

        <div class="field type-tel">
            <div class="field-label">
                <label for="phone-secondary">Secondary Phone</label>
            </div>
            <div class="field-content">
                [tel phone_secondary id:phone-secondary]
            </div>
        </div>
    </div>
</div>

<div class="field-group-heading">
    <h3 class="title">Address</h3>
</div>

<div class="field-group field-list">
    <div class="group-fields group-columns-2">
        <div class="field type-text is-required">
            <div class="field-label">
                <label for="street">Street Address</label>
            </div>
            <div class="field-content">
                [text* street id:street]
            </div>
        </div>

        <div class="field type-select">
            <div class="field-label">
                <label for="apartment-type">Apt/Suite Type</label>
            </div>
            <div class="field-content">
                [select apartment_type id:apartment-type "None" "Apt" "Suite" "Unit" "Building"]
            </div>
        </div>

        <div class="field type-text">
            <div class="field-label">
                <label for="apartment-number">Apt/Suite Number</label>
            </div>
            <div class="field-content">
                [text apartment_number id:apartment-number]
            </div>
        </div>

        <div class="field type-text is-required">
            <div class="field-label">
                <label for="city">City</label>
            </div>
            <div class="field-content">
                [text* city id:city]
            </div>
        </div>

        <div class="field type-text is-required">
            <div class="field-label">
                <label for="state">State</label>
            </div>
            <div class="field-content">
                [text* state id:state placeholder "e.g., FL"]
            </div>
        </div>

        <div class="field type-text is-required">
            <div class="field-label">
                <label for="zip-code">ZIP Code</label>
            </div>
            <div class="field-content">
                [text* zip_code id:zip-code]
            </div>
        </div>
    </div>
</div>

<div class="field-group-heading">
    <h3 class="title">Additional Information</h3>
</div>

<div class="field-group field-list">
    <div class="group-fields group-columns-1">
        <div class="field type-textarea">
            <div class="field-label">
                <label for="previous-evaluations">Previous Evaluations (if any)</label>
            </div>
            <div class="field-content">
                [textarea previous_evaluations id:previous-evaluations placeholder "Please describe any previous evaluations, when they were done, and by whom"]
            </div>
        </div>

        <div class="field type-textarea">
            <div class="field-label">
                <label for="current-services">Current Services/Therapies</label>
            </div>
            <div class="field-content">
                [textarea current_services id:current-services placeholder "e.g., Speech therapy, Occupational therapy, etc."]
            </div>
        </div>

        <div class="field type-text">
            <div class="field-label">
                <label for="referral-source">How did you hear about us?</label>
            </div>
            <div class="field-content">
                [text referral_source id:referral-source placeholder "e.g., Physician referral, Online search, Friend"]
            </div>
        </div>
    </div>
</div>

<div class="field-group-heading">
    <h3 class="title">Insurance Information</h3>
</div>

<div class="field-group field-list">
    <div class="group-fields group-columns-2">
        <div class="field type-text">
            <div class="field-label">
                <label for="insurance-name">Insurance Provider</label>
            </div>
            <div class="field-content">
                [text insurance_name id:insurance-name]
            </div>
        </div>

        <div class="field type-text">
            <div class="field-label">
                <label for="insurance-id">Insurance ID/Member Number</label>
            </div>
            <div class="field-content">
                [text insurance_id id:insurance-id]
            </div>
        </div>
    </div>
</div>

<div class="field-group-heading">
    <h3 class="title">Scheduling Preferences</h3>
</div>

<div class="field-group field-list">
    <div class="group-fields group-columns-1">
        <div class="field type-select">
            <div class="field-label">
                <label for="preferred-time">Preferred Appointment Time</label>
            </div>
            <div class="field-content">
                [select preferred_time id:preferred-time "Morning (8am-12pm)" "Afternoon (12pm-5pm)" "Evening (5pm-8pm)" "No Preference"]
            </div>
        </div>
    </div>
</div>

<div class="field-group group-submit">
    <div class="group-fields group-columns-3">
        <div class="field type-submit">
            [submit "Submit Request" class:button]
        </div>
    </div>
</div>';
}

/**
 * Get Vanderbilt Assessment form template (condensed)
 */
function gcm_get_vanderbilt_form_template() {
    return '<div class="field-group-heading">
    <h3 class="title">NICHQ Vanderbilt Assessment Scale</h3>
    <p>Rating: 0 = Never | 1 = Occasionally | 2 = Often | 3 = Very Often</p>
</div>

<div class="field-group field-list">
    <div class="group-label">Student Information</div>
    <div class="group-fields group-columns-3">
        <div class="field type-text is-required">
            <div class="field-label">
                <label for="student-first-name">Student\'s First Name</label>
            </div>
            <div class="field-content">
                [text* student_first_name id:student-first-name class:letters_space]
            </div>
        </div>

        <div class="field type-text is-required">
            <div class="field-label">
                <label for="student-last-name">Student\'s Last Name</label>
            </div>
            <div class="field-content">
                [text* student_last_name id:student-last-name class:letters_space]
            </div>
        </div>

        <div class="field type-date is-required">
            <div class="field-label">
                <label for="student-dob">Student\'s Date of Birth</label>
            </div>
            <div class="field-content">
                [date* student_dob id:student-dob]
            </div>
        </div>
    </div>
</div>

<div class="field-group-heading">
    <h3 class="title">Inattention Symptoms (Questions 1-9)</h3>
</div>

<div class="field-group field-list">
    <div class="group-fields group-columns-1">
        <div class="field type-radio-button-row is-required">
            <div class="field-label">
                <label>1. Fails to give attention to details or makes careless mistakes</label>
            </div>
            <div class="field-content">
                [radio q1_fails_attention use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
            </div>
        </div>

        <div class="field type-radio-button-row is-required">
            <div class="field-label">
                <label>2. Has difficulty sustaining attention</label>
            </div>
            <div class="field-content">
                [radio q2_difficulty_sustaining use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
            </div>
        </div>

        <div class="field type-radio-button-row is-required">
            <div class="field-label">
                <label>3. Does not seem to listen when spoken to directly</label>
            </div>
            <div class="field-content">
                [radio q3_not_listening use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
            </div>
        </div>

        <div class="field type-radio-button-row is-required">
            <div class="field-label">
                <label>4. Does not follow through on instructions and fails to finish work</label>
            </div>
            <div class="field-content">
                [radio q4_not_follow_through use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
            </div>
        </div>

        <div class="field type-radio-button-row is-required">
            <div class="field-label">
                <label>5. Has difficulty organizing tasks and activities</label>
            </div>
            <div class="field-content">
                [radio q5_difficulty_organizing use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
            </div>
        </div>

        <div class="field type-radio-button-row is-required">
            <div class="field-label">
                <label>6. Avoids tasks requiring sustained mental effort</label>
            </div>
            <div class="field-content">
                [radio q6_avoids_tasks use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
            </div>
        </div>

        <div class="field type-radio-button-row is-required">
            <div class="field-label">
                <label>7. Loses things necessary for tasks or activities</label>
            </div>
            <div class="field-content">
                [radio q7_loses_things use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
            </div>
        </div>

        <div class="field type-radio-button-row is-required">
            <div class="field-label">
                <label>8. Is easily distracted by extraneous stimuli</label>
            </div>
            <div class="field-content">
                [radio q8_easily_distracted use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
            </div>
        </div>

        <div class="field type-radio-button-row is-required">
            <div class="field-label">
                <label>9. Is forgetful in daily activities</label>
            </div>
            <div class="field-content">
                [radio q9_forgetful use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
            </div>
        </div>
    </div>
</div>

<div class="field-group-heading">
    <h3 class="title">Hyperactivity/Impulsivity Symptoms (Questions 10-18)</h3>
</div>

<div class="field-group field-list">
    <div class="group-fields group-columns-1">
        <div class="field type-radio-button-row is-required">
            <div class="field-label">
                <label>10. Fidgets with hands or feet or squirms in seat</label>
            </div>
            <div class="field-content">
                [radio q10_fidgets use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
            </div>
        </div>

        <div class="field type-radio-button-row is-required">
            <div class="field-label">
                <label>11. Leaves seat when remaining seated is expected</label>
            </div>
            <div class="field-content">
                [radio q11_leaves_seat use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
            </div>
        </div>

        <div class="field type-radio-button-row is-required">
            <div class="field-label">
                <label>12. Runs about or climbs excessively in inappropriate situations</label>
            </div>
            <div class="field-content">
                [radio q12_runs_climbs use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
            </div>
        </div>

        <div class="field type-radio-button-row is-required">
            <div class="field-label">
                <label>13. Has difficulty playing or engaging in leisure activities quietly</label>
            </div>
            <div class="field-content">
                [radio q13_difficulty_quiet use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
            </div>
        </div>

        <div class="field type-radio-button-row is-required">
            <div class="field-label">
                <label>14. Is "on the go" or acts as if "driven by a motor"</label>
            </div>
            <div class="field-content">
                [radio q14_on_the_go use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
            </div>
        </div>

        <div class="field type-radio-button-row is-required">
            <div class="field-label">
                <label>15. Talks excessively</label>
            </div>
            <div class="field-content">
                [radio q15_talks_excessively use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
            </div>
        </div>

        <div class="field type-radio-button-row is-required">
            <div class="field-label">
                <label>16. Blurts out answers before questions have been completed</label>
            </div>
            <div class="field-content">
                [radio q16_blurts_answers use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
            </div>
        </div>

        <div class="field type-radio-button-row is-required">
            <div class="field-label">
                <label>17. Has difficulty waiting their turn</label>
            </div>
            <div class="field-content">
                [radio q17_difficulty_waiting use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
            </div>
        </div>

        <div class="field type-radio-button-row is-required">
            <div class="field-label">
                <label>18. Interrupts or intrudes on others</label>
            </div>
            <div class="field-content">
                [radio q18_interrupts use_label_element default:1 "0 - Never" "1 - Occasionally" "2 - Often" "3 - Very Often"]
            </div>
        </div>
    </div>
</div>

<div class="field-group-heading">
    <h3 class="title">Respondent Information</h3>
</div>

<div class="field-group field-list">
    <div class="group-fields group-columns-2">
        <div class="field type-text is-required">
            <div class="field-label">
                <label for="respondent-name">Your Name</label>
            </div>
            <div class="field-content">
                [text* respondent_name id:respondent-name class:letters_space]
            </div>
        </div>

        <div class="field type-text is-required">
            <div class="field-label">
                <label for="respondent-relationship">Relationship to Student</label>
            </div>
            <div class="field-content">
                [text* respondent_relationship id:respondent-relationship]
            </div>
        </div>
    </div>
</div>

<div class="field-group group-submit">
    <div class="group-fields group-columns-3">
        <div class="field type-submit">
            [submit "Submit Assessment" class:button]
        </div>
    </div>
</div>';
}

/**
 * Get Teacher Report form template (condensed)
 */
function gcm_get_teacher_report_form_template() {
    return '<div class="field-group-heading">
    <h3 class="title">Teacher Report Form</h3>
    <p>Please provide your observations about this student.</p>
</div>

<div class="field-group field-list">
    <div class="group-label">Student Information</div>
    <div class="group-fields group-columns-3">
        <div class="field type-text is-required">
            <div class="field-label">
                <label for="student-first-name">Student\'s First Name</label>
            </div>
            <div class="field-content">
                [text* student_first_name id:student-first-name class:letters_space]
            </div>
        </div>

        <div class="field type-text is-required">
            <div class="field-label">
                <label for="student-last-name">Student\'s Last Name</label>
            </div>
            <div class="field-content">
                [text* student_last_name id:student-last-name class:letters_space]
            </div>
        </div>

        <div class="field type-date is-required">
            <div class="field-label">
                <label for="student-dob">Student\'s Date of Birth</label>
            </div>
            <div class="field-content">
                [date* student_dob id:student-dob]
            </div>
        </div>
    </div>
</div>

<div class="field-group field-list">
    <div class="group-label">Teacher Information</div>
    <div class="group-fields group-columns-2">
        <div class="field type-text is-required">
            <div class="field-label">
                <label for="teacher-name">Your Name</label>
            </div>
            <div class="field-content">
                [text* teacher_name id:teacher-name class:letters_space]
            </div>
        </div>

        <div class="field type-email is-required">
            <div class="field-label">
                <label for="teacher-email">Your Email</label>
            </div>
            <div class="field-content">
                [email* teacher_email id:teacher-email]
            </div>
        </div>

        <div class="field type-text is-required">
            <div class="field-label">
                <label for="school-name">School Name</label>
            </div>
            <div class="field-content">
                [text* school_name id:school-name]
            </div>
        </div>

        <div class="field type-text is-required">
            <div class="field-label">
                <label for="grade-taught">Grade</label>
            </div>
            <div class="field-content">
                [text* grade_taught id:grade-taught]
            </div>
        </div>

        <div class="field type-text">
            <div class="field-label">
                <label for="subject-taught">Subject(s) Taught</label>
            </div>
            <div class="field-content">
                [text subject_taught id:subject-taught]
            </div>
        </div>

        <div class="field type-text is-required">
            <div class="field-label">
                <label for="time-known">How long have you known this student?</label>
            </div>
            <div class="field-content">
                [text* time_known_student id:time-known placeholder "e.g., 6 months, 1 year"]
            </div>
        </div>
    </div>
</div>

<div class="field-group-heading">
    <h3 class="title">Academic Performance</h3>
</div>

<div class="field-group field-list">
    <div class="group-fields group-columns-1">
        <div class="field type-select is-required">
            <div class="field-label">
                <label for="reading-level">Reading Level</label>
            </div>
            <div class="field-content">
                [select* reading_level id:reading-level "Below Grade Level" "At Grade Level" "Above Grade Level"]
            </div>
        </div>

        <div class="field type-select is-required">
            <div class="field-label">
                <label for="math-level">Math Level</label>
            </div>
            <div class="field-content">
                [select* math_level id:math-level "Below Grade Level" "At Grade Level" "Above Grade Level"]
            </div>
        </div>

        <div class="field type-select is-required">
            <div class="field-label">
                <label for="writing-level">Writing Level</label>
            </div>
            <div class="field-content">
                [select* writing_level id:writing-level "Below Grade Level" "At Grade Level" "Above Grade Level"]
            </div>
        </div>

        <div class="field type-select is-required">
            <div class="field-label">
                <label for="academic-performance">Overall Academic Performance</label>
            </div>
            <div class="field-content">
                [select* academic_performance id:academic-performance "Excellent" "Good" "Average" "Below Average" "Poor"]
            </div>
        </div>

        <div class="field type-textarea">
            <div class="field-label">
                <label for="academic-concerns">Academic Concerns (if any)</label>
            </div>
            <div class="field-content">
                [textarea academic_concerns id:academic-concerns]
            </div>
        </div>
    </div>
</div>

<div class="field-group-heading">
    <h3 class="title">Behavioral Observations</h3>
</div>

<div class="field-group field-list">
    <div class="group-fields group-columns-1">
        <div class="field type-select is-required">
            <div class="field-label">
                <label for="attention-focus">Ability to Maintain Attention/Focus</label>
            </div>
            <div class="field-content">
                [select* attention_focus id:attention-focus "Excellent" "Good" "Fair" "Poor" "Very Poor"]
            </div>
        </div>

        <div class="field type-select is-required">
            <div class="field-label">
                <label for="follows-directions">Follows Directions</label>
            </div>
            <div class="field-content">
                [select* follows_directions id:follows-directions "Always" "Usually" "Sometimes" "Rarely" "Never"]
            </div>
        </div>

        <div class="field type-select is-required">
            <div class="field-label">
                <label for="completes-tasks">Completes Tasks/Assignments</label>
            </div>
            <div class="field-content">
                [select* completes_tasks id:completes-tasks "Always" "Usually" "Sometimes" "Rarely" "Never"]
            </div>
        </div>

        <div class="field type-select is-required">
            <div class="field-label">
                <label for="impulsivity">Impulsivity Level</label>
            </div>
            <div class="field-content">
                [select* impulsivity id:impulsivity "None" "Mild" "Moderate" "Severe"]
            </div>
        </div>

        <div class="field type-select is-required">
            <div class="field-label">
                <label for="hyperactivity">Hyperactivity Level</label>
            </div>
            <div class="field-content">
                [select* hyperactivity id:hyperactivity "None" "Mild" "Moderate" "Severe"]
            </div>
        </div>

        <div class="field type-textarea">
            <div class="field-label">
                <label for="behavioral-concerns">Behavioral Concerns (if any)</label>
            </div>
            <div class="field-content">
                [textarea behavioral_concerns id:behavioral-concerns]
            </div>
        </div>
    </div>
</div>

<div class="field-group-heading">
    <h3 class="title">Social/Emotional Development</h3>
</div>

<div class="field-group field-list">
    <div class="group-fields group-columns-1">
        <div class="field type-select is-required">
            <div class="field-label">
                <label for="peer-relationships">Peer Relationships</label>
            </div>
            <div class="field-content">
                [select* peer_relationships id:peer-relationships "Excellent" "Good" "Fair" "Poor" "Very Poor"]
            </div>
        </div>

        <div class="field type-select is-required">
            <div class="field-label">
                <label for="adult-relationships">Relationships with Adults</label>
            </div>
            <div class="field-content">
                [select* adult_relationships id:adult-relationships "Excellent" "Good" "Fair" "Poor" "Very Poor"]
            </div>
        </div>

        <div class="field type-select is-required">
            <div class="field-label">
                <label for="emotional-regulation">Emotional Regulation</label>
            </div>
            <div class="field-content">
                [select* emotional_regulation id:emotional-regulation "Excellent" "Good" "Fair" "Poor" "Very Poor"]
            </div>
        </div>

        <div class="field type-textarea">
            <div class="field-label">
                <label for="social-concerns">Social/Emotional Concerns (if any)</label>
            </div>
            <div class="field-content">
                [textarea social_concerns id:social-concerns]
            </div>
        </div>
    </div>
</div>

<div class="field-group-heading">
    <h3 class="title">Additional Information</h3>
</div>

<div class="field-group field-list">
    <div class="group-fields group-columns-1">
        <div class="field type-textarea">
            <div class="field-label">
                <label for="accommodations">Current Accommodations/Modifications</label>
            </div>
            <div class="field-content">
                [textarea accommodations id:accommodations]
            </div>
        </div>

        <div class="field type-select is-required">
            <div class="field-label">
                <label for="iep-status">IEP or 504 Plan Status</label>
            </div>
            <div class="field-content">
                [select* iep_status id:iep-status "Has IEP" "Has 504 Plan" "Neither" "Don\'t Know"]
            </div>
        </div>

        <div class="field type-textarea">
            <div class="field-label">
                <label for="student-strengths">Student Strengths</label>
            </div>
            <div class="field-content">
                [textarea student_strengths id:student-strengths]
            </div>
        </div>

        <div class="field type-textarea">
            <div class="field-label">
                <label for="recommendations">Recommendations</label>
            </div>
            <div class="field-content">
                [textarea recommendations id:recommendations]
            </div>
        </div>

        <div class="field type-textarea">
            <div class="field-label">
                <label for="additional-comments">Additional Comments</label>
            </div>
            <div class="field-content">
                [textarea additional_comments id:additional-comments]
            </div>
        </div>
    </div>
</div>

<div class="field-group group-submit">
    <div class="group-fields group-columns-3">
        <div class="field type-submit">
            [submit "Submit Report" class:button]
        </div>
    </div>
</div>';
}

/**
 * Get PCP Referral form template (condensed)
 */
function gcm_get_pcp_referral_form_template() {
    return '<div class="field-group-heading">
    <h3 class="title">Primary Care Physician Referral Form</h3>
    <p>Please complete this form to refer a patient for developmental evaluation.</p>
</div>

<div class="field-group field-list">
    <div class="group-label">Patient Information</div>
    <div class="group-fields group-columns-3">
        <div class="field type-text is-required">
            <div class="field-label">
                <label for="patient-first-name">Patient\'s First Name</label>
            </div>
            <div class="field-content">
                [text* patient_first_name id:patient-first-name class:letters_space]
            </div>
        </div>

        <div class="field type-text is-required">
            <div class="field-label">
                <label for="patient-last-name">Patient\'s Last Name</label>
            </div>
            <div class="field-content">
                [text* patient_last_name id:patient-last-name class:letters_space]
            </div>
        </div>

        <div class="field type-date is-required">
            <div class="field-label">
                <label for="patient-dob">Patient\'s Date of Birth</label>
            </div>
            <div class="field-content">
                [date* patient_dob id:patient-dob]
            </div>
        </div>
    </div>
</div>

<div class="field-group field-list">
    <div class="group-label">Referring Physician Information</div>
    <div class="group-fields group-columns-2">
        <div class="field type-text is-required">
            <div class="field-label">
                <label for="physician-name">Physician Name</label>
            </div>
            <div class="field-content">
                [text* physician_name id:physician-name class:letters_space]
            </div>
        </div>

        <div class="field type-text is-required">
            <div class="field-label">
                <label for="practice-name">Practice Name</label>
            </div>
            <div class="field-content">
                [text* practice_name id:practice-name]
            </div>
        </div>

        <div class="field type-tel is-required">
            <div class="field-label">
                <label for="physician-phone">Phone</label>
            </div>
            <div class="field-content">
                [tel* physician_phone id:physician-phone]
            </div>
        </div>

        <div class="field type-tel is-required">
            <div class="field-label">
                <label for="physician-fax">Fax</label>
            </div>
            <div class="field-content">
                [tel* physician_fax id:physician-fax]
            </div>
        </div>

        <div class="field type-email is-required">
            <div class="field-label">
                <label for="physician-email">Email</label>
            </div>
            <div class="field-content">
                [email* physician_email id:physician-email]
            </div>
        </div>

        <div class="field type-textarea">
            <div class="field-label">
                <label for="practice-address">Practice Address</label>
            </div>
            <div class="field-content">
                [textarea practice_address id:practice-address]
            </div>
        </div>
    </div>
</div>

<div class="field-group-heading">
    <h3 class="title">Referral Information</h3>
</div>

<div class="field-group field-list">
    <div class="group-fields group-columns-1">
        <div class="field type-select is-required">
            <div class="field-label">
                <label for="referral-reason">Reason for Referral</label>
            </div>
            <div class="field-content">
                [select* referral_reason id:referral-reason "ADHD Evaluation" "Autism Spectrum Evaluation" "Learning Disability Assessment" "Developmental Delay Evaluation" "Behavioral Assessment" "Speech/Language Concerns" "Other"]
            </div>
        </div>

        <div class="field type-textarea is-required">
            <div class="field-label">
                <label for="chief-complaint">Chief Complaint / Reason for Referral</label>
            </div>
            <div class="field-content">
                [textarea* chief_complaint id:chief-complaint]
            </div>
        </div>

        <div class="field type-select is-required">
            <div class="field-label">
                <label for="urgency-level">Urgency Level</label>
            </div>
            <div class="field-content">
                [select* urgency_level id:urgency-level "Routine" "Urgent" "Emergent"]
            </div>
        </div>

        <div class="field type-select is-required">
            <div class="field-label">
                <label for="preferred-timeframe">Preferred Appointment Timeframe</label>
            </div>
            <div class="field-content">
                [select* preferred_timeframe id:preferred-timeframe "Within 1 week" "Within 2 weeks" "Within 1 month" "Within 3 months" "No preference"]
            </div>
        </div>
    </div>
</div>

<div class="field-group-heading">
    <h3 class="title">Parent/Guardian Contact Information</h3>
    <p>For scheduling purposes</p>
</div>

<div class="field-group field-list">
    <div class="group-fields group-columns-2">
        <div class="field type-text is-required">
            <div class="field-label">
                <label for="parent-name">Parent/Guardian Name</label>
            </div>
            <div class="field-content">
                [text* parent_name id:parent-name class:letters_space]
            </div>
        </div>

        <div class="field type-tel is-required">
            <div class="field-label">
                <label for="parent-phone">Parent/Guardian Phone</label>
            </div>
            <div class="field-content">
                [tel* parent_phone id:parent-phone]
            </div>
        </div>

        <div class="field type-email">
            <div class="field-label">
                <label for="parent-email">Parent/Guardian Email</label>
            </div>
            <div class="field-content">
                [email parent_email id:parent-email]
            </div>
        </div>
    </div>
</div>

<div class="field-group-heading">
    <h3 class="title">Insurance Information</h3>
</div>

<div class="field-group field-list">
    <div class="group-fields group-columns-1">
        <div class="field type-text">
            <div class="field-label">
                <label for="insurance-name">Insurance Provider Name</label>
            </div>
            <div class="field-content">
                [text insurance_name id:insurance-name placeholder "e.g., Blue Cross, Aetna, UnitedHealthcare"]
            </div>
        </div>
    </div>
</div>

<div class="field-group group-submit">
    <div class="group-fields group-columns-3">
        <div class="field type-submit">
            [submit "Submit Referral" class:button]
        </div>
    </div>
</div>';
}

/**
 * Run form installation on plugin activation
 */
register_activation_hook( GCM_PATH . '/developmentalondemand.php', 'gcm_install_evaluation_forms' );

/**
 * Add admin page to manually install/reinstall forms
 */
add_action( 'admin_menu', function() {
    add_submenu_page(
        'acf-gcm-settings',
        __( 'Install Forms', 'gcm' ),
        __( 'Install Forms', 'gcm' ),
        'manage_options',
        'gcm-install-forms',
        'gcm_display_install_forms_page'
    );
}, 11 ); // Priority 11 to run after main menu is created

/**
 * Display Install Forms admin page
 */
function gcm_display_install_forms_page() {
    // Handle installation request
    $install_result = null;
    if ( isset( $_POST['gcm_install_forms'] ) && check_admin_referer( 'gcm_install_forms', 'gcm_install_nonce' ) ) {
        $install_result = gcm_install_evaluation_forms();
    }

    ?>
    <div class="wrap">
        <h1>Install Evaluation Forms</h1>

        <?php if ( $install_result ): ?>
            <div class="notice notice-<?php echo $install_result['success'] ? 'success' : 'error'; ?> is-dismissible">
                <p><strong><?php echo esc_html( $install_result['message'] ); ?></strong></p>
                <?php if ( ! empty( $install_result['details']['forms_created'] ) ): ?>
                    <ul>
                        <?php foreach ( $install_result['details']['forms_created'] as $form ): ?>
                            <li>✓ <?php echo esc_html( $form ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>Automatic Form Installation</h2>
            <p>Click the button below to automatically create Contact Form 7 forms for developmental evaluations.</p>

            <p><strong>Forms that will be created:</strong></p>
            <ul>
                <li>📋 Developmental Evaluation Intake</li>
                <li>📊 NICHQ Vanderbilt Assessment</li>
                <li>🏫 Teacher Report Form</li>
                <li>🩺 PCP Referral Form</li>
            </ul>

            <p><small><strong>Note:</strong> If forms already exist, they will be skipped. This is safe to run multiple times.</small></p>

            <form method="post" action="">
                <?php wp_nonce_field( 'gcm_install_forms', 'gcm_install_nonce' ); ?>
                <p>
                    <button type="submit" name="gcm_install_forms" class="button button-primary button-large">
                        Install Forms Now
                    </button>
                </p>
            </form>
        </div>

        <div class="card" style="margin-top: 20px;">
            <h2>Current Status</h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Form</th>
                        <th>Status</th>
                        <th>Form ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $forms_status = array(
                        'Developmental Evaluation' => 'gcm_developmental_eval_form_id',
                        'Vanderbilt Assessment' => 'gcm_vanderbilt_form_id',
                        'Teacher Report' => 'gcm_teacher_report_form_id',
                        'PCP Referral' => 'gcm_pcp_referral_form_id',
                    );

                    foreach ( $forms_status as $form_name => $option_key ):
                        $form_id = get_option( $option_key );
                        $exists = $form_id && get_post( $form_id );
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html( $form_name ); ?></strong></td>
                            <td>
                                <?php if ( $exists ): ?>
                                    <span style="color: green;">✓ Installed</span>
                                <?php else: ?>
                                    <span style="color: red;">✗ Not installed</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ( $exists ): ?>
                                    <code><?php echo esc_html( $form_id ); ?></code>
                                    <a href="<?php echo admin_url( 'admin.php?page=wpcf7&post=' . $form_id . '&action=edit' ); ?>">Edit</a>
                                <?php else: ?>
                                    <em>—</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
