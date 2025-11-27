<?php
/**
 * Template: Inventory Grid with Modal Support
 */
// Get items per row (passed from shortcode, default to 3)
$items_per_row = isset( $items_per_row ) ? intval( $items_per_row ) : 3;
// Apply grid columns via inline style (will be responsive on mobile via media query)
$grid_style = 'grid-template-columns: repeat(' . esc_attr( $items_per_row ) . ', 1fr);';
?>
<div class="rca-inventory-wrapper">
    <div class="rca-inventory-grid" style="<?php echo esc_attr( $grid_style ); ?>">
        <?php if ( $query->have_posts() ) : ?>
            <?php while ( $query->have_posts() ) : $query->the_post(); 
                $meta = get_post_meta( get_the_ID() );
                $w_rate = isset($meta['_rca_weekly_rate'][0]) ? $meta['_rca_weekly_rate'][0] : '';
                $year = isset($meta['_rca_year'][0]) ? $meta['_rca_year'][0] : '';
                $make = isset($meta['_rca_make'][0]) ? $meta['_rca_make'][0] : '';
                $model = isset($meta['_rca_model'][0]) ? $meta['_rca_model'][0] : '';
                $tag = isset($meta['_rca_notes'][0]) ? $meta['_rca_notes'][0] : ''; // Using notes as tag for now
                $img_url = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'medium_large') : '';
            ?>
                <div class="rca-vehicle-card">
                    <div class="rca-vehicle-thumb" <?php if($img_url) echo 'style="background-image: url(' . esc_url($img_url) . ');"'; ?>>
                        <?php if ( ! $img_url ) : ?>
                            <div class="rca-placeholder-img">No Image</div>
                        <?php endif; ?>
                        <?php if($tag): ?>
                            <!-- <span class="rca-vehicle-tag"><?php echo esc_html(substr($tag, 0, 20)); ?>...</span> -->
                        <?php endif; ?>
                    </div>
                    <div class="rca-vehicle-info">
                        <h3><?php echo esc_html( "$year $make $model" ); ?></h3>
                        
                        <?php if ( $w_rate ) : ?>
                            <div class="rca-price">$<?php echo esc_html( $w_rate ); ?> <span>/ week</span></div>
                        <?php endif; ?>
                        
                        <div class="rca-actions">
                            <button type="button" 
                                    class="rca-btn rca-open-modal" 
                                    data-vehicle-id="<?php echo get_the_ID(); ?>"
                                    data-vehicle-title="<?php echo esc_attr("$year $make $model"); ?>"
                                    data-vehicle-rate="<?php echo esc_attr($w_rate); ?>">
                                Book This Car
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; wp_reset_postdata(); ?>
        <?php else : ?>
            <p>No vehicles available at the moment.</p>
        <?php endif; ?>
    </div>
</div>

