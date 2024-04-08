jQuery(function() {

	const s = window.gcm_validation_settings || false;
	
	if ( ! s ) {
		console.log('GCM Validation: Settings not available, aborting');
		return;
	}
	
	const validation_methods = s.methods || []; // array( string $class, string[] $methods )
	const min_prefix = s.min_prefix || false;
	const max_prefix = s.max_prefix || false;
	const debug_mode = s.debug_mode || false;

	const $html = jQuery('html');
	const $header_desktop = jQuery('header.site-header');
	const $header_mobile = jQuery('.menu-header');

	// Show a console message if debug mode is enabled
	const debug_log = function() {
		if ( ! debug_mode ) return;
		if ( typeof console !== 'object' ) return;
		if ( typeof console.log !== 'function' ) return;

		let a = arguments;
		let prefix = '[GCM Validation]';

		switch( a.length ) {
			case 0: console.log( prefix ); break;
			case 1: console.log( prefix, a[0] ); break;
			case 2: console.log( prefix, a[0], a[1] ); break;
			case 3: console.log( prefix, a[0], a[1], a[2] ); break;
			case 4: console.log( prefix, a[0], a[1], a[2], a[3] ); break;
			case 5: console.log( prefix, a[0], a[1], a[2], a[3], a[4] ); break;
			case 6: console.log( prefix, a[0], a[1], a[2], a[3], a[4], a[5] ); break;
			default: console.log( prefix, a ); break;
		}
	}

	// Scrolls to a Y-offset of an element. This accounts for the sticky header and admin bar, if displayed.
	const scroll_to_element = function( $element ) {
		let $group = $element.closest('.field-group');
		if ( $group.length > 0 ) $element = $group;

		let y = $element.offset().top;

		// Adjust for admin bar, which puts margin top on the <html>
		y -= parseInt( $html.css('margin-top') );

		// Adjust for sticky header (desktop)
		if ( $header_desktop.css('display') !== 'none' && $header_desktop.css('position') === 'sticky' ) {
			y -= $header_desktop.outerHeight();
			y -= parseInt( $header_desktop.css('margin-top') );
		}

		// Adjust for sticky header (mobile)
		if ( $header_mobile.css('display') !== 'none' && $header_mobile.css('position') === 'sticky' ) {
			y -= $header_mobile.outerHeight();
			y -= parseInt( $header_mobile.css('margin-top') );
		}

		// Debug message to explain
		debug_log( 'Scrolling to element:', $element[0], y );

		// Scroll to the element
		jQuery('html, body').animate({
			scrollTop: y,
		}, 250);
	};

	// Checks if a field is required
	const is_field_required = function( $field ) {
		let is_required = false;

		if ( $field.prop('required') ) is_required = true;
		if ( $field.hasClass('wpcf7-validates-as-required') ) is_required = true;

		// Check if the field is hidden, which overrides it being required
		if ( ! $field.is(':visible') ) is_required = false;

		return is_required;
	};

	// Check a field for different validation types.
	// Return an array of validation methods as objects, each having a "type" property and possibly other data.
	const get_validation_methods = function( $field ) {
		let i = 0;
		let j = 0;
		let k = 0;
		let value = 0;
		let methods = [];
		let class_str = $field.attr('class');
		let classes = class_str ? class_str.split(' ') : [];
		
		// Check if any class starts with the "min_prefix"
		for ( i in classes ) {
			if ( classes[i].startsWith(min_prefix) ) {
				value = classes[i].replace(min_prefix, '');
				methods.push({
					type: 'minimum_length',
					min: value,
				});
				break;
			}
		}
		
		// Check if any class starts with the "max_prefix"
		for ( i in classes ) {
			if ( classes[i].startsWith(max_prefix) ) {
				value = classes[i].replace(max_prefix, '');
				methods.push({
					type: 'maximum_length',
					max: value,
				});
				break;
			}
		}

		// Check if any other validation classes are present, and add them as "patterns"
		let patterns = [];

		// Loop through each class on the element
		for ( i in classes ) {
			let class_name = classes[i]; // "letters_space", "digits"... see Form Validation menu.

			// Compare to each validation method from the settings page
			for ( j in validation_methods ) {
				let method_class = validation_methods[j]['class']; // "letters", "numbers", "spaces"
				let methods = validation_methods[j]['methods'];    // ["letters_space", "digits", "RegEx-3190"]

				// If the element has a validation class, add the methods to the list
				if ( class_name === method_class ) {
					for ( k in methods ) {
						patterns.push( methods[k] );
					}
				}
			}
		}

		// Check if the field is required
		if ( is_field_required( $field ) ) {
			methods.push({
				type: 'required',
			});
		}

		if ( patterns.length > 0 ) {
			methods.push({
				type: 'patterns',
				patterns: patterns,
			});
		}

		// Debug message to explain
		debug_log( 'Validation methods for field:', $field[0], methods );

		return methods;
	};

	// Validate a value against a list of patterns.
	const validate_patterns = function( value, patterns ) {
		if ( ! value || ! patterns || patterns.length < 1 ) {
			return false;
		}

		let reg_string = '';
		let desc = [];

		for ( let i in patterns ) {
			let name = patterns[i]; // letters, numbers, or spaces

			if ( name === 'letters' ) {

				reg_string += 'a-zA-Z';
				desc.push( 'letters' );

			}else if ( name === 'numbers' ) {

				reg_string += '0-9';
				desc.push( 'numbers' );

			}else if ( name === 'spaces' ) {

				reg_string += ' ';
				desc.push( 'spaces' );

			}
		}

		// Check if the value matches the patterns
		let reg = new RegExp('^[' + reg_string + ']+$');
		if ( reg.test(value) ) {
			return false; // No errors
		}

		// Join the "desc" parts into a string, such as: "Must contain only letters, numbers, and spaces."
		let error_string = 'Must contain only ' + desc.join(', ') + '.';
		let comma_pos = error_string.lastIndexOf(',');
		if ( comma_pos > -1 ) {
			error_string = error_string.substring(0, comma_pos) + ', and' + error_string.substring(comma_pos + 1);
		}

		// Debug message to explain
		debug_log( 'Validating patterns for value', {
			value:  value,
			regex_pattern: reg,
			patterns: patterns,
			error_string: error_string,
		});

		return error_string;
	};

	// Get validation errors for a field.
	// Returns an array of validation errors if any are found, or false if no errors are found.
	const get_validation_errors = function( $field ) {
		let i = 0;
		let errors = [];

		// Get field value
		let value = $field.val();

		// Get validation methods
		let methods = get_validation_methods( $field );

		// Check each method and add any errors
		for ( i in methods ) {
			let method = methods[i];

			switch( method.type ) {

				case 'required':
					if ( value === "" ) {
						errors.push( 'Please fill in the required field.' );
					}
					break;

				case 'minimum_length':
					if ( value === "" ) break;
					if ( value.length < method.min ) {
						errors.push( 'Minimum length: ' + method.min + ' characters' );
					}
					break;

				case 'maximum_length':
					if ( value === "" ) break;
					if ( value.length > method.max ) {
						errors.push( 'Maximum length: ' + method.max + ' characters' );
					}
					break;

				case 'patterns':
					if ( value === "" ) break;
					let msg = validate_patterns( value, method.patterns );
					if ( msg ) {
						errors.push( msg );
					}
					break;

				default:
					errors.push( 'Unknown validation method "' + method + '"' );
					if ( typeof console === 'object' && typeof console.error === 'function' ) {
						console.error( 'Unknown validation method "' + method + '" for field: ', $field[0] );
					}
					break;

			}
		}

		if ( errors.length < 1 ) {
			// No errors
			// Debug message to explain
			debug_log( 'Field is valid:', $field[0] );

			return false;
		}else{

			// Errors found
			// Debug message to explain
			debug_log( 'Validation errors for field:', {
				field: $field[0],
				errors: errors
			});

			return errors;
		}
	}

	// Get the CF7 field wrapper element, if it exists
	const get_cf7_field_wrapper = function( $field ) {
		let $wrapper = $field.closest('.wpcf7-form-control-wrap');

		if ( $wrapper.length > 0 ) {
			return $wrapper;
		}else{
			return false;
		}
	};

	// Display or clear validation errors for a field
	const display_validation_errors = function( $field, errors ) {
		let $wrapper = get_cf7_field_wrapper( $field );

		// Remove existing errors
		if ( $wrapper ) {
			$wrapper.find('.wpcf7-not-valid-tip').remove();
		}

		// Add new errors, if any
		if ( errors && errors.length > 0 ) {
			// Show all errors, or just one?
			// let error_message = errors.join('<br>');
			let error_message = errors[0];
			$wrapper.append('<span class="wpcf7-not-valid-tip" aria-hidden="true">' + error_message + '</span>');
		}
	};

	// Validates an entire form and shows any field errors
	// Returns true if valid, false otherwise
	const validate_form = function( $form ) {
		let $fields = $form.find(':input');
		let errors = [];

		// Validate each field and check if any errors are found
		$fields.each(function() {
			let $field = jQuery(this);
			let validation_errors = get_validation_errors( $field );

			display_validation_errors( $field, validation_errors );

			if ( validation_errors ) {
				errors.push({
					'field': $field,
					'errors': validation_errors,
				});
			}
		});

		// If no errors, return true
		if ( errors.length < 1 ) {
			// Debug message to explain
			debug_log( 'Form is valid:', $form[0] );
			return true;
		}

		// Scroll to the first error
		let $first_error = errors[0].field;
		let $scroll_to = get_cf7_field_wrapper( $first_error ) || $first_error;
		if ( $scroll_to ) {
			scroll_to_element( $scroll_to );
		}

		// Debug message to explain
		debug_log( 'Form is invalid:', {
			form: $form[0],
			first_element: $first_error[0],
			errors: errors
		});

		return false;
	};

	// When a field is changed, check for validation errors and display them.
	jQuery(document.body).on( 'change keyup blur', ':input', function(e) {
		// If keyup, ignore mouse events
		if ( e.type === 'keyup' && e.which >= 1 && e.which <= 31 ) return;

		let $field = jQuery(this);
		let validation_errors = get_validation_errors( $field );

		display_validation_errors( $field, validation_errors );
	});

	// When a form is submitted, check for validation errors and display them.
	// Prevent the form from submitting if any errors are found.
	jQuery(document.body).on( 'submit', 'form', function(e) {
		let $form = jQuery(this);

		if ( ! validate_form( $form ) ) {
			return false;
		}
	});

	// When a submit button is clicked for a form, perform validation.
	jQuery(document.body).on( 'click', 'form :submit', function(e) {
		let $form = jQuery(this);

		if ( ! validate_form( $form ) ) {
			return false;
		}
	});

	// Replace CF7's submit method with our own, while still performing theirs if ours passes.
	// This seems to be the only way to prevent CF7 from submitting the form when there are validation errors.
	if ( typeof wpcf7.submit === 'function' ) {
		const default_wpcf7_submit = wpcf7.submit;

		wpcf7.submit = function( t, e ) {
			let $form = jQuery(t);

			if ( ! validate_form( $form ) ) {
				return false;
			}

			return default_wpcf7_submit( t, e );
		};
	}
	
});