<?php
/**
 * Template: Inventory Grid
 */
?>
<div class="rca-inventory-grid">
    <?php if ( $query->have_posts() ) : ?>
        <?php while ( $query->have_posts() ) : $query->the_post(); 
            $meta = get_post_meta( get_the_ID() );
            $w_rate = isset($meta['_rca_weekly_rate'][0]) ? $meta['_rca_weekly_rate'][0] : '';
            $year = isset($meta['_rca_year'][0]) ? $meta['_rca_year'][0] : '';
            $make = isset($meta['_rca_make'][0]) ? $meta['_rca_make'][0] : '';
            $model = isset($meta['_rca_model'][0]) ? $meta['_rca_model'][0] : '';
        ?>
            <div class="rca-vehicle-card">
                <div class="rca-vehicle-thumb">
                    <?php if ( has_post_thumbnail() ) {
                        the_post_thumbnail( 'medium' );
                    } else {
                        echo '<div class="rca-placeholder-img">No Image</div>';
                    } ?>
                </div>
                <div class="rca-vehicle-info">
                    <h3><?php echo esc_html( "$year $make $model" ); ?></h3>
                    <?php if ( $w_rate ) : ?>
                        <div class="rca-price">$<?php echo esc_html( $w_rate ); ?> / week</div>
                    <?php endif; ?>
                    <div class="rca-actions">
                        <!-- Assuming the booking page is known, or using a query param on current page if form is hidden -->
                        <!-- Ideally user puts [rental_car_booking] on a page like /booking/ -->
                        <!-- For this demo, we link to a hypothetical booking page or same page with param -->
                        <a href="?vehicle_id=<?php echo get_the_ID(); ?>#booking" class="rca-btn rca-btn-small">Book Now</a>
                    </div>
                </div>
            </div>
        <?php endwhile; wp_reset_postdata(); ?>
    <?php else : ?>
        <p>No vehicles available at the moment.</p>
    <?php endif; ?>
</div>

