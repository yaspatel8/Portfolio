<?php
$id = wp_unique_id('tcbTabbedContent-');
extract($attributes);

$active = $tabBorder['active'] ?? ['width' => 0, 'color' => '#000', 'style' => 'solid'];

$theme = $options['theme'] ? $options['theme'] : "default";
$height = $borderHeight['height'] ?? "3px";
$width = $tabMenuBorder['width'] ?? "1px";
$menuBColor = $tabMenuBorder['menuBColor'] ?? "#ccc";
$isTabBG = $elements['isTabBG'];

$mainSl = "#$id";
$tabMenuSl = "$mainSl .tabMenu";
$tabbedSl = "$mainSl .tcbTabContent";

$styles = TCBPlugin::getTypoCSS('', $titleTypo)['googleFontLink'] .
	TCBPlugin::getTypoCSS("$tabMenuSl .tabLabel", $titleTypo)['styles'] . "

	$tabMenuSl {
		padding: " . implode(' ', $tabsPadding) . ";
	}
	
	$tabMenuSl li{
		" . TCBPlugin::getColorsCSS($tabColors) . "
	}
	$tabMenuSl li.active {
		" .
	TCBPlugin::getColorsCSS($tabActiveColors) .
	TCBPlugin::getBorderBoxCSS($active)
	. "
	}
	$tabMenuSl li .menuIcon i{
		font-size: " . $icon['size'] . ";
		color: " . $icon['color'] . ";
	}
	$tabMenuSl li.active .menuIcon i{
		color: " . $icon['activeColor'] . ";
	}
	$mainSl .tabContent {" .
	TCBPlugin::getBackgroundCSS($contentBG)
	. "
	}
";

if ($theme === "theme1") {
	$styles .= "
	$tabMenuSl li::after {
		height: $height;
		" . TCBPlugin::getBackgroundCSS($borderBG) . "
	}";
}

if ($theme === "theme1" || $theme === "theme5" || $theme === "theme6") {
	$styles .= "
	$mainSl .tabContent{
		border-top: $width solid $menuBColor;
	}";
}

if ($isTabBG) {
	$styles .= "
	$tabbedSl {
	" . TCBPlugin::getBackgroundCSS($tabbedBG) . "
	padding: " . implode(' ', $TabbedPadding) . ";
	}";
}

// Style disappearing problem
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

<div <?php echo get_block_wrapper_attributes(); ?> id='<?php echo esc_attr($id); ?>' data-attributes='<?php echo esc_attr(wp_json_encode($attributes)); ?>'>
	<style>
		<?php echo esc_html($styles); ?>
	</style>

	<div class='tcbTabContent <?php echo esc_attr($options['theme'] ?? 'default'); ?> '>
		<ul class='tabMenu' id='tabMenu-<?php echo esc_attr($id); ?>'>
			<?php foreach ($tabs as $index => $tab) {
				extract($tab);
				$iconShow = $elements['icon'] ? 'show' : 'hide';
				$labelShow = $elements['title'] ? 'show' : 'hide';
				$iconEl = isset($icon['class']) ? "<span class='menuIcon " . $iconShow . " '><i class='" . $icon["class"] . "'></i></span>" : '';

				$iconStyles = isset($icon['color']) || isset($icon['gradient']) ? TCBPlugin::getIconCSS($icon, false) : '';
			?>
				<li>
					<style>
						<?php echo esc_html("#$id #tabMenu-$id > li .menuIcon i{
							$iconStyles
						}"); ?>
					</style>

					<?php echo wp_kses_post($iconEl); ?>

					<span class='tabLabel <?php echo esc_attr($labelShow); ?>'>
						<?php echo wp_kses_post($title); ?>
					</span>
				</li>
			<?php } ?>
		</ul>

		<div class='tabContent' id='tabContent-<?php echo esc_attr($id); ?>'>
			<?php echo wp_kses( $content, $allowed_html ); ?>
		</div>
	</div>
</div>