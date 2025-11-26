<?php
/**
 * Template for printable agreement
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Agreement #<?php echo $booking_id; ?></title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12pt; line-height: 1.5; color: #333; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1, h2, h3 { margin-bottom: 10px; text-transform: uppercase; }
        h1 { font-size: 24px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .section { margin-bottom: 25px; border: 1px solid #ddd; padding: 15px; }
        .section h3 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 5px; font-size: 14px; color: #555; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .row { margin-bottom: 5px; }
        .label { font-weight: bold; width: 120px; display: inline-block; }
        .terms { font-size: 10pt; text-align: justify; white-space: pre-wrap; }
        .signatures { margin-top: 50px; display: flex; justify-content: space-between; }
        .sig-box { width: 45%; border-top: 1px solid #000; padding-top: 10px; }
        .no-print { margin-bottom: 20px; text-align: right; }
        
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print();" style="padding: 10px 20px; font-size: 16px; background: #0073aa; color: #fff; border: none; cursor: pointer;">Print Agreement</button>
    </div>

    <div class="header">
        <div class="company-info">
            <h2><?php echo esc_html( $settings['business_name'] ); ?></h2>
            <p>
                <?php echo nl2br( esc_html( $settings['business_address'] ) ); ?><br>
                <?php echo esc_html( $settings['business_phone'] ); ?><br>
                <?php echo esc_html( $settings['business_email'] ); ?>
            </p>
        </div>
        <div class="agreement-meta">
            <h3>Rental Agreement</h3>
            <p><strong>Agreement #:</strong> <?php echo $booking_id; ?></p>
            <p><strong>Date:</strong> <?php echo date('F j, Y'); ?></p>
        </div>
    </div>

    <div class="grid">
        <div class="section">
            <h3>Renter Information</h3>
            <div class="row"><span class="label">Full Name:</span> <?php echo esc_html( $meta['name'] ); ?></div>
            <div class="row"><span class="label">Address:</span> <?php echo esc_html( $meta['address'] ); ?></div>
            <div class="row"><span class="label">Phone:</span> <?php echo esc_html( $meta['phone'] ); ?></div>
            <div class="row"><span class="label">Email:</span> <?php echo esc_html( $meta['email'] ); ?></div>
            <div class="row"><span class="label">License #:</span> <?php echo esc_html( $meta['license'] ); ?></div>
        </div>

        <div class="section">
            <h3>Vehicle Information</h3>
            <?php if ( ! empty( $v_meta ) ) : ?>
                <div class="row"><span class="label">Vehicle:</span> <?php echo esc_html( $v_meta['year'] . ' ' . $v_meta['make'] . ' ' . $v_meta['model'] ); ?></div>
                <div class="row"><span class="label">VIN:</span> <?php echo esc_html( $v_meta['vin'] ); ?></div>
                <div class="row"><span class="label">License Plate:</span> <?php echo esc_html( $v_meta['plate'] ); ?></div>
                <div class="row"><span class="label">Color:</span> <?php echo esc_html( $v_meta['color'] ); ?></div>
            <?php else : ?>
                <p>No vehicle assigned yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="section">
        <h3>Rental Details</h3>
        <div class="grid">
            <div>
                <div class="row"><span class="label">Start Date:</span> <?php echo rca_format_date( $meta['start'] ); ?></div>
                <div class="row"><span class="label">End Date:</span> <?php echo rca_format_date( $meta['end'] ); ?></div>
            </div>
            <div>
                <div class="row"><span class="label">Daily Rate:</span> $<?php echo esc_html( $v_meta['rate_d'] ); ?></div>
                <div class="row"><span class="label">Weekly Rate:</span> $<?php echo esc_html( $v_meta['rate_w'] ); ?></div>
                <div class="row"><span class="label">Insurance:</span> <?php echo ucfirst( $meta['insurance'] ); ?></div>
            </div>
        </div>
    </div>

    <?php if ( ! empty( $settings['terms'] ) ) : ?>
    <div class="section">
        <h3>Terms and Conditions</h3>
        <div class="terms">
            <?php echo esc_html( $settings['terms'] ); ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="signatures">
        <div class="sig-box">
            <p><strong>Renter Signature</strong></p>
            <br><br>
            <p>Date: _______________________</p>
        </div>
        <div class="sig-box">
            <p><strong>Authorized Representative</strong></p>
            <br><br>
            <p>Date: _______________________</p>
        </div>
    </div>

    <?php if ( ! empty( $settings['footer_text'] ) ) : ?>
        <div style="margin-top: 30px; text-align: center; font-size: 0.8em; color: #777; border-top: 1px solid #eee; padding-top: 10px;">
            <?php echo esc_html( $settings['footer_text'] ); ?>
        </div>
    <?php endif; ?>

</body>
</html>

