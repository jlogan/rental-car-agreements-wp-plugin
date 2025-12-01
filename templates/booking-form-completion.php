<?php
/**
 * Template: Complete Booking Form (Pre-filled from lead)
 */
?>
<div class="rca-booking-form-container">
    <?php if ( isset( $_GET['completed'] ) && $_GET['completed'] == '1' ) : ?>
        <div class="rca-alert rca-alert-success" style="max-width: 800px; margin: 2rem auto; padding: 2rem; text-align: center;">
            <h3 style="color: #10b981; margin-bottom: 1rem;">Booking Completed Successfully!</h3>
            <p style="color: #cbd5e1; line-height: 1.6;">Your rental agreement has been completed. We will contact you shortly to confirm your rental.</p>
        </div>
    <?php else : ?>
        
        <?php if ( $vehicle ) : 
            $img_url = has_post_thumbnail($vehicle->ID) ? get_the_post_thumbnail_url($vehicle->ID, 'large') : '';
            $w_rate = get_post_meta( $vehicle->ID, '_rca_weekly_rate', true ); 
            $make = get_post_meta( $vehicle->ID, '_rca_make', true );
            $model = get_post_meta( $vehicle->ID, '_rca_model', true );
            $year = get_post_meta( $vehicle->ID, '_rca_year', true );
            $vin = get_post_meta( $vehicle->ID, '_rca_vin', true );
            $color = get_post_meta( $vehicle->ID, '_rca_color', true );
            $plate = get_post_meta( $vehicle->ID, '_rca_plate', true );
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

        <form action="<?php echo esc_url( $_SERVER['REQUEST_URI'] ); ?>" method="POST" class="rca-booking-form" id="rca-car-rental-agreement-form" onsubmit="return false;" data-min-booking-days="<?php 
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
            <input type="hidden" id="rca_booking_nonce" name="rca_booking_nonce" value="<?php echo wp_create_nonce( 'rca_submit_booking_action' ); ?>">
            <input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr( $_SERVER['REQUEST_URI'] ); ?>">
            <input type="hidden" name="vehicle_id" value="<?php echo esc_attr( $vehicle_id ); ?>">
            <input type="hidden" name="rca_submit_booking" value="1">
            <input type="hidden" name="rca_complete_booking" value="1">
            <input type="hidden" name="rca_booking_id" value="<?php echo esc_attr( $booking_id ); ?>">
            <!-- Hidden field - Renter ID will be generated on submission -->
            <input type="hidden" name="rca_renter_id" value="">

            <!-- Section 2: Identification of the Rental Vehicle -->
            <div class="rca-form-section">
                <h4>IDENTIFICATION OF THE RENTAL VEHICLE</h4>
                <p>Owner hereby agrees to rent a passenger vehicle identified as followed:</p>
                <div class="rca-vehicle-specs">
                    <div class="rca-spec-item">
                        <strong>Make and Model:</strong> <span><?php echo esc_html( $make . ' ' . $model ); ?></span>
                    </div>
                    <div class="rca-spec-item">
                        <strong>Year:</strong> <span><?php echo esc_html( $year ); ?></span>
                    </div>
                    <div class="rca-spec-item">
                        <strong>VIN:</strong> <span><?php echo esc_html( $vin ); ?></span>
                    </div>
                    <div class="rca-spec-item">
                        <strong>Color:</strong> <span><?php echo esc_html( $color ); ?></span>
                    </div>
                    <div class="rca-spec-item">
                        <strong>Plate Number:</strong> <span><?php echo esc_html( $plate ); ?></span>
                    </div>
                </div>
                <!-- Vehicle data will be fetched from backend during submission - no hidden fields needed -->
            </div>

            <!-- Section 3: Rental Term -->
            <div class="rca-form-section">
                <h4>RENTAL TERM</h4>
                <p>The Owner hereby agrees to rent the Vehicle to the Renter for the duration specified below, subject to the terms and conditions of this Rental Agreement:</p>
                <div class="rca-form-grid">
                    <div class="rca-form-group">
                        <label for="rca_start_date">Start Date <span class="rca-required-asterisk">*</span></label>
                        <input type="date" name="rca_start_date" id="rca_start_date" value="<?php echo esc_attr( $start_date ); ?>" required readonly style="opacity: 0.6; cursor: not-allowed;" onfocus="this.blur();" onkeydown="return false;">
                    </div>
                    <div class="rca-form-group">
                        <label for="rca_end_date">End Date <span class="rca-required-asterisk">*</span></label>
                        <input type="date" name="rca_end_date" id="rca_end_date" value="<?php echo esc_attr( $end_date ); ?>" required readonly style="opacity: 0.6; cursor: not-allowed;" onfocus="this.blur();" onkeydown="return false;">
                    </div>
                </div>
                <p><small>Please be advised that in the event of an extension of the rental term, we require a minimum of 2 days' notice prior to the scheduled return date.</small></p>
                <div class="rca-initial-field">
                    <label>
                        <input type="checkbox" name="rca_rental_term_initial" required> 
                        I acknowledge and agree to the rental term conditions. <span class="rca-required-asterisk">*</span>
                    </label>
                </div>
            </div>

            <!-- Section 4: Mileage -->
            <div class="rca-form-section">
                <h4>MILEAGE</h4>
                <p>The Rental Vehicle has unlimited mileage per day.</p>
                <div class="rca-initial-field">
                    <label>
                        <input type="checkbox" name="rca_mileage_initial" required> 
                        I acknowledge unlimited mileage per day. <span class="rca-required-asterisk">*</span>
                    </label>
                </div>
            </div>

            <!-- Section 5: Rental Fees -->
            <div class="rca-form-section">
                <h4>RENTAL FEES</h4>
                <p>The Renter shall remit rental fees to the Owner in accordance with the following payment schedule:</p>
                <div class="rca-vehicle-specs">
                    <div class="rca-spec-item">
                        <strong>Base fee (weekly):</strong> <span>$<?php echo esc_html( $w_rate ); ?></span>
                    </div>
                </div>
                <!-- Base fee will be fetched from backend during submission -->
                <p><strong>Payment Terms:</strong> Weekly payments shall be due every Wednesday. First payment is due once the rental agreement is signed by the client. All payments are due every Wednesday. Should the Renter fail to remit payment by 11:59PM EST on the due date, a late fee of $50 shall be assessed. In the event payment is not received within 24 hours of the due date; Owner reserves the right to disable and/or repossess the Rental Vehicle through the services of a security company.</p>
                <div class="rca-initial-field">
                    <label>
                        <input type="checkbox" name="rca_rental_fees_initial" required> 
                        I acknowledge and agree to the rental fees and payment schedule. <span class="rca-required-asterisk">*</span>
                    </label>
                </div>
            </div>

            <!-- Section 6: Loss of Keys -->
            <div class="rca-form-section">
                <h4>LOSS OF KEYS</h4>
                <p>If the renter loses the key provided by the rental company, they are responsible for a $400 replacement fee. If the renter locks the key inside the car, they will be charged $185 for the rental company to send a locksmith to unlock the vehicle.</p>
                <div class="rca-initial-field">
                    <label>
                        <input type="checkbox" name="rca_keys_initial" required> 
                        I acknowledge the key replacement fees. <span class="rca-required-asterisk">*</span>
                    </label>
                </div>
            </div>

            <!-- Section 7: Responsibility for Damage or Loss -->
            <div class="rca-form-section">
                <h4>RESPONSIBILITY FOR DAMAGE OR LOSS; REPORTING TO POLICE</h4>
                <p>You are responsible for damage to, or loss or theft of the Vehicle, which includes the cost of repair, or the actual cash retail value of the Vehicle on the date of the loss if it is not repairable or if we elect not to repair it, plus loss of use, diminished value of the Vehicle caused by damage to it or repair of it, and our administrative expenses incurred processing the insurance claim, whether or not you are at fault. You must report all accidents or incidents of theft and vandalism to us, and the police, as soon as you discover them.</p>
                <div class="rca-initial-field">
                    <label>
                        <input type="checkbox" name="rca_damage_responsibility_initial" required> 
                        I acknowledge my responsibility for damage or loss. <span class="rca-required-asterisk">*</span>
                    </label>
                </div>
            </div>

            <!-- Section 8: Insurance Coverage -->
            <div class="rca-form-section">
                <h4>INSURANCE COVERAGE</h4>
                <p>Please read each insurance option below &amp; initial beside the one you agree to. N/A the other 3 options.</p>
                
                <div class="rca-insurance-options">
                    <div class="rca-insurance-option">
                        <label>
                            <input type="radio" name="rca_insurance_option" value="option1" required> 
                            I agree to provide your own insurance covering you, us, &amp; the vehicle in case of an accident. You are responsible for all damage or loss you cause to others. (Note: you are required to make the rental company a interested party on the policy for verification purposes of coverage &amp; any changes that could occur to policy)
                        </label>
                    </div>
                    <div class="rca-insurance-option">
                        <label>
                            <input type="radio" name="rca_insurance_option" value="option2" required> 
                            I have decided to provide liability insurance &amp; purchase RENTERS COLLISION PROTECTION available through this car rental company for $20 per day, including a service charge. I understand that this product will pay for any collision damage done to this rented vehicle up to a maximum $20,000 with a $250 deductible, as long as I or any 'Authorized Drivers' do not violate this contract. I also understand that the purchase of the RCP is non-refundable, even if the rental vehicle is returned early.
                        </label>
                    </div>
                    <div class="rca-insurance-option">
                        <label>
                            <input type="radio" name="rca_insurance_option" value="option3" required> 
                            I agree to allow the rental car company to create a personalized insurance policy covering you, us, &amp; the vehicle in case of an accident.
                        </label>
                    </div>
                    <div class="rca-insurance-option">
                        <label>
                            <input type="radio" name="rca_insurance_option" value="option4" required> 
                            The insurance is included in the vehicles price
                        </label>
                    </div>
                </div>
                <div class="rca-initial-field">
                    <label>
                        <input type="checkbox" name="rca_insurance_initial" required> 
                        I have selected and initialed my insurance option above. <span class="rca-required-asterisk">*</span>
                    </label>
                </div>
            </div>

            <!-- Section 9: Insurance Claims -->
            <div class="rca-form-section">
                <h4>INSURANCE CLAIMS</h4>
                <p>In the event that the Rental Vehicle is damaged or destroyed while it is in the possession of Renter, Renter shall be responsible for paying the required insurance deductible in the amount of $1000, in the event that a claim is made for the damages. If a claim is not filed, Renter agrees to pay Owner directly for any repairs or damages incurred to the Rental Vehicle.</p>
                <div class="rca-initial-field">
                    <label>
                        <input type="checkbox" name="rca_insurance_claims_initial" required> 
                        I acknowledge the insurance claims deductible. <span class="rca-required-asterisk">*</span>
                    </label>
                </div>
            </div>

            <!-- Section 10: Indemnification -->
            <div class="rca-form-section">
                <h4>INDEMNIFICATION</h4>
                <p>The Renter hereby agrees to indemnify, defend, and hold harmless the Rental Company, its owner, employees, and affiliates from any and all claims, demands, causes of action, losses, damages, and expenses, including reasonable attorney's fees, arising out of or in connection with the Renter's use or operation of the Rental Vehicle during the rental period. The Renter shall assume full responsibility and pay for any parking tickets, moving violations, tolls, or other citations received while in possession of the Rental Vehicle, and shall promptly notify the Owner of any such citations. The indemnification also includes any attorney fees necessarily incurred by the Owner for these purposes. Furthermore, the Rental Company (Owner) shall not be liable for any loss, damage, or injury to the Renter or any third party arising from the use of the Rental Vehicle, whether caused by the Renter's negligence or otherwise. Renter agrees by initialing document and signing that Renter or any third party cannot take any legal action against PNS GLOBAL RESOURCES L.L.C for any loss, damage, or injury to the Renter or any third party arising from the use of the Rental Vehicle, whether caused by the Renter's negligence or otherwise.</p>
                <div class="rca-initial-field">
                    <label>
                        <input type="checkbox" name="rca_indemnification_initial" required> 
                        I acknowledge and agree to the indemnification terms. <span class="rca-required-asterisk">*</span>
                    </label>
                </div>
            </div>

            <!-- Section 11: Acknowledgment of Risk -->
            <div class="rca-form-section">
                <h4>ACKNOWLEDGMENT OF RISK</h4>
                <p>Renter acknowledges that the use, operation, or possession of the rented equipment/property ("Rental Item(s)") involves inherent risks that may result in injury, death, or property damage. Renter agrees to assume all risks associated with the use of the Rental Item(s), whether known or unknown, foreseeable or unforeseeable.</p>
                <div class="rca-initial-field">
                    <label>
                        <input type="checkbox" name="rca_risk_acknowledgment_initial" required> 
                        I acknowledge the risks. <span class="rca-required-asterisk">*</span>
                    </label>
                </div>
            </div>

            <!-- Section 12: Release & Waiver -->
            <div class="rca-form-section">
                <h4>RELEASE &amp; WAIVER</h4>
                <p>In consideration of being permitted to rent and use the Rental Item(s), Renter voluntarily and knowingly releases, waives, and discharges the Company PNS GLOBAL RESOURCES L.L.C from any and all liability, claims, demands, actions, or causes of action arising out of or related to any loss, damage, or injury, including death, that may be sustained while using, transporting, or in any way engaging with the Rental Item(s), whether caused by the Company's negligence or otherwise.</p>
                <div class="rca-initial-field">
                    <label>
                        <input type="checkbox" name="rca_release_waiver_initial" required> 
                        I acknowledge the release and waiver. <span class="rca-required-asterisk">*</span>
                    </label>
                </div>
            </div>

            <!-- Section 13: Car Rental Violation Charges -->
            <div class="rca-form-section">
                <h4>CAR RENTAL VIOLATION CHARGES</h4>
                <ul class="rca-violation-list">
                    <li><strong>Fuel Level:</strong> If the gas tank is returned below the level at which it was provided, a charge of $50 will be applied.</li>
                    <li><strong>Vehicle Cleanliness:</strong> If the vehicle is returned extremely dirty, an additional cleaning fee of $100 will be applied.</li>
                    <li><strong>Tire Damage:</strong> Any damage to the tires caused during the rental period will incur a charge of $120 per tire.</li>
                    <li><strong>Rim Damage:</strong> Any rash or damage to the rims will result in a charge of $400 per rim.</li>
                    <li><strong>Spills:</strong> Any spills inside the vehicle will result in a cleaning charge of $50.</li>
                    <li><strong>Smoking:</strong> This Vehicle is a NON-SMOKING VEHICLE. Absolutely NO SMOKING and if there is any evidence of smoking or smell there will be a $150 smoking fee.</li>
                </ul>
                <p>Please ensure the vehicle is returned in the same condition as when it was rented to avoid these charges.</p>
                <div class="rca-initial-field">
                    <label>
                        <input type="checkbox" name="rca_violation_charges_initial" required> 
                        I acknowledge the violation charges. <span class="rca-required-asterisk">*</span>
                    </label>
                </div>
            </div>

            <!-- Section 14: Vehicle Condition and Maintenance -->
            <div class="rca-form-section">
                <h4>VEHICLE CONDITION AND MAINTENANCE</h4>
                <p>Owner represents and warrants that, to the best of Owner's knowledge, the Rental Vehicle is in good condition and is safe for ordinary operation. Prior to taking possession of the Rental Vehicle, Renter has the opportunity to inspect the vehicle for any existing damages to the vehicle. For long-term renters, Owner shall cover basic maintenance such as oil changes, wipers, air filters, and cabin filters. However, Renter shall be responsible for any damages, such as blown tires or damaged wheels, and associated repairs. All maintenance and repairs shall be conducted by mobile mechanic. For all long-term renters, a mandatory safety inspection shall be conducted every 30-35 days.</p>
                <div class="rca-initial-field">
                    <label>
                        <input type="checkbox" name="rca_vehicle_condition_initial" required> 
                        I acknowledge vehicle condition and maintenance terms. <span class="rca-required-asterisk">*</span>
                    </label>
                </div>
            </div>

            <!-- Section 15: Check In & Check Out Process -->
            <div class="rca-form-section">
                <h4>CHECK IN &amp; CHECK OUT PROCESS</h4>
                <p>A comprehensive inspection of the vehicle's condition is mandatory before &amp; after rental period. Before the trip begins, a meticulous assessment of the vehicle's state is conducted in the presence of the customer to document any pre-existing damages or issues. After the trip ends, another detailed inspection occurs with the customer to evaluate the vehicle's condition post-trip. It's crucial that the customer remains present during inspection to address any potential damages or discrepancies that may have occurred during their use of the vehicle. If there are any damages done, the renter is responsible for $1000 as an additional charge. This process ensures transparency and fairness regarding the vehicle's condition and any associated costs.</p>
                <div class="rca-initial-field">
                    <label>
                        <input type="checkbox" name="rca_checkin_checkout_initial" required> 
                        I acknowledge the check-in and check-out process. <span class="rca-required-asterisk">*</span>
                    </label>
                </div>
            </div>

            <!-- Section 16: Free Will Clause -->
            <div class="rca-form-section">
                <h4>FREE WILL CLAUSE</h4>
                <p>PNS GLOBAL RESOURCES L.L.C affirms the free will of its customers to enter into rental agreements voluntarily. Customers acknowledge that they are entering into contracts of their own accord, without coercion, and with a clear understanding of the terms and conditions outlined in the agreement.</p>
                <div class="rca-initial-field">
                    <label>
                        <input type="checkbox" name="rca_free_will_initial" required> 
                        I acknowledge, understand, and agree. <span class="rca-required-asterisk">*</span>
                    </label>
                </div>
            </div>

            <!-- Section 17: Discriminatory Clause -->
            <div class="rca-form-section">
                <h4>DISCRIMINATORY CLAUSE</h4>
                <p>PNS GLOBAL RESOURCES L.L.C strictly prohibits any form of discrimination against customers based on race, color, religion, sex, national origin, disability, or any other protected characteristic. The agency is committed to providing equal access and opportunities to all customers, ensuring a fair and inclusive rental experience for everyone.</p>
                <div class="rca-initial-field">
                    <label>
                        <input type="checkbox" name="rca_discriminatory_initial" required> 
                        I agree that I was not discriminated against in any way. <span class="rca-required-asterisk">*</span>
                    </label>
                </div>
            </div>

            <!-- Section 18: ECPA Clause -->
            <div class="rca-form-section">
                <h4>ECPA "ELECTRONICALLY FUNDED PAYMENT" CLAUSE</h4>
                <p>PNS GLOBAL RESOURCES L.L.C, in compliance with the Electronic Communications Privacy Act (ECPA), acknowledges that customer receipts of payment, whether in electronic or written form, shall be treated with the utmost confidentiality. The agency commits to safeguarding the privacy of payment information and will not disclose, sell, or share such information with third parties without the express consent of the customer, except as required by law.</p>
                <div class="rca-initial-field">
                    <label>
                        <input type="checkbox" name="rca_ecpa_initial" required> 
                        I acknowledge, understand, and agree. <span class="rca-required-asterisk">*</span>
                    </label>
                </div>
            </div>

            <!-- Section 19: Opt-In and Opt-Out Clause -->
            <div class="rca-form-section">
                <h4>OPT-IN AND OPT-OUT CLAUSE</h4>
                <p>By signing this agreement, you acknowledge that you may be offered optional services such as insurance upgrades, roadside assistance, or GPS rental. You may choose to opt in to any of these services at the time of rental. All optional services selected will be clearly itemized on your rental agreement and are subject to applicable fees and terms.</p>
                <p>In addition, you may opt in to receive marketing communications from PNS GLOBAL RESOURCES L.L.C, including promotions, discounts, and service updates, via email, text message, or phone.</p>
                <div class="rca-checkbox-field">
                    <label>
                        <input type="checkbox" name="rca_marketing_optin"> 
                        I opt in to receive marketing communications
                    </label>
                </div>
                <div class="rca-initial-field">
                    <label>
                        <input type="checkbox" name="rca_optin_optout_initial" required> 
                        I acknowledge the opt-in and opt-out clause. <span class="rca-required-asterisk">*</span>
                    </label>
                </div>
            </div>

            <!-- Section 20: Arbitration Agreement -->
            <div class="rca-form-section">
                <h4>ARBITRATION AGREEMENT</h4>
                <p>By entering into a contract with PNS GLOBAL RESOURCES L.L.C, the customer agrees that any disputes or claims arising out of or in connection with the contract shall be resolved through binding arbitration in accordance with the rules of the American Arbitration Association. The customer further agrees that, in the event of a breach of contract, PNS GLOBAL RESOURCES L.L.C, retains the right to pursue legal action in small claims court or any appropriate venue to seek damages or injunctive relief as permitted by law.</p>
                <div class="rca-initial-field">
                    <label>
                        <input type="checkbox" name="rca_arbitration_initial" required> 
                        I acknowledge, understand, and agree. <span class="rca-required-asterisk">*</span>
                    </label>
                </div>
            </div>

            <!-- Section 21: Breach Of Contract -->
            <div class="rca-form-section">
                <h4>BREACH OF CONTRACT</h4>
                <p>Breach of contract includes but is not limited to:</p>
                <ul class="rca-violation-list">
                    <li>Failure to Pay: Non-payment or late payment for rental services.</li>
                    <li>Unauthorized Use: Using the rental vehicle for purposes not specified in the agreement.</li>
                    <li>Failure to Return: Not returning the rental vehicle by the agreed-upon date and time.</li>
                    <li>Negligence or Misuse: Neglecting or misusing the rental vehicle, causing damage beyond normal wear and tear.</li>
                    <li>Unauthorized Drivers: Allowing an unauthorized person to drive the rental vehicle.</li>
                    <li>Violation of Traffic Laws: Committing traffic violations or engaging in illegal activities with the rental vehicle.</li>
                    <li>Subleasing or Unauthorized Transfers: Subleasing the rental vehicle to another party without proper authorization.</li>
                    <li>Failure to Comply with Policies: Violating any specific policies outlined in the rental agreement, such as smoking or pet policies.</li>
                    <li>Providing False Information: Providing false information during the rental process or on the rental agreement.</li>
                </ul>
                <div class="rca-initial-field">
                    <label>
                        <input type="checkbox" name="rca_breach_contract_initial" required> 
                        I acknowledge the breach of contract terms. <span class="rca-required-asterisk">*</span>
                    </label>
                </div>
            </div>

            <!-- Section 22: Your Property -->
            <div class="rca-form-section">
                <h4>YOUR PROPERTY</h4>
                <p>You release us, our agents and employees from all claims for loss of, or damage to, your personal property or that of any other person, that we received, handled or stored, or that was left or carried in or on the Vehicle or in any service vehicle or in our offices, whether or not the loss or damage was caused by our negligence or was otherwise our responsibility.</p>
                <div class="rca-initial-field">
                    <label>
                        <input type="checkbox" name="rca_your_property_initial" required> 
                        I acknowledge the property release. <span class="rca-required-asterisk">*</span>
                    </label>
                </div>
            </div>

            <!-- Section 23: Returning Vehicle -->
            <div class="rca-form-section">
                <h4>RETURNING VEHICLE</h4>
                <p>If the renter returns the vehicle mid-week (Wednesday) your payment for the week will be prorated (you will owe half of weekly rate). DEPOSIT IF PAID ($200 WILL BE RETURNED UPON DROP OFF). If the renter was to return the vehicle any day after (Wednesday) will be responsible for paying the weekly rate.</p>
                <div class="rca-initial-field">
                    <label>
                        <input type="checkbox" name="rca_returning_vehicle_initial" required> 
                        I acknowledge the returning vehicle terms. <span class="rca-required-asterisk">*</span>
                    </label>
                </div>
            </div>

            <!-- Renter's Information -->
            <div class="rca-form-section">
                <h4>RENTER'S INFORMATION</h4>
                <?php
                // Parse fullname into first and last name
                $name_parts = explode( ' ', trim( $fullname ), 2 );
                $first_name = isset( $name_parts[0] ) ? $name_parts[0] : $fullname;
                $last_name = isset( $name_parts[1] ) ? $name_parts[1] : '';
                
                // Parse address into components (try to extract from stored address)
                $street_address = $address;
                $apt_unit = '';
                $city = '';
                $state = $driver_state ?: '';
                $zip_code = '';
                
                // Try to parse address if it contains newlines or commas
                if ( $address ) {
                    $address_lines = preg_split( '/[\r\n]+/', $address );
                    if ( count( $address_lines ) >= 1 ) {
                        $street_address = trim( $address_lines[0] );
                    }
                    if ( count( $address_lines ) >= 2 ) {
                        $city_state_zip = trim( $address_lines[1] );
                        // Try to extract city, state, zip from second line
                        if ( preg_match( '/^(.+?),\s*([A-Z]{2})\s+(\d{5}(-\d{4})?)$/', $city_state_zip, $matches ) ) {
                            $city = $matches[1];
                            $state = $matches[2];
                            $zip_code = $matches[3];
                        }
                    }
                }
                
                // Get individual address fields from meta if they exist (for new submissions)
                $stored_street = get_post_meta( $booking_id, '_rca_street_address', true );
                $stored_apt = get_post_meta( $booking_id, '_rca_apt_unit', true );
                $stored_city = get_post_meta( $booking_id, '_rca_city', true );
                $stored_state = get_post_meta( $booking_id, '_rca_state', true );
                $stored_zip = get_post_meta( $booking_id, '_rca_zip_code', true );
                
                if ( $stored_street ) $street_address = $stored_street;
                if ( $stored_apt ) $apt_unit = $stored_apt;
                if ( $stored_city ) $city = $stored_city;
                if ( $stored_state ) $state = $stored_state;
                if ( $stored_zip ) $zip_code = $stored_zip;
                
                // Get first/last name from meta if they exist
                $stored_first = get_post_meta( $booking_id, '_rca_first_name', true );
                $stored_last = get_post_meta( $booking_id, '_rca_last_name', true );
                if ( $stored_first ) $first_name = $stored_first;
                if ( $stored_last ) $last_name = $stored_last;
                ?>
                <div class="rca-form-grid">
                    <div class="rca-form-group">
                        <label for="rca_first_name">First Name <span class="rca-required-asterisk">*</span></label>
                        <input type="text" name="rca_first_name" id="rca_first_name" value="<?php echo esc_attr( $first_name ); ?>" required readonly style="opacity: 0.6; cursor: not-allowed;">
                    </div>
                    <div class="rca-form-group">
                        <label for="rca_last_name">Last Name <span class="rca-required-asterisk">*</span></label>
                        <input type="text" name="rca_last_name" id="rca_last_name" value="<?php echo esc_attr( $last_name ); ?>" required readonly style="opacity: 0.6; cursor: not-allowed;">
                    </div>
                    <div class="rca-form-group full-width">
                        <label for="rca_street_address">Street Address <span class="rca-required-asterisk">*</span></label>
                        <input type="text" name="rca_street_address" id="rca_street_address" value="<?php echo esc_attr( $street_address ); ?>" required readonly style="opacity: 0.6; cursor: not-allowed;">
                    </div>
                    <div class="rca-form-group">
                        <label for="rca_apt_unit">Apt/Unit</label>
                        <input type="text" name="rca_apt_unit" id="rca_apt_unit" value="<?php echo esc_attr( $apt_unit ); ?>" readonly style="opacity: 0.6; cursor: not-allowed;">
                    </div>
                    <div class="rca-form-group">
                        <label for="rca_city">City <span class="rca-required-asterisk">*</span></label>
                        <input type="text" name="rca_city" id="rca_city" value="<?php echo esc_attr( $city ); ?>" required readonly style="opacity: 0.6; cursor: not-allowed;">
                    </div>
                    <div class="rca-form-group">
                        <label for="rca_state">State <span class="rca-required-asterisk">*</span></label>
                        <select name="rca_state" id="rca_state" required disabled style="opacity: 0.6; cursor: not-allowed;">
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
                                $selected = ( $code === $state ) ? 'selected' : '';
                                echo '<option value="' . esc_attr( $code ) . '" ' . $selected . '>' . esc_html( $name ) . '</option>';
                            endforeach;
                            ?>
                        </select>
                        <input type="hidden" name="rca_state" value="<?php echo esc_attr( $state ); ?>">
                    </div>
                    <div class="rca-form-group">
                        <label for="rca_zip_code">Zip Code <span class="rca-required-asterisk">*</span></label>
                        <input type="text" name="rca_zip_code" id="rca_zip_code" value="<?php echo esc_attr( $zip_code ); ?>" required readonly style="opacity: 0.6; cursor: not-allowed;">
                    </div>
                    <div class="rca-form-group full-width">
                        <label for="rca_license">Driver's License Number <span class="rca-required-asterisk">*</span></label>
                        <input type="text" name="rca_license" id="rca_license" value="<?php echo esc_attr( $license ); ?>" required readonly style="opacity: 0.6; cursor: not-allowed;">
                    </div>
                    <div class="rca-form-group">
                        <label for="rca_phone">Phone <span class="rca-required-asterisk">*</span></label>
                        <input type="tel" name="rca_phone" id="rca_phone" value="<?php echo esc_attr( $phone ); ?>" required readonly style="opacity: 0.6; cursor: not-allowed;">
                    </div>
                    <div class="rca-form-group">
                        <label for="rca_email">Email <span class="rca-required-asterisk">*</span></label>
                        <input type="email" name="rca_email" id="rca_email" value="<?php echo esc_attr( $email ); ?>" required readonly style="opacity: 0.6; cursor: not-allowed;" maxlength="100" title="Please enter a valid email address">
                    </div>
                </div>
            </div>

            <!-- Section 24: Out of State Driving -->
            <div class="rca-form-section">
                <h4>OUT OF STATE DRIVING</h4>
                <p>Out of state driving is strictly prohibited with the Rental Vehicle. In the event that Renter utilizes the Rental Vehicle outside of the state without such consent, the rental agreement will be terminated. In the event of any mechanical issue or damage to the Rental Vehicle while outside of the state, Renter shall be fully responsible for all associated expenses incurred by Owner, including but not limited to towing fees and repair costs.</p>
                <div class="rca-initial-field">
                <label>
                        <input type="checkbox" name="rca_out_of_state_initial" required> 
                        I acknowledge that out of state driving is prohibited. <span class="rca-required-asterisk">*</span>
                </label>
                </div>
            </div>

            <!-- Entire Agreement -->
            <div class="rca-form-section">
                <h4>ENTIRE AGREEMENT</h4>
                <p>This Car Rental Agreement constitutes the entire agreement between the Parties with respect to this rental arrangement. No modification to this agreement can be made unless in writing signed by both Parties. Any notice required to be given to the other party will be made to the contact information above.</p>
            </div>

            <!-- Signature -->
            <div class="rca-form-section rca-signature-section">
                <div class="rca-form-group">
                    <label for="rca_signature">Signature (Type your full name) <span class="rca-required-asterisk">*</span></label>
                    <input type="text" name="rca_signature" id="rca_signature" placeholder="Type your full name as signature" required>
                </div>
                <p><small>By typing your name above, you are electronically signing this agreement.</small></p>
            </div>

            <button type="submit" class="rca-btn rca-submit-btn">Submit Rental Agreement</button>
        </form>
    <?php endif; ?>
</div>




