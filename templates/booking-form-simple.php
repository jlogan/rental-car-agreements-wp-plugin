<?php
/**
 * Template: Simple Booking Form (Lead Capture for Modal)
 */
?>
<div class="rca-booking-form-container">
    <?php if ( $vehicle ) : 
        $img_url = has_post_thumbnail($vehicle->ID) ? get_the_post_thumbnail_url($vehicle->ID, 'large') : '';
        $w_rate = get_post_meta( $vehicle->ID, '_rca_weekly_rate', true ); 
    ?>
        <div class="rca-booking-header">
            <?php if($img_url): ?>
                <div class="rca-booking-image" style="background-image: url('<?php echo esc_url($img_url); ?>');"></div>
            <?php endif; ?>
            
            <div class="rca-booking-title">
                <h3><?php echo esc_html( $vehicle->post_title ); ?></h3>
                <?php if($w_rate): ?>
                    <div class="rca-booking-price">$<?php echo esc_html($w_rate); ?> <span class="per-week">/ week</span></div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <form action="#" method="POST" class="rca-booking-form" id="rca-car-rental-agreement-form" onsubmit="return false;" data-min-booking-days="<?php 
        $min_booking_days = 7; // Default
        if ( $vehicle && isset( $vehicle->ID ) ) {
            $min_booking_days = get_post_meta( $vehicle->ID, '_rca_min_booking_days', true );
            if ( empty( $min_booking_days ) ) {
                $min_booking_days = 7;
            }
        }
        echo esc_attr( $min_booking_days ); 
    ?>">
        <?php wp_nonce_field( 'rca_submit_booking_action', 'rca_booking_nonce' ); ?>
        <input type="hidden" name="vehicle_id" value="<?php echo esc_attr( $vehicle_id ); ?>">
        <input type="hidden" name="rca_is_simple_form" value="1">
        <input type="hidden" name="rca_submit_booking" value="1">
        
        <div class="rca-form-section">
            <h4>BOOKING REQUEST</h4>
            <p>Please fill out the form below to request a booking. We will contact you shortly to complete your rental agreement.</p>
        </div>

        <div class="rca-form-section">
            <h4>CONTACT INFORMATION</h4>
            <div class="rca-form-grid">
                <div class="rca-form-group">
                    <label for="rca_first_name">First Name <span class="rca-required-asterisk">*</span></label>
                    <input type="text" name="rca_first_name" id="rca_first_name" required minlength="1" maxlength="50" pattern="[A-Za-z\s\-']+" title="Please enter a valid first name">
                </div>
                <div class="rca-form-group">
                    <label for="rca_last_name">Last Name <span class="rca-required-asterisk">*</span></label>
                    <input type="text" name="rca_last_name" id="rca_last_name" required minlength="1" maxlength="50" pattern="[A-Za-z\s\-']+" title="Please enter a valid last name">
                </div>
                <div class="rca-form-group">
                    <label for="rca_email">Email <span class="rca-required-asterisk">*</span></label>
                    <input type="email" name="rca_email" id="rca_email" required maxlength="100" title="Please enter a valid email address">
                </div>
                <div class="rca-form-group">
                    <label for="rca_phone">Phone <span class="rca-required-asterisk">*</span></label>
                    <input type="tel" name="rca_phone" id="rca_phone" required>
                </div>
                <div class="rca-form-group full-width">
                    <label for="rca_street_address">Street Address <span class="rca-required-asterisk">*</span></label>
                    <input type="text" name="rca_street_address" id="rca_street_address" required>
                </div>
                <div class="rca-form-group">
                    <label for="rca_apt_unit">Apt/Unit</label>
                    <input type="text" name="rca_apt_unit" id="rca_apt_unit" maxlength="20">
                </div>
                <div class="rca-form-group">
                    <label for="rca_city">City <span class="rca-required-asterisk">*</span></label>
                    <input type="text" name="rca_city" id="rca_city" required maxlength="50">
                </div>
                <div class="rca-form-group">
                    <label for="rca_state">State <span class="rca-required-asterisk">*</span></label>
                    <select name="rca_state" id="rca_state" required>
                        <option value="">Select State</option>
                        <?php
                        $states = array(
                            'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas', 'CA' => 'California',
                            'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware', 'FL' => 'Florida', 'GA' => 'Georgia',
                            'HI' => 'Hawaii', 'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa',
                            'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
                            'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi', 'MO' => 'Missouri',
                            'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey',
                            'NM' => 'New Mexico', 'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio',
                            'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
                            'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah', 'VT' => 'Vermont',
                            'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming',
                            'DC' => 'District of Columbia', 'PR' => 'Puerto Rico', 'VI' => 'Virgin Islands', 'GU' => 'Guam', 'AS' => 'American Samoa'
                        );
                        foreach ( $states as $code => $name ) :
                            $selected = ( $code === 'GA' ) ? 'selected' : '';
                            echo '<option value="' . esc_attr( $code ) . '" ' . $selected . '>' . esc_html( $name ) . '</option>';
                        endforeach;
                        ?>
                    </select>
                </div>
                <div class="rca-form-group">
                    <label for="rca_license">Driver's License Number <span class="rca-required-asterisk">*</span></label>
                    <input type="text" name="rca_license" id="rca_license" required>
                </div>
                <div class="rca-form-group full-width">
                    <label for="rca_zip_code">Zip Code <span class="rca-required-asterisk">*</span></label>
                    <input type="text" name="rca_zip_code" id="rca_zip_code" required pattern="[0-9]{5}(-[0-9]{4})?" title="Please enter a valid zip code (12345 or 12345-6789)" maxlength="10">
                </div>
            </div>
        </div>

        <div class="rca-form-section">
            <h4>RENTAL DATES</h4>
            <div class="rca-form-grid">
                <div class="rca-form-group">
                    <label for="rca_start_date">Start Date <span class="rca-required-asterisk">*</span></label>
                    <input type="date" name="rca_start_date" id="rca_start_date" required min="<?php echo date('Y-m-d'); ?>" title="Start date cannot be before today">
                </div>
                <div class="rca-form-group">
                    <label for="rca_end_date">End Date <span class="rca-required-asterisk">*</span></label>
                    <input type="date" name="rca_end_date" id="rca_end_date" required min="<?php echo date('Y-m-d'); ?>" title="End date cannot be before today">
                </div>
            </div>
            <p><small>Please be advised that in the event of an extension of the rental term, we require a minimum of 2 days' notice prior to the scheduled return date.</small></p>
        </div>

        <button type="submit" class="rca-btn rca-submit-btn">Submit Booking Request</button>
    </form>
</div>
