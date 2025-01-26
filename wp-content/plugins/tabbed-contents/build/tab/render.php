<?php
$id = wp_unique_id( 'tcbTabContentTab-' );
extract( $attributes );

global $allowedposttags;
$allowed_html = wp_parse_args(['style' => [], 'iframe' => [
	'allowfullscreen' => true,
	'allowpaymentrequest' => true,
	'height' => true,
	'loading' => true,
	'name' => true,
	'referrerpolicy' => true,
	'sandbox' => true,
	'src' => true,
	'srcdoc' => true,
	'width' => true,
	'aria-controls' => true,
	'aria-current' => true,
	'aria-describedby' => true,
	'aria-details' => true,
	'aria-expanded' => true,
	'aria-hidden' => true,
	'aria-label' => true,
	'aria-labelledby' => true,
	'aria-live' => true,
	'class' => true,
	'data-*' => true,
	'dir' => true,
	'hidden' => true,
	'id' => true,
	'lang' => true,
	'style' => true,
	'title' => true,
	'role' => true,
	'xml:lang' => true
]], $allowedposttags);
?>

<div <?php echo get_block_wrapper_attributes(); ?> id='<?php echo esc_attr( $id ); ?>'>
	<?php echo wp_kses( $content, $allowed_html ); ?>
</div>