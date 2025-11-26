<?php
/**
 * Template: Booking Form
 */
?>
<div class="rca-booking-form-container">
    <?php if ( isset( $_GET['rca_booking_success'] ) ) : ?>
        <div class="rca-alert rca-alert-success">
            Your booking request has been received successfully! We will contact you shortly.
        </div>
    <?php else : ?>
        
        <?php if ( $vehicle ) : ?>
            <div class="rca-selected-vehicle">
                <h3>Booking: <?php echo esc_html( $vehicle->post_title ); ?></h3>
                <?php 
                $w_rate = get_post_meta( $vehicle->ID, '_rca_weekly_rate', true ); 
                if($w_rate): ?>
                    <p class="price-highlight">$<?php echo esc_html($w_rate); ?> / week</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="rca-booking-form">
            <?php wp_nonce_field( 'rca_submit_booking_action', 'rca_booking_nonce' ); ?>
            <input type="hidden" name="vehicle_id" value="<?php echo esc_attr( $vehicle_id ); ?>">
            <input type="hidden" name="rca_submit_booking" value="1">

            <div class="rca-form-row">
                <div class="rca-form-group">
                    <label for="rca_fullname">Full Name *</label>
                    <input type="text" name="rca_fullname" id="rca_fullname" required>
                </div>
                <div class="rca-form-group">
                    <label for="rca_email">Email Address *</label>
                    <input type="email" name="rca_email" id="rca_email" required>
                </div>
            </div>

            <div class="rca-form-row">
                <div class="rca-form-group">
                    <label for="rca_phone">Phone Number *</label>
                    <input type="tel" name="rca_phone" id="rca_phone" required>
                </div>
                <div class="rca-form-group">
                    <label for="rca_license">Driver's License # *</label>
                    <input type="text" name="rca_license" id="rca_license" required>
                </div>
            </div>

            <div class="rca-form-group">
                <label for="rca_address">Current Address *</label>
                <textarea name="rca_address" id="rca_address" rows="2" required></textarea>
            </div>

            <div class="rca-form-row">
                <div class="rca-form-group">
                    <label for="rca_start_date">Desired Start Date *</label>
                    <input type="date" name="rca_start_date" id="rca_start_date" required>
                </div>
                <div class="rca-form-group">
                    <label for="rca_end_date">End Date (Optional)</label>
                    <input type="date" name="rca_end_date" id="rca_end_date">
                </div>
            </div>

            <div class="rca-form-group">
                <label for="rca_insurance">Insurance Option</label>
                <select name="rca_insurance" id="rca_insurance">
                    <option value="basic">Basic Coverage</option>
                    <option value="premium">Premium Coverage</option>
                    <option value="declined">I have my own insurance (Decline)</option>
                </select>
            </div>

            <div class="rca-form-group rca-checkbox">
                <label>
                    <input type="checkbox" name="rca_terms" required> 
                    I agree to the rental terms and conditions and authorize the verification of my information.
                </label>
            </div>

            <button type="submit" class="rca-btn">Submit Booking Request</button>
        </form>
    <?php endif; ?>
</div>

