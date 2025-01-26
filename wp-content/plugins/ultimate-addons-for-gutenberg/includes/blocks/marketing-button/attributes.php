<?php
/**
 * Attributes File.
 *
 * @since 2.0.0
 *
 * @package uagb
 */

$button_attribute   = UAGB_Block_Helper::uag_generate_border_attribute(
	'btn'
);
$inherit_from_theme = 'enabled' === ( 'deleted' !== UAGB_Admin_Helper::get_admin_settings_option( 'uag_btn_inherit_from_theme_fallback', 'deleted' ) ? 'disabled' : UAGB_Admin_Helper::get_admin_settings_option( 'uag_btn_inherit_from_theme', 'disabled' ) );

return array_merge(
	array(
		'classMigrate'              => false,
		'block_id'                  => '',
		'align'                     => 'center',
		'textAlign'                 => 'center',
		'link'                      => '#',
		'linkTarget'                => false,
		'titleSpace'                => 0,
		'titleSpaceTablet'          => '',
		'titleSpaceUnit'            => 'px',
		'titleSpaceMobile'          => '',
		'vPadding'                  => '',
		'hPadding'                  => '',
		'vPaddingMobile'            => '',
		'hPaddingMobile'            => '',
		'vPaddingTablet'            => '',
		'hPaddingTablet'            => '',
		'paddingType'               => 'px',
		'backgroundType'            => 'color',
		'backgroundColor'           => '',
		'backgroundHoverColor'      => '',
		'gradientColor1'            => '#06558a',
		'gradientColor2'            => '#0063A1',
		'gradientType'              => 'linear',
		'gradientLocation1'         => 0,
		'gradientLocationTablet1'   => '',
		'gradientLocationMobile1'   => '',
		'gradientLocation2'         => 100,
		'gradientLocationTablet2'   => '',
		'gradientLocationMobile2'   => '',
		'gradientAngle'             => 0,
		'gradientAngleTablet'       => '',
		'gradientAngleMobile'       => '',
		'backgroundOpacity'         => '',
		'backgroundHoverOpacity'    => '',
		'titleColor'                => '',
		'titleHoverColor'           => '',
		'icon'                      => 'up-right-from-square',
		'iconColor'                 => '',
		'iconHoverColor'            => '',
		'iconPosition'              => 'after',
		'prefixColor'               => '',
		'prefixHoverColor'          => '',
		'iconSpace'                 => 10,
		'iconSpaceTablet'           => '',
		'iconSpaceMobile'           => '',
		'titleLoadGoogleFonts'      => false,
		'titleFontFamily'           => '',
		'titleFontWeight'           => '',
		'titleFontStyle'            => '',
		'titleFontSize'             => 20,
		'titleFontSizeType'         => 'px',
		'titleFontSizeTablet'       => 20,
		'titleFontSizeMobile'       => 20,
		'titleLineHeightType'       => 'em',
		'titleLineHeight'           => '',
		'titleLineHeightTablet'     => '',
		'titleLineHeightMobile'     => '',
		'titleTag'                  => 'span',
		'prefixLoadGoogleFonts'     => false,
		'prefixFontFamily'          => '',
		'prefixFontWeight'          => '',
		'prefixFontStyle'           => '',
		'prefixFontSize'            => 14,
		'prefixFontSizeType'        => 'px',
		'prefixFontSizeTablet'      => 14,
		'prefixFontSizeMobile'      => 14,
		'prefixLineHeightType'      => 'em',
		'prefixLineHeight'          => 2,
		'prefixLineHeightTablet'    => '',
		'prefixLineHeightMobile'    => '',
		'iconFontSize'              => 20,
		'iconFontSizeType'          => 'px',
		'iconFontSizeTablet'        => '',
		'iconFontSizeMobile'        => '',
		'paddingBtnUnit'            => 'px',
		'mobilePaddingBtnUnit'      => 'px',
		'tabletPaddingBtnUnit'      => 'px',
		'titleTransform'            => '',
		'titleDecoration'           => '',
		'prefixTransform'           => '',
		'prefixDecoration'          => '',
		'titleLetterSpacing'        => '',
		'titleLetterSpacingTablet'  => '',
		'titleLetterSpacingMobile'  => '',
		'titleLetterSpacingType'    => 'px',
		'prefixLetterSpacing'       => '',
		'prefixLetterSpacingTablet' => '',
		'prefixLetterSpacingMobile' => '',
		'prefixLetterSpacingType'   => 'px',
		'borderStyle'               => 'solid',
		'borderWidth'               => 1,
		'borderRadius'              => '',
		'borderColor'               => '',
		'borderHoverColor'          => '',
		'inheritFromTheme'          => $inherit_from_theme,
	),
	$button_attribute
);