<?php
/**
 * Template for printable agreement
 */
$print_mode = isset( $_GET['print'] ) && $_GET['print'] === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Rental Agreement - <?php echo esc_html( $meta['renter_id'] ); ?></title>
    <style>
        @media print {
            @page {
                margin: 1cm;
            }
            body { margin: 0; padding: 0; }
            .no-print { display: none !important; }
        }
        body { 
            font-family: 'Times New Roman', Times, serif; 
            font-size: 11pt; 
            line-height: 1.6; 
            color: #000; 
            max-width: 8.5in; 
            margin: 0 auto; 
            padding: 20px; 
        }
        .no-print { 
            margin-bottom: 20px; 
            text-align: center; 
            padding: 20px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        .no-print button {
            padding: 12px 24px;
            font-size: 16px;
            background: #0073aa;
            color: #fff;
            border: none;
            cursor: pointer;
            border-radius: 3px;
            margin: 0 5px;
        }
        .no-print button:hover {
            background: #005a87;
        }
        h1, h2, h3 { 
            margin: 8px 0 3px 0; 
            font-weight: bold;
        }
        h1 { 
            font-size: 18pt; 
            text-align: center;
            margin-bottom: 10px;
        }
        h2 {
            font-size: 14pt;
            text-align: center;
            margin: 5px 0 3px 0;
        }
        h3 {
            font-size: 12pt;
            margin-top: 4px;
            margin-bottom: 2px;
        }
        .company-header {
            text-align: center;
            margin-bottom: 15px;
        }
        .company-header h1 {
            font-size: 16pt;
            margin-bottom: 5px;
        }
        .company-address {
            font-size: 11pt;
            margin: 5px 0;
        }
        .section {
            margin-bottom: 4px;
        }
        .section p {
            margin: 3px 0;
            text-align: justify;
        }
        .section-title {
            font-weight: bold;
            margin-top: 8px;
            margin-bottom: 4px;
        }
        .field-line {
            margin: 3px 0;
        }
        .field-label {
            font-weight: bold;
            display: inline-block;
            min-width: 150px;
        }
        .initial-line {
            margin: 4px 0;
        }
        .checkmark {
            color: #000;
            font-weight: bold;
            font-size: 14pt;
        }
        .signature-section {
            margin-top: 15px;
            border-top: 1px solid #000;
            padding-top: 8px;
        }
        .renter-info-section {
            margin-top: 10px;
            border-top: 1px solid #ccc;
            padding-top: 8px;
        }
        ul {
            margin: 4px 0;
            padding-left: 25px;
        }
        li {
            margin: 2px 0;
        }
        .insurance-option {
            margin: 5px 0;
        }
        .insurance-option-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
    </style>
    <script>
        function printPDF() {
            window.print();
        }
        function downloadPDF() {
            window.print();
        }
    </script>
</head>
<body>

    <?php if ( isset( $print_mode ) && $print_mode ) : ?>
    <div class="no-print">
        <button onclick="printPDF();">Print PDF</button>
        <button onclick="window.close();">Close</button>
    </div>
    <?php endif; ?>

    <div class="company-header">
        <h1>PNS GLOBAL RESOURCES L.L.C</h1>
        <div class="company-address">
            5872 NEW PEACHTREE RD STE ST 103<br>
            Doraville, GA 30340
        </div>
    </div>

    <h2 style="margin-bottom: 3px;">Car Rental Agreement</h2>

    <div class="section" style="margin-top: 2px;">
        <p style="margin-top: 2px;">This Car Rental Agreement is entered into between PNS GLOBAL RESOURCES L.L.C (OWNER) and ("Renter") (collectively the "Parties") and outlines the respective rights and obligations of the Parties relating to the rental of a vehicle.</p>
        
        <div class="field-line">
            <span class="field-label">Renter I.D. Information:</span> <?php echo esc_html( $meta['renter_id'] ); ?>
        </div>
    </div>

    <div class="section">
        <h3>1. IDENTIFICATION OF THE RENTAL VEHICLE</h3>
        <p>Owner hereby agrees to rent a passenger vehicle identified as followed:</p>
        <div class="field-line">
            <span class="field-label">Make and Model:</span> <?php echo esc_html( $v_meta['make'] . ' ' . $v_meta['model'] ); ?>
        </div>
        <div class="field-line">
            <span class="field-label">Year:</span> <?php echo esc_html( $v_meta['year'] ); ?>
        </div>
        <div class="field-line">
            <span class="field-label">VIN:</span> <?php echo esc_html( $v_meta['vin'] ); ?>
        </div>
        <div class="field-line">
            <span class="field-label">Color:</span> <?php echo esc_html( $v_meta['color'] ); ?>
        </div>
        <div class="field-line">
            <span class="field-label">Plate Number:</span> <?php echo esc_html( $v_meta['plate'] ); ?>
        </div>
        <p>(Hereinafter referred to as "Rental Vehicle")</p>
    </div>

    <div class="section">
        <h3>2. RENTAL TERM</h3>
        <p>The Owner hereby agrees to rent the Vehicle to the Renter for the duration specified below, subject to the terms and conditions of this Rental Agreement:</p>
        <div class="field-line">
            <span class="field-label">Start Date:</span> <?php echo esc_html( date( 'F j, Y', strtotime( $meta['start'] ) ) ); ?>
        </div>
        <div class="field-line">
            <span class="field-label">End Date:</span> <?php echo esc_html( date( 'F j, Y', strtotime( $meta['end'] ) ) ); ?>
        </div>
        <p>The term of this Car Rental Agreement shall commence upon the date and time of Vehicle pickup as specified above and shall terminate upon the return of the Vehicle to the Owner, provided that all the terms of this Agreement have been fully complied with by both Parties. In the event that the Renter wishes to extend the term of this Agreement, approval must first be obtained from the Owner.</p>
        <p>Please be advised that in the event of an extension of the rental term, we require a minimum of 2 days' notice prior to the scheduled return date. Failure to provide such notice may result in the denial of the extension request or imposition of additional fees or charges.</p>
        <div class="initial-line">
            <span class="field-label">Initial:</span> <?php echo ( isset( $all_initials['rca_rental_term_initial'] ) && $all_initials['rca_rental_term_initial'] === 'yes' ) ? '<span class="checkmark">&#10003;</span>' : '___'; ?>
        </div>
    </div>

    <div class="section">
        <h3>3. MILEAGE</h3>
        <p>The Rental Vehicle has unlimited mileage per day.</p>
        <div class="initial-line">
            <span class="field-label">Initial:</span> <?php echo ( isset( $all_initials['rca_mileage_initial'] ) && $all_initials['rca_mileage_initial'] === 'yes' ) ? '<span class="checkmark">&#10003;</span>' : '___'; ?>
        </div>
    </div>

    <div class="section">
        <h3>4. RENTAL FEES</h3>
        <p>The Renter shall remit rental fees to the Owner in accordance with the following payment schedule:</p>
        <div class="field-line">
            <span class="field-label">Base fee (weekly):</span> $<?php 
            $clean_rate = str_replace( array( '$', ',' ), '', $v_meta['rate_w'] );
            echo esc_html( $clean_rate ); 
            ?>
        </div>
        <p>Weekly payments shall be due every Wednesday. First payment is due once the rental agreement is signed by the client. All payments are due every Wednesday. Should the Renter fail to remit payment by 11:59PM EST on the due date, a late fee of $50 shall be assessed. In the event payment is not received within 24 hours of the due date; Owner reserves the right to disable and/or repossess the Rental Vehicle through the services of a security company.</p>
        <div class="initial-line">
            <span class="field-label">Initial:</span> <?php echo ( isset( $all_initials['rca_rental_fees_initial'] ) && $all_initials['rca_rental_fees_initial'] === 'yes' ) ? '<span class="checkmark">&#10003;</span>' : '___'; ?>
        </div>
    </div>

        <div class="section">
        <h3>5. Loss of Keys</h3>
        <p>If the renter loses the key provided by the rental company, they are responsible for a $400 replacement fee. If the renter locks the key inside the car, they will be charged $185 for the rental company to send a locksmith to unlock the vehicle.</p>
        <div class="initial-line">
            <span class="field-label">Initial:</span> <?php echo ( isset( $all_initials['rca_keys_initial'] ) && $all_initials['rca_keys_initial'] === 'yes' ) ? '<span class="checkmark">&#10003;</span>' : '___'; ?>
        </div>
        </div>

        <div class="section">
        <h3>6. Responsibility for Damage or Loss; Reporting to Police.</h3>
        <p>You are responsible for damage to, or loss or theft of the Vehicle, which includes the cost of repair, or the actual cash retail value of the Vehicle on the date of the loss if it is not repairable or if we elect not to repair it, plus loss of use, diminished value of the Vehicle caused by damage to it or repair of it, and our administrative expenses incurred processing the insurance claim, whether or not you are at fault. You must report all accidents or incidents of theft and vandalism to us, and the police, as soon as you discover them.</p>
        <div class="initial-line">
            <span class="field-label">Initial:</span> <?php echo ( isset( $all_initials['rca_damage_responsibility_initial'] ) && $all_initials['rca_damage_responsibility_initial'] === 'yes' ) ? '<span class="checkmark">&#10003;</span>' : '___'; ?>
        </div>
    </div>

    <div class="section">
        <h3>7. INSURANCE COVERAGE</h3>
        <p>Please read each insurance option below & initial beside the one you agree to. N/A the other 3 options.</p>
        
        <div class="insurance-option">
            <div class="insurance-option-title">Option 1.</div>
            <p>I agree to provide your own insurance covering you, us, & the vehicle in case of an accident. You are responsible for all damage or loss you cause to others. (Note: you are required to make the rental company a interested party on the policy for verification purposes of coverage & any changes that could occur to policy)</p>
            <div class="initial-line">
                <span class="field-label">Initial:</span> <?php echo ( $meta['insurance'] === 'option1' ) ? '<span class="checkmark">&#10003;</span>' : '___'; ?>
            </div>
        </div>

        <div class="insurance-option">
            <div class="insurance-option-title">Option 2.</div>
            <p>I have decided to provide liability insurance & purchase RENTERS COLLISION PROTECTION available through this car rental company for $20 per day, including a service charge. I understand that this product will pay for any collision damage done to this rented vehicle up to a maximum $20,000 with a $250 deductible, as long as I or any 'Authorized Drivers' do not violate this contract. I also understand that the purchase of the RCP is non-refundable, even if the rental vehicle is returned early.</p>
            <div class="initial-line">
                <span class="field-label">Initial:</span> <?php echo ( $meta['insurance'] === 'option2' ) ? '<span class="checkmark">&#10003;</span>' : '___'; ?>
            </div>
        </div>

        <div class="insurance-option">
            <div class="insurance-option-title">Option 3.</div>
            <p>I agree to allow the rental car company to create a personalized insurance policy covering you, us, & the vehicle in case of an accident.</p>
            <div class="initial-line">
                <span class="field-label">Initial:</span> <?php echo ( $meta['insurance'] === 'option3' ) ? '<span class="checkmark">&#10003;</span>' : '___'; ?>
            </div>
        </div>

        <div class="insurance-option">
            <div class="insurance-option-title">Option 4.</div>
            <p>The insurance is included in the vehicles price</p>
            <div class="initial-line">
                <span class="field-label">Initial:</span> <?php echo ( $meta['insurance'] === 'option4' ) ? '<span class="checkmark">&#10003;</span>' : '___'; ?>
            </div>
        </div>
    </div>

    <div class="section">
        <h3>8. INSURANCE CLAIMS</h3>
        <p>In the event that the Rental Vehicle is damaged or destroyed while it is in the possession of Renter, Renter shall be responsible for paying the required insurance deductible in the amount of $1000, in the event that a claim is made for the damages. If a claim is not filed, Renter agrees to pay Owner directly for any repairs or damages incurred to the Rental Vehicle.</p>
        <div class="initial-line">
            <span class="field-label">Initial:</span> <?php echo ( isset( $all_initials['rca_insurance_claims_initial'] ) && $all_initials['rca_insurance_claims_initial'] === 'yes' ) ? '<span class="checkmark">&#10003;</span>' : '___'; ?>
        </div>
    </div>

    <div class="section">
        <h3>9. INDEMNIFICATION</h3>
        <p>The Renter hereby agrees to indemnify, defend, and hold harmless the Rental Company, its owner, employees, and affiliates from any and all claims, demands, causes of action, losses, damages, and expenses, including reasonable attorney's fees, arising out of or in connection with the Renter's use or operation of the Rental Vehicle during the rental period. The Renter shall assume full responsibility and pay for any parking tickets, moving violations, tolls, or other citations received while in possession of the Rental Vehicle, and shall promptly notify the Owner of any such citations. The indemnification also includes any attorney fees necessarily incurred by the Owner for these purposes. Furthermore, the Rental Company (Owner) shall not be liable for any loss, damage, or injury to the Renter or any third party arising from the use of the Rental Vehicle, whether caused by the Renter's negligence or otherwise. Renter agrees by initialing document and signing that Renter or any third party cannot take any legal action against PNS GLOBAL RESOURCES L.L.C for any loss, damage, or injury to the Renter or any third party arising from the use of the Rental Vehicle, whether caused by the Renter's negligence or otherwise.</p>
        <div class="initial-line">
            <span class="field-label">Initial:</span> <?php echo ( isset( $all_initials['rca_indemnification_initial'] ) && $all_initials['rca_indemnification_initial'] === 'yes' ) ? '<span class="checkmark">&#10003;</span>' : '___'; ?>
        </div>
    </div>

    <div class="section">
        <h3>10. ACKNOWLEDGMENT OF RISK</h3>
        <p>Renter acknowledges that the use, operation, or possession of the rented equipment/property ("Rental Item(s)") involves inherent risks that may result in injury, death, or property damage. Renter agrees to assume all risks associated with the use of the Rental Item(s), whether known or unknown, foreseeable or unforeseeable.</p>
        <div class="initial-line">
            <span class="field-label">Initial:</span> <?php echo ( isset( $all_initials['rca_risk_acknowledgment_initial'] ) && $all_initials['rca_risk_acknowledgment_initial'] === 'yes' ) ? '<span class="checkmark">&#10003;</span>' : '___'; ?>
        </div>
    </div>

    <div class="section">
        <h3>11. RELEASE & WAIVER</h3>
        <p>In consideration of being permitted to rent and use the Rental Item(s), Renter voluntarily and knowingly releases, waives, and discharges the Company PNS GLOBAL RESOURCES L.L.C from any and all liability, claims, demands, actions, or causes of action arising out of or related to any loss, damage, or injury, including death, that may be sustained while using, transporting, or in any way engaging with the Rental Item(s), whether caused by the Company's negligence or otherwise.</p>
        <div class="initial-line">
            <span class="field-label">Initial:</span> <?php echo ( isset( $all_initials['rca_release_waiver_initial'] ) && $all_initials['rca_release_waiver_initial'] === 'yes' ) ? '<span class="checkmark">&#10003;</span>' : '___'; ?>
        </div>
    </div>

    <div class="section">
        <h3>12. CAR RENTAL VIOLATION CHARGES</h3>
        <ol>
            <li><strong>Fuel Level:</strong> If the gas tank is returned below the level at which it was provided, a charge of $50 will be applied.</li>
            <li><strong>Vehicle Cleanliness:</strong> If the vehicle is returned extremely dirty, an additional cleaning fee of $100 will be applied.</li>
            <li><strong>Tire Damage:</strong> Any damage to the tires caused during the rental period will incur a charge of $120 per tire.</li>
            <li><strong>Rim Damage:</strong> Any rash or damage to the rims will result in a charge of $400 per rim.</li>
            <li><strong>Spills:</strong> Any spills inside the vehicle will result in a cleaning charge of $50.</li>
            <li><strong>Smoking:</strong> This Vehicle is a NON-SMOKING VEHICLE. Absolutely NO SMOKING and if there is any evidence of smoking or smell there will be a $150 smoking fee.</li>
        </ol>
        <p>Please ensure the vehicle is returned in the same condition as when it was rented to avoid these charges.</p>
        <div class="initial-line">
            <span class="field-label">Initial:</span> <?php echo ( isset( $all_initials['rca_violation_charges_initial'] ) && $all_initials['rca_violation_charges_initial'] === 'yes' ) ? '<span class="checkmark">&#10003;</span>' : '___'; ?>
        </div>
    </div>

    <div class="section">
        <h3>13. VEHICLE CONDITION AND MAINTENANCE</h3>
        <p>Owner represents and warrants that, to the best of Owner's knowledge, the Rental Vehicle is in good condition and is safe for ordinary operation. Prior to taking possession of the Rental Vehicle, Renter has the opportunity to inspect the vehicle for any existing damages to the vehicle. For long-term renters, Owner shall cover basic maintenance such as oil changes, wipers, air filters, and cabin filters. However, Renter shall be responsible for any damages, such as blown tires or damaged wheels, and associated repairs. All maintenance and repairs shall be conducted by mobile mechanic. For all long-term renters, a mandatory safety inspection shall be conducted every 30-35 days.</p>
        <div class="initial-line">
            <span class="field-label">Initial:</span> <?php echo ( isset( $all_initials['rca_vehicle_condition_initial'] ) && $all_initials['rca_vehicle_condition_initial'] === 'yes' ) ? '<span class="checkmark">&#10003;</span>' : '___'; ?>
        </div>
    </div>

    <div class="section">
        <h3>14. CHECK IN & CHECK OUT PROCESS</h3>
        <p>A comprehensive inspection of the vehicle's condition is mandatory before & after rental period. Before the trip begins, a meticulous assessment of the vehicle's state is conducted in the presence of the customer to document any pre-existing damages or issues. After the trip ends, another detailed inspection occurs with the customer to evaluate the vehicle's condition post-trip. It's crucial that the customer remains present during inspection to address any potential damages or discrepancies that may have occurred during their use of the vehicle. If there are any damages done, the renter is responsible for $1000 as an additional charge. This process ensures transparency and fairness regarding the vehicle's condition and any associated costs.</p>
        <div class="initial-line">
            <span class="field-label">Initial:</span> <?php echo ( isset( $all_initials['rca_checkin_checkout_initial'] ) && $all_initials['rca_checkin_checkout_initial'] === 'yes' ) ? '<span class="checkmark">&#10003;</span>' : '___'; ?>
        </div>
    </div>

    <div class="section">
        <h3>15. FREE WILL CLAUSE</h3>
        <p>PNS GLOBAL RESOURCES L.L.C affirms the free will of its customers to enter into rental agreements voluntarily. Customers acknowledge that they are entering into contracts of their own accord, without coercion, and with a clear understanding of the terms and conditions outlined in the agreement. By initialing you acknowledge, understand, and agree</p>
        <div class="initial-line">
            <span class="field-label">Initial:</span> <?php echo ( isset( $all_initials['rca_free_will_initial'] ) && $all_initials['rca_free_will_initial'] === 'yes' ) ? '<span class="checkmark">&#10003;</span>' : '___'; ?>
        </div>
    </div>

    <div class="section">
        <h3>16. Discriminatory Clause</h3>
        <p>PNS GLOBAL RESOURCES L.L.C strictly prohibits any form of discrimination against customers based on race, color, religion, sex, national origin, disability, or any other protected characteristic. The agency is committed to providing equal access and opportunities to all customers, ensuring a fair and inclusive rental experience for everyone. By initialing you agree that you were not discriminated against in any way.</p>
        <div class="initial-line">
            <span class="field-label">Initial:</span> <?php echo ( isset( $all_initials['rca_discriminatory_initial'] ) && $all_initials['rca_discriminatory_initial'] === 'yes' ) ? '<span class="checkmark">&#10003;</span>' : '___'; ?>
        </div>
    </div>

    <div class="section">
        <h3>17. ECPA "Electronically Funded Payment" Clause</h3>
        <p>PNS GLOBAL RESOURCES L.L.C, in compliance with the Electronic Communications Privacy Act (ECPA), acknowledges that customer receipts of payment, whether in electronic or written form, shall be treated with the utmost confidentiality. The agency commits to safeguarding the privacy of payment information and will not disclose, sell, or share such information with third parties without the express consent of the customer, except as required by law. By initialing you acknowledge, understand, and agree.</p>
        <div class="initial-line">
            <span class="field-label">Initial:</span> <?php echo ( isset( $all_initials['rca_ecpa_initial'] ) && $all_initials['rca_ecpa_initial'] === 'yes' ) ? '<span class="checkmark">&#10003;</span>' : '___'; ?>
        </div>
    </div>

    <div class="section">
        <h3>18. Opt-In and Opt-Out Clause</h3>
        <p>By signing this agreement, you acknowledge that you may be offered optional services such as insurance upgrades, roadside assistance, or GPS rental. You may choose to opt in to any of these services at the time of rental. All optional services selected will be clearly itemized on your rental agreement and are subject to applicable fees and terms.</p>
        <p>In addition, you may opt in to receive marketing communications from PNS GLOBAL RESOURCES L.L.C, including promotions, discounts, and service updates, via email, text message, or phone.</p>
        <div class="initial-line">
            <span class="field-label">Initial:</span> <?php echo ( isset( $all_initials['rca_optin_optout_initial'] ) && $all_initials['rca_optin_optout_initial'] === 'yes' ) ? '<span class="checkmark">&#10003;</span>' : '___'; ?>
        </div>
    </div>

    <div class="section">
        <h3>19. Arbitration Agreement</h3>
        <p>By entering into a contract with PNS GLOBAL RESOURCES L.L.C, the customer agrees that any disputes or claims arising out of or in connection with the contract shall be resolved through binding arbitration in accordance with the rules of the American Arbitration Association. The customer further agrees that, in the event of a breach of contract, PNS GLOBAL RESOURCES L.L.C, retains the right to pursue legal action in small claims court or any appropriate venue to seek damages or injunctive relief as permitted by law. By initialing you acknowledge, understand, and agree.</p>
        <div class="initial-line">
            <span class="field-label">Initial:</span> <?php echo ( isset( $all_initials['rca_arbitration_initial'] ) && $all_initials['rca_arbitration_initial'] === 'yes' ) ? '<span class="checkmark">&#10003;</span>' : '___'; ?>
        </div>
    </div>

    <div class="section">
        <h3>20. Breach Of Contract</h3>
        <ul>
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
        <div class="initial-line">
            <span class="field-label">Initial:</span> <?php echo ( isset( $all_initials['rca_breach_contract_initial'] ) && $all_initials['rca_breach_contract_initial'] === 'yes' ) ? '<span class="checkmark">&#10003;</span>' : '___'; ?>
        </div>
    </div>

    <div class="section">
        <h3>21. Your Property</h3>
        <p>You release us, our agents and employees from all claims for loss of, or damage to, your personal property or that of any other person, that we received, handled or stored, or that was left or carried in or on the Vehicle or in any service vehicle or in our offices, whether or not the loss or damage was caused by our negligence or was otherwise our responsibility.</p>
        <div class="initial-line">
            <span class="field-label">Initial:</span> <?php echo ( isset( $all_initials['rca_your_property_initial'] ) && $all_initials['rca_your_property_initial'] === 'yes' ) ? '<span class="checkmark">&#10003;</span>' : '___'; ?>
        </div>
    </div>

    <div class="section">
        <h3>22. Returning Vehicle</h3>
        <p>If the renter returns the vehicle mid-week (Wednesday) your payment for the week will be prorated (you will owe half of weekly rate). DEPOSIT IF PAID ($200 WILL BE RETURNED UPON DROP OFF). If the renter was to return the vehicle any day after (Wednesday) will be responsible for paying the weekly rate.</p>
        <div class="initial-line">
            <span class="field-label">Initial:</span> <?php echo ( isset( $all_initials['rca_returning_vehicle_initial'] ) && $all_initials['rca_returning_vehicle_initial'] === 'yes' ) ? '<span class="checkmark">&#10003;</span>' : '___'; ?>
        </div>
    </div>

    <div class="renter-info-section">
        <h3>Renter's information:</h3>
        <div class="field-line">
            <span class="field-label">Name:</span> <?php echo esc_html( $meta['name'] ); ?>
        </div>
        <div class="field-line">
            <span class="field-label">Driver's License Number:</span> <?php echo esc_html( $meta['license'] ); ?>
        </div>
        <div class="field-line">
            <span class="field-label">Address:</span> <?php echo esc_html( $meta['address'] ); ?>
        </div>
        <div class="field-line">
            <span class="field-label">Phone:</span> <?php echo esc_html( $meta['phone'] ); ?>
        </div>
        <div class="field-line">
            <span class="field-label">Email:</span> <?php echo esc_html( $meta['email'] ); ?>
        </div>
        <div class="field-line">
            <span class="field-label">Date:</span> <?php echo esc_html( date( 'F j, Y', strtotime( $meta['agreement_date'] ) ) ); ?>
        </div>
    </div>

    <div class="section">
        <h3>23. OUT OF STATE DRIVING</h3>
        <p>Out of state driving is strictly prohibited with the Rental Vehicle. In the event that Renter utilizes the Rental Vehicle outside of the state without such consent, the rental agreement will be terminated. In the event of any mechanical issue or damage to the Rental Vehicle while outside of the state, Renter shall be fully responsible for all associated expenses incurred by Owner, including but not limited to towing fees and repair costs.</p>
        <div class="initial-line">
            <span class="field-label">Initial:</span> <?php echo ( isset( $all_initials['rca_out_of_state_initial'] ) && $all_initials['rca_out_of_state_initial'] === 'yes' ) ? '<span class="checkmark">&#10003;</span>' : '___'; ?>
        </div>
    </div>

    <div class="section">
        <h3>ENTIRE AGREEMENT</h3>
        <p>This Car Rental Agreement constitutes the entire agreement between the Parties with respect to this rental arrangement. No modification to this agreement can be made unless in writing signed by both Parties. Any notice required to be given to the other party will be made to the contact information above.</p>
    </div>

    <div class="signature-section">
        <div class="field-line">
            <span class="field-label">Signature:</span> <?php echo esc_html( $meta['signature'] ); ?>
        </div>
    </div>

</body>
</html>
