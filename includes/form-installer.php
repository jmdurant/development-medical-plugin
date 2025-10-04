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

        if ( $existing_form_id && get_post( $existing_form_id ) ) {
            $results['forms_skipped'][] = $form_data['title'] . ' (already exists)';
            continue;
        }

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
        } else {
            // Save form ID to options
            update_option( $form_data['option_key'], $form_id );
            $results['forms_created'][] = $form_data['title'] . ' (ID: ' . $form_id . ')';
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
    <div class="group-fields group-columns-3">
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
                [tel* phone_primary id:phone-primary class:digits]
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
    <div class="group-fields group-columns-3">
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
    <div class="group-label">Referring Physician</div>
    <div class="group-fields group-columns-3">
        <div class="field type-text is-required">
            <div class="field-label">
                <label for="physician-name">Your Name</label>
            </div>
            <div class="field-content">
                [text* physician_name id:physician-name class:letters_space]
            </div>
        </div>

        <div class="field type-email is-required">
            <div class="field-label">
                <label for="physician-email">Your Email</label>
            </div>
            <div class="field-content">
                [email* physician_email id:physician-email]
            </div>
        </div>

        <div class="field type-dropdown is-required">
            <div class="field-label">
                <label for="referral-reason">Reason for Referral</label>
            </div>
            <div class="field-content">
                [select* referral_reason id:referral-reason "ADHD Evaluation" "Autism Spectrum Evaluation" "Learning Disability Assessment" "Developmental Delay Evaluation" "Other"]
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
