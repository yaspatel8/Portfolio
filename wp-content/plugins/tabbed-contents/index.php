<?php

/**
 * Plugin Name: Tabbed Contents Block
 * Description: Display responsive, accessible tabs featuring dynamic content.
 * Version: 1.1.0
 * Author: bPlugins
 * Author URI: https://bplugins.com
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: tabbed-contents
 * @fs_free_only, /bplugins_sdk
 */
// ABS PATH
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
if ( function_exists( 'tc_fs' ) ) {
    // This for .. if free plugin is installed, and when we will install pro plugin then uninstall free plugin
    register_activation_hook( __FILE__, function () {
        if ( is_plugin_active( 'tabbed-contents/index.php' ) ) {
            deactivate_plugins( 'tabbed-contents/index.php' );
        }
        if ( is_plugin_active( 'tabbed-contents-pro/index.php' ) ) {
            deactivate_plugins( 'tabbed-contents-pro/index.php' );
        }
    } );
} else {
    // Constant
    define( 'TCB_VERSION', ( isset( $_SERVER['HTTP_HOST'] ) && 'localhost' === $_SERVER['HTTP_HOST'] ? time() : '1.1.0' ) );
    define( 'TCB_DIR_URL', plugin_dir_url( __FILE__ ) );
    define( 'TCB_DIR_PATH', plugin_dir_path( __FILE__ ) );
    define( 'TCB_HAS_FREE', 'tabbed-contents/index.php' === plugin_basename( __FILE__ ) );
    define( 'TCB_HAS_PRO', 'tabbed-contents-pro/index.php' === plugin_basename( __FILE__ ) );
    if ( !function_exists( 'tc_fs' ) ) {
        // Create a helper function for easy SDK access.
        function tc_fs() {
            global $tc_fs;
            if ( !isset( $tc_fs ) ) {
                // Include Freemius SDK.
                $fsStartPath = dirname( __FILE__ ) . '/freemius/start.php';
                $bSDKInitPath = dirname( __FILE__ ) . '/bplugins_sdk/init.php';
                if ( TCB_HAS_PRO && file_exists( $fsStartPath ) ) {
                    require_once $fsStartPath;
                } else {
                    if ( TCB_HAS_FREE && file_exists( $bSDKInitPath ) ) {
                        require_once $bSDKInitPath;
                    }
                }
                $tcbConfig = array(
                    'id'                  => '17493',
                    'slug'                => 'tabbed-contents',
                    'premium_slug'        => 'tabbed-contents-pro',
                    'type'                => 'plugin',
                    'public_key'          => 'pk_787bec9e91498f40f1d0feddec7bc',
                    'is_premium'          => true,
                    'premium_suffix'      => 'Pro',
                    'has_premium_version' => true,
                    'has_addons'          => false,
                    'has_paid_plans'      => true,
                    'trial'               => array(
                        'days'               => 7,
                        'is_require_payment' => false,
                    ),
                    'menu'                => array(
                        'slug'       => 'edit.php?post_type=tabbed-contents',
                        'first-path' => 'edit.php?post_type=tabbed-contents&page=tcb_demo_page',
                        'support'    => false,
                    ),
                );
                $tc_fs = ( TCB_HAS_PRO && file_exists( $fsStartPath ) ? fs_dynamic_init( $tcbConfig ) : fs_lite_dynamic_init( $tcbConfig ) );
            }
            return $tc_fs;
        }

        // Init Freemius.
        tc_fs();
        // Signal that SDK was initiated.
        do_action( 'tc_fs_loaded' );
    }
    // ... Your plugin's main file logic ...
    function tcbIsPremium() {
        return ( TCB_HAS_PRO ? tc_fs()->can_use_premium_code() : false );
    }

    if ( !class_exists( 'TCBPlugin' ) ) {
        class TCBPlugin {
            public function __construct() {
                add_action( 'enqueue_block_assets', [$this, 'enqueueBlockAssets'] );
                add_action( 'init', [$this, 'onInit'] );
                // submenu function hooks
                add_action( 'admin_menu', [$this, 'addSubmenu'] );
                add_action( 'admin_enqueue_scripts', [$this, 'adminEnqueueScripts'] );
                // Post Type function hooks
                add_action( 'init', array($this, 'tcb_tabbed_contents_post_type') );
                // shortcode type function hooks
                add_shortcode( 'tabbed', [$this, 'tcb_shortcode_handler'] );
                //manage column
                add_filter( 'manage_tabbed-contents_posts_columns', [$this, 'tabbedManageColumns'], 10 );
                // Custom manage column
                add_action(
                    'manage_tabbed-contents_posts_custom_column',
                    [$this, 'tabbedManageCustomColumns'],
                    10,
                    2
                );
                // premium checker
                add_action( 'wp_ajax_tcbPipeChecker', [$this, 'tcbPipeChecker'] );
                add_action( 'wp_ajax_nopriv_tcbPipeChecker', [$this, 'tcbPipeChecker'] );
                add_action( 'admin_init', [$this, 'registerSettings'] );
                add_action( 'rest_api_init', [$this, 'registerSettings'] );
            }

            //manage column
            function tabbedManageColumns( $defaults ) {
                unset($defaults['date']);
                $defaults['shortcode'] = 'ShortCode';
                $defaults['date'] = 'Date';
                return $defaults;
            }

            // custom manage column
            function tabbedManageCustomColumns( $column_name, $post_ID ) {
                if ( $column_name == 'shortcode' ) {
                    echo '<div class="bPlAdminShortcode" id="bPlAdminShortcode-' . esc_attr( $post_ID ) . '">
					<input value="[tabbed id=' . esc_attr( $post_ID ) . ']" onclick="copyBPlAdminShortcode(\'' . esc_attr( $post_ID ) . '\')" readonly>
					<span class="tooltip">Copy To Clipboard</span>
				</div>';
                }
            }

            // shortcode function calls
            function tcb_shortcode_handler( $attributes ) {
                $postID = $attributes['id'];
                $post = get_post( $postID );
                $blocks = parse_blocks( $post->post_content );
                ob_start();
                echo render_block( $blocks[0] );
                return ob_get_clean();
            }

            // Custom Post Type function calls
            function tcb_tabbed_contents_post_type() {
                register_post_type( 'tabbed-contents', array(
                    'label'         => 'Tabs',
                    'labels'        => [
                        'add_new'        => 'Add New',
                        'add_new_item'   => 'Add New Tabbed',
                        'edit_item'      => 'Edit Tabbed',
                        'not_found'      => 'There is no tabbed please add one',
                        'item_published' => 'Tabbed Contents Published',
                        'item_updated'   => 'Tabbed Contents Updated',
                    ],
                    'public'        => false,
                    'show_ui'       => true,
                    'show_in_rest'  => true,
                    'menu_icon'     => 'dashicons-welcome-widgets-menus',
                    'template'      => [['tcb/tabs', [], [['tcb/tab'], ['tcb/tab'], ['tcb/tab']]]],
                    'template_lock' => false,
                ) );
            }

            function tcbPipeChecker() {
                $nonce = $_POST['_wpnonce'] ?? null;
                if ( !wp_verify_nonce( $nonce, 'wp_ajax' ) ) {
                    wp_send_json_error( 'Invalid Request' );
                }
                wp_send_json_success( [
                    'isPipe' => tcbIsPremium(),
                ] );
            }

            function registerSettings() {
                register_setting( 'tcbUtils', 'tcbUtils', [
                    'show_in_rest'      => [
                        'name'   => 'tcbUtils',
                        'schema' => [
                            'type' => 'string',
                        ],
                    ],
                    'type'              => 'string',
                    'default'           => wp_json_encode( [
                        'nonce' => wp_create_nonce( 'wp_ajax' ),
                    ] ),
                    'sanitize_callback' => 'sanitize_text_field',
                ] );
            }

            function enqueueBlockAssets() {
                wp_register_style(
                    'fontAwesome',
                    TCB_DIR_URL . 'public/css/font-awesome.min.css',
                    [],
                    '6.4.2'
                );
            }

            function onInit() {
                register_block_type( __DIR__ . '/build/tabs' );
                register_block_type( __DIR__ . '/build/tab' );
            }

            // All hooks function call here
            function addSubmenu() {
                add_submenu_page(
                    'edit.php?post_type=tabbed-contents',
                    'Demo Page',
                    'Demo & Help',
                    'manage_options',
                    'tcb_demo_page',
                    [$this, 'tcb_render_demo_page']
                );
            }

            function renderTemplate( $content ) {
                $parseBlocks = parse_blocks( $content );
                return render_block( $parseBlocks[0] );
            }

            function tcb_render_demo_page() {
                ?>
				<div id="bplAdminHelpPage" data-version='<?php 
                echo esc_attr( TCB_VERSION );
                ?>' data-is-premium='<?php 
                echo esc_attr( tcbIsPremium() );
                ?>'>
					<div class='renderHere'>

					</div>
					<div class="templates" style='display: none;'>
						<div class="default">
							<?php 
                echo $this->renderTemplate( '<!-- wp:tcb/tabs {"tabs":[{"title":"HTML5 Audio Player","icon":{"class":"fa-solid fa-music"}},{"title":"HTML5 Video Player","icon":{"class":"fa-solid fa-video"}},{"title":"PDF Poster","icon":{"class":"fa-solid fa-file-pdf"}}],"contentBG":{"type":"gradient","gradient":"-webkit-linear-gradient(90deg, #020024 0%, #e8f3d6 0%, #f2f6cb 39%, #f2f6ca 70%, #fcf9be 90%)"}} --><!-- wp:tcb/tab --><!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"28px"}}} --><h3 class="wp-block-heading" style="font-size:28px;text-align:left;">HTML5 Audio Player</h3><!-- /wp:heading --><!-- wp:paragraph {"style":{"typography":{"fontSize":"15px"}}} --><p style="font-size:15px;text-align:left;">Welcome to the amazing world of our WordPress plugin, PDF Poster! This plugin is designed to enhance your website\'s functionality and provide a seamless user experience. With its intuitive interface and powerful features, you can easily manage your content and customize your site to your liking. <br>PDF Poster offers a variety of tools to help you optimize your site for search engines, improve your site\'s performance, and engage your audience. Whether you\'re a beginner or an experienced developer, you\'ll find PDF Poster easy to use and highly effective.<br>Experience the difference with PDF Poster and take your website to the next level. Try it out today and see the results for yourself!</p><!-- /wp:paragraph --><!-- /wp:tcb/tab --><!-- wp:tcb/tab {"title":"HTML5 Video Player","icon":{"class":"fa-solid fa-video"}} --><!-- /wp:tcb/tab --><!-- wp:tcb/tab {"title":"PDF Poster","icon":{"class":"fa-solid fa-file-pdf"}} --><!-- /wp:tcb/tab --><!-- /wp:tcb/tabs -->' );
                ?>
						</div>
						<div class="theme1">
							<?php 
                echo $this->renderTemplate( '<!-- wp:tcb/tabs {"tabs":[{"clientId":"16b42722-26f1-4644-a31c-c0878d4f80e6","title":"HTML5 Audio Player","icon":{"class":"fa-solid fa-music"}},{"clientId":"8f28fabf-d266-4c58-a8b2-e3981a999ecf","title":"HTML5 Video Player","icon":{"class":"fa-solid fa-video"}},{"clientId":"8802e79b-58a5-4646-bf97-d018a1e75917","title":"PDF Poster","icon":{"class":"fa-solid fa-file-pdf"}}],"options":{"theme":"theme1"},"TabbedPadding":{"top":"16px","right":"16px","bottom":"16px","left":"16px"},"tabColors":{"color":"#37D1B7","bgType":"solid","bg":"#fff"},"tabActiveColors":{"color":"#1857AF","bgType":"solid","bg":"#fff"},"tabBorder":{"active":{"left":{"width":"0px","type":"solid","color":"#118B50"}},"normal":{"width":0,"color":"#000","style":"solid"}},"titleTypo":{"fontSize":16},"icon":{"size":"18px","color":"#37D1B7","activeColor":"#1857AF"},"borderHeight":{"height":"9px"},"tabMenuBorder":{"width":"0px","menuBColor":"#ccc"},"contentBG":{"type":"solid","color":"rgba(55, 209, 183, 1)"}} --><!-- wp:tcb/tab --><!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"28px"},"elements":{"link":{"color":{"text":"var:preset|color|base-2"}}}},"textColor":"base-2"} --><h3 class="wp-block-heading has-base-2-color has-text-color has-link-color" style="font-size:28px;text-align:left;color:#fff;">HTML5 Audio Player</h3><!-- /wp:heading --><!-- wp:paragraph {"style":{"typography":{"fontSize":"15px"},"elements":{"link":{"color":{"text":"var:preset|color|base-2"}}}},"textColor":"base-2"} --><p class="has-base-2-color has-text-color has-link-color" style="font-size:15px;text-align:left;color:#fff;">Welcome to the amazing world of our WordPress plugin, HTML5 Audio Player! This plugin is designed to enhance your website\'s functionality and provide a seamless user experience. With its intuitive interface and powerful features, you can easily manage your content and customize your site to your liking. <br>HTML5 Audio Player offers a variety of tools to help you optimize your site for search engines, improve your site\'s performance, and engage your audience. Whether you\'re a beginner or an experienced developer, you\'ll find HTML5 Audio Player easy to use and highly effective.<br>Experience the difference with HTML5 Audio Player and take your website to the next level. Try it out today and see the results for yourself!</p><!-- /wp:paragraph --><!-- /wp:tcb/tab --><!-- /wp:tcb/tabs -->' );
                ?>
						</div>

						<div class="theme2">
							<?php 
                echo $this->renderTemplate( '<!-- wp:tcb/tabs {"tabs":[{"clientId":"16b42722-26f1-4644-a31c-c0878d4f80e6","title":"HTML5 Audio Player","icon":{"class":"fa-solid fa-music"}},{"clientId":"8f28fabf-d266-4c58-a8b2-e3981a999ecf","title":"HTML5 Video Player","icon":{"class":"fa-solid fa-video"}},{"clientId":"8802e79b-58a5-4646-bf97-d018a1e75917","title":"PDF Poster","icon":{"class":"fa-solid fa-file-pdf"}}],"options":{"theme":"theme2"},"elements":{"icon":true,"title":true,"isTabBG":true},"TabbedPadding":{"top":"16px","right":"16px","bottom":"16px","left":"16px"},"tabColors":{"color":"#111111","bgType":"solid","bg":"#fff"},"tabActiveColors":{"color":"#fff","bgType":"solid","bg":"rgba(44, 62, 80, 1)"},"tabBorder":{"active":{"left":{"width":"10px","type":"solid","color":"#e48a1f"}},"normal":{"width":0,"color":"#000","style":"solid"}},"titleTypo":{"fontSize":16},"icon":{"size":"15px","color":"#636363","activeColor":"#fff"},"borderHeight":{"height":"9px"},"tabbedBG":{"color":"rgba(249, 249, 249, 1)","type":"solid"},"contentBG":{"type":"solid","color":"rgba(255, 255, 255, 1)"}} --><!-- wp:tcb/tab --><!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"28px"},"elements":{"link":{"color":{"text":"var:preset|color|contrast"}}}},"textColor":"contrast"} --><h3 class="wp-block-heading has-contrast-color has-text-color has-link-color" style="font-size:28px;text-align:left;">HTML5 Audio Player</h3><!-- /wp:heading --><!-- wp:paragraph {"style":{"typography":{"fontSize":"15px"},"elements":{"link":{"color":{"text":"var:preset|color|contrast"}}}},"textColor":"contrast"} --><p class="has-contrast-color has-text-color has-link-color" style="font-size:15px;text-align:left;width:460px;">Welcome to the amazing world of our WordPress plugin, HTML5 Audio Player! This plugin is designed to enhance your website\'s functionality and provide a seamless user experience. With its intuitive interface and powerful features, you can easily manage your content and customize your site to your liking. <br>HTML5 Audio Player offers a variety of tools to help you optimize your site for search engines, improve your site\'s performance, and engage your audience. Whether you\'re a beginner or an experienced developer, you\'ll find HTML5 Audio Player easy to use and highly effective.<br>Experience the difference with HTML5 Audio Player and take your website to the next level. Try it out today and see the results for yourself!</p><!-- /wp:paragraph --><!-- /wp:tcb/tab --><!-- /wp:tcb/tabs -->' );
                ?>
						</div>

						<div class="theme3">
							<?php 
                echo $this->renderTemplate( '<!-- wp:tcb/tabs {"tabs":[{"clientId":"f01f6a1d-7cc3-457e-86ad-9f06121b663f","title":"HTML5 Audio Player","icon":{"class":"fa-solid fa-music"}},{"clientId":"703f1992-2e02-4903-b71f-d292d302528f","title":"HTML5 Video Player","icon":{"class":"fa-solid fa-video"}},{"clientId":"b7ac9df4-15e6-42ee-a87c-6e24aa56808d","title":"PDF Poster","icon":{"class":"fa-solid fa-file-pdf"}}],"options":{"theme":"theme3"},"elements":{"icon":true,"title":false,"isTabBG":false},"tabColors":{"color":"rgba(22, 199, 205, 1)","bgType":"solid","bg":"#fff"},"tabActiveColors":{"color":"#fff","bgType":"solid","bg":"rgba(0, 183, 224, 1)"},"tabBorder":{"active":{"left":{"width":"0px","type":"solid","color":"#118B50"}},"normal":{"width":0,"color":"#000","style":"solid"}},"icon":{"size":"42px","color":"rgba(22, 199, 205, 1)","activeColor":"#fff"},"contentBG":{"type":"solid","color":"rgba(22, 199, 205, 1)"}} --><!-- wp:tcb/tab --><!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"28px"},"elements":{"link":{"color":{"text":"var:preset|color|base"}}}},"textColor":"base"} --><h3 class="wp-block-heading has-base-color has-text-color has-link-color" style="font-size:28px;text-align:left;color:#fff;">HTML5 Audio Player</h3><!-- /wp:heading --><!-- wp:paragraph {"style":{"typography":{"fontSize":"15px"},"elements":{"link":{"color":{"text":"var:preset|color|base"}}}},"textColor":"base"} --><p class="has-base-color has-text-color has-link-color" style="font-size:15px;text-align:left;color:#fff;">Welcome to the amazing world of our WordPress plugin, HTML5 Audio Player! This plugin is designed to enhance your website\'s functionality and provide a seamless user experience. With its intuitive interface and powerful features, you can easily manage your content and customize your site to your liking. <br>HTML5 Audio Player offers a variety of tools to help you optimize your site for search engines, improve your site\'s performance, and engage your audience. Whether you\'re a beginner or an experienced developer, you\'ll find HTML5 Audio Player easy to use and highly effective.<br>Experience the difference with HTML5 Audio Player and take your website to the next level. Try it out today and see the results for yourself!</p><!-- /wp:paragraph --><!-- /wp:tcb/tab --><!-- /wp:tcb/tabs -->' );
                ?>
						</div>

						<div class="theme4">
							<?php 
                echo $this->renderTemplate( '<!-- wp:tcb/tabs {"tabs":[{"clientId":"470bb61e-c589-473e-96fe-15b658a4dc2b","title":"HTML5 Audio Player","icon":{"class":"fa-solid fa-music"}},{"clientId":"eb504570-e6d7-4835-b58c-7baa15c620e6","title":"HTML5 Video Player","icon":{"class":"fa-solid fa-video"}},{"clientId":"8c64ee53-2346-4283-a3b4-012f1b7c3d1b","title":"PDF Poster","icon":{"class":"fa-solid fa-file-pdf"}}],"options":{"theme":"theme4"},"tabColors":{"color":"#fff","bgType":"solid","bg":"#2CC185"},"tabActiveColors":{"color":"#fff","bgType":"solid","bg":"#074799"},"tabBorder":{"active":{"left":{"width":"0px","type":"solid","color":"#118B50"}},"normal":{"width":0,"color":"#000","style":"solid"}},"titleTypo":{"fontSize":16},"icon":{"size":"20px","color":"#fff","activeColor":"#fff"},"contentBG":{"type":"solid","color":"#fff"}} --><!-- wp:tcb/tab --><!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"28px"}}} --><h3 class="wp-block-heading" style="font-size:28px;text-align:left;">HTML5 Audio Player</h3><!-- /wp:heading --><!-- wp:paragraph {"style":{"typography":{"fontSize":"15px"}}} --><p style="font-size:15px;text-align:left;">Welcome to the amazing world of our WordPress plugin, HTML5 Audio Player! This plugin is designed to enhance your website\'s functionality and provide a seamless user experience. With its intuitive interface and powerful features, you can easily manage your content and customize your site to your liking. <br>HTML5 Audio Player offers a variety of tools to help you optimize your site for search engines, improve your site\'s performance, and engage your audience. Whether you\'re a beginner or an experienced developer, you\'ll find HTML5 Audio Player easy to use and highly effective.<br>Experience the difference with HTML5 Audio Player and take your website to the next level. Try it out today and see the results for yourself!</p><!-- /wp:paragraph --><!-- /wp:tcb/tab --><!-- /wp:tcb/tabs -->' );
                ?>
						</div>

						<div class="theme5">
							<?php 
                echo $this->renderTemplate( '<!-- wp:tcb/tabs {"tabs":[{"clientId":"971a76ab-8a1d-419a-bf4c-23ccd43eb43b","title":"HTML5 Audio Player","icon":{"class":"fa-solid fa-music"}},{"clientId":"434c4454-4cdb-4616-b675-c40766c392a1","title":"HTML5 Video Player","icon":{"class":"fa-solid fa-video"}},{"clientId":"4d2bd862-843e-4ccf-9609-e78cbf527a2e","title":"PDF Poster","icon":{"class":"fa-solid fa-file-pdf"}}],"options":{"theme":"theme5"},"elements":{"icon":false,"title":true,"isTabBG":false},"tabsPadding":{"top":"0px","right":"0px","bottom":"10px","left":"0px"},"tabColors":{"color":"#000","bgType":"solid","bg":"#FFFFFF"},"tabActiveColors":{"color":"#fff","bgType":"solid","bg":"#F43F5E"},"tabBorder":{"active":{"left":{"width":"0px","type":"solid","color":"#118B50"}},"normal":{"width":0,"color":"#000","style":"solid"}},"titleTypo":{"fontSize":16},"icon":{"size":"20px","color":"#fff","activeColor":"#fff"},"tabMenuBorder":{"width":"5px","menuBColor":"#F43F5E"},"contentBG":{"type":"solid","color":"#111111"}} -->
							<!-- wp:tcb/tab --><!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"28px"},"elements":{"link":{"color":{"text":"var:preset|color|base-2"}}}},"textColor":"base-2"} --><h3 class="wp-block-heading has-base-2-color has-text-color has-link-color" style="font-size:28px;text-align:left;color:#fff;">HTML5 Audio Player</h3><!-- /wp:heading --><!-- wp:paragraph {"style":{"typography":{"fontSize":"15px"},"elements":{"link":{"color":{"text":"var:preset|color|base-2"}}}},"textColor":"base-2"} --><p class="has-base-2-color has-text-color has-link-color" style="font-size:15px;text-align:left;color:#fff;">Welcome to the amazing world of our WordPress plugin, HTML5 Audio Player! This plugin is designed to enhance your website\'s functionality and provide a seamless user experience. With its intuitive interface and powerful features, you can easily manage your content and customize your site to your liking. <br>HTML5 Audio Player offers a variety of tools to help you optimize your site for search engines, improve your site\'s performance, and engage your audience. Whether you\'re a beginner or an experienced developer, you\'ll find HTML5 Audio Player easy to use and highly effective.</p><!-- /wp:paragraph --><!-- /wp:tcb/tab --><!-- /wp:tcb/tabs -->' );
                ?>
						</div>

						<div class="theme6">
							<?php 
                echo $this->renderTemplate( '<!-- wp:tcb/tabs {"tabs":[{"clientId":"762437bc-54b4-44ac-b211-e09ff0798dcb","title":"HTML5 Audio Player","icon":{"class":"fa-solid fa-music"}},{"clientId":"70fccf42-77f7-4152-b323-3d1f4fc23186","title":"HTML5 Video Player","icon":{"class":"fa-solid fa-video"}},{"clientId":"9053bebb-3646-49b4-bbd7-4130e989c8ab","title":"PDF Poster","icon":{"class":"fa-solid fa-file-pdf"}},{"clientId":"aa284a30-57db-4c7c-bc87-7bf47708334e","title":"HTML5 Audio Player","icon":{"class":"fa-solid fa-music"}}],"options":{"theme":"theme6"},"elements":{"icon":false,"title":true,"isTabBG":false},"tabsPadding":{"top":"0px","right":"0px","bottom":"10px","left":"0px"},"tabColors":{"color":"#ffffff","bgType":"solid","bg":"#111111"},"tabActiveColors":{"color":"#fff","bgType":"solid","bg":"#F43F5E"},"tabBorder":{"active":{"left":{"width":"0px","type":"solid","color":"#118B50"}},"normal":{"width":0,"color":"#000","style":"solid"}},"titleTypo":{"fontSize":16},"icon":{"size":"15px","color":"#9AA6B2","activeColor":"#fff"},"tabbedBG":{"color":"rgba(21, 190, 111, 1)","type":"solid","gradient":"linear-gradient(135deg, #00ff8f, #00a1ff)"},"tabMenuBorder":{"width":"5px","menuBColor":"#F43F5E"},"contentBG":{"type":"solid","color":"#111111"}} -->
							<!-- wp:tcb/tab --><!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"28px"},"elements":{"link":{"color":{"text":"var:preset|color|base-2"}}}},"textColor":"base-2"} --><h3 class="wp-block-heading has-base-2-color has-text-color has-link-color" style="font-size:28px;text-align:left;color:#fff;">HTML5 Audio Player</h3><!-- /wp:heading --><!-- wp:paragraph {"style":{"typography":{"fontSize":"15px"},"elements":{"link":{"color":{"text":"var:preset|color|base-2"}}}},"textColor":"base-2"} --><p class="has-base-2-color has-text-color has-link-color" style="font-size:15px;text-align:left;color:#fff;width:475px;">Welcome to the amazing world of our WordPress plugin, HTML5 Audio Player! This plugin is designed to enhance your website\'s functionality and provide a seamless user experience. With its intuitive interface and powerful features, you can easily manage your content and customize your site to your liking. HTML5 Audio Player offers a variety of tools to help you optimize your site for search engines, improve your site\'s performance, and engage your audience. Whether you\'re a beginner or an experienced</p><!-- /wp:paragraph --><!-- /wp:tcb/tab --><!-- /wp:tcb/tabs -->' );
                ?>
						</div>

						<div class="theme7">
							<?php 
                echo $this->renderTemplate( '<!-- wp:tcb/tabs {"tabs":[{"clientId":"0e395d95-670a-4cee-876d-fde690a9f80c","title":"HTML5 Audio Player","icon":{"class":"fa-solid fa-music"}},{"clientId":"cf9aec0d-3ddb-4586-8878-96ec613f495e","title":"HTML5 Video Player","icon":{"class":"fa-solid fa-video"}},{"clientId":"139f16d9-b3d7-4da6-8206-acc46cc4c29a","title":"PDF Poster","icon":{"class":"fa-solid fa-file-pdf"}}],"options":{"theme":"theme7"},"elements":{"icon":false,"title":true,"isTabBG":true},"tabsPadding":{"top":"0px","right":"0px","bottom":"10px","left":"0px"},"tabColors":{"color":"#000","bgType":"solid","bg":"#FFFFFF"},"tabActiveColors":{"color":"#fff","bgType":"solid","bg":"rgba(255, 152, 0, 1)"},"tabBorder":{"active":{"left":{"width":"0px","type":"solid","color":"#118B50"}},"normal":{"width":0,"color":"#000","style":"solid"}},"titleTypo":{"fontSize":15},"icon":{"size":"14px","color":"#000","activeColor":"#fff"},"tabbedBG":{"color":"rgba(52, 152, 219, 1)","type":"solid"},"tabMenuBorder":{"width":"5px","menuBColor":"#F43F5E"},"contentBG":{"type":"solid","color":"#FFFFFF"}} --><!-- wp:tcb/tab --><!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"28px"}}} --><h3 class="wp-block-heading" style="font-size:28px;text-align:left;">HTML5 Audio Player</h3><!-- /wp:heading --><!-- wp:paragraph {"style":{"typography":{"fontSize":"15px"}}} --><p style="font-size:15px;text-align:left;">Welcome to the amazing world of our WordPress plugin, HTML5 Audio Player! This plugin is designed to enhance your website\'s functionality and provide a seamless user experience. With its intuitive interface and powerful features, you can easily manage your content and customize your site to your liking. <br>HTML5 Audio Player offers a variety of tools to help you optimize your site for search engines, improve your site\'s performance, and engage your audience. Whether you\'re a beginner or an experienced developer, you\'ll find HTML5 Audio Player easy to use and highly effective.<br>Experience the difference with HTML5 Audio Player and take your website to the next level. Try it out today and see the results for yourself!</p><!-- /wp:paragraph --><!-- /wp:tcb/tab --><!-- /wp:tcb/tabs -->' );
                ?>
						</div>
					</div>
				</div>
<?php 
            }

            function adminEnqueueScripts() {
                wp_enqueue_style(
                    'admin-post-css',
                    TCB_DIR_URL . 'build/admin-post-css.css',
                    [],
                    TCB_VERSION
                );
                wp_enqueue_script(
                    'admin-post-js',
                    TCB_DIR_URL . 'build/admin-post.js',
                    [],
                    TCB_VERSION,
                    true
                );
                wp_register_script(
                    'tcb-view',
                    TCB_DIR_URL . 'build/tabs/view.js',
                    ['react', 'react-dom'],
                    TCB_VERSION
                );
                wp_register_style(
                    'fontAwesome',
                    TCB_DIR_URL . 'public/css/font-awesome.min.css',
                    [],
                    TCB_VERSION
                );
                wp_register_style(
                    'tcb-view',
                    TCB_DIR_URL . 'build/tabs/view.css',
                    ['fontAwesome'],
                    TCB_VERSION
                );
                wp_enqueue_script(
                    'fs',
                    TCB_DIR_URL . 'public/js/fs.js',
                    [],
                    '1'
                );
                wp_enqueue_style(
                    'tcb-admin-style',
                    TCB_DIR_URL . 'build/admin-help.css',
                    ['tcb-view'],
                    TCB_VERSION
                );
                wp_enqueue_script(
                    'tcb-admin-help',
                    TCB_DIR_URL . 'build/admin-help.js',
                    [
                        'react',
                        'react-dom',
                        'wp-components',
                        'fs'
                    ],
                    TCB_VERSION
                );
                wp_set_script_translations( 'tcb-admin-help', 'tabbed-contents', TCB_DIR_PATH . 'languages' );
            }

            static function getIconCSS( $icon, $isSize = true, $isColor = true ) {
                extract( $icon );
                $fontSize = $fontSize ?? 16;
                $colorType = $colorType ?? 'solid';
                $color = $color ?? 'inherit';
                $gradient = $gradient ?? 'linear-gradient(135deg, #4527a4, #8344c5)';
                $colorCSS = ( 'gradient' === $colorType ? "color: transparent; background-image: {$gradient}; -webkit-background-clip: text; background-clip: text;" : "color: {$color};" );
                $styles = '';
                $styles .= ( !$fontSize || !$isSize ? '' : "font-size: " . esc_attr( $fontSize ) . "px;" );
                $styles .= ( $isColor ? $colorCSS : '' );
                return $styles;
            }

            static function getColorsCSS( $colors ) {
                extract( $colors );
                $color = $color ?? '#333';
                $bgType = $bgType ?? 'solid';
                $bg = $bg ?? '#0000';
                $gradient = $gradient ?? 'linear-gradient(135deg, #4527a4, #8344c5)';
                $background = ( $bgType === 'gradient' ? $gradient : $bg );
                $styles = '';
                $styles .= ( $color ? "color: " . esc_attr( $color ) . ";" : '' );
                $styles .= ( $gradient || $bg ? "background: " . esc_attr( $background ) . ";" : '' );
                return $styles;
            }

            static function getBackgroundCSS(
                $bg,
                $isSolid = true,
                $isGradient = true,
                $isImage = true
            ) {
                extract( $bg );
                $type = $type ?? 'solid';
                $color = $color ?? '#F5F0BB';
                $gradient = $gradient ?? 'linear-gradient(135deg, #4527a4, #8344c5)';
                $image = $image ?? [];
                $position = $position ?? 'center center';
                $attachment = $attachment ?? 'initial';
                $repeat = $repeat ?? 'no-repeat';
                $size = $size ?? 'cover';
                $overlayColor = $overlayColor ?? '#F5F0BB';
                $gradientCSS = ( $isGradient ? "background: " . esc_attr( $gradient ) . ";" : '' );
                $imgUrl = $image['url'] ?? '';
                $imageCSS = ( $isImage ? "background: url(" . esc_url( $imgUrl ) . "); background-color: " . esc_attr( $overlayColor ) . "; background-position: " . esc_attr( $position ) . "; background-size: " . esc_attr( $size ) . "; background-repeat: " . esc_attr( $repeat ) . "; background-attachment: " . esc_attr( $attachment ) . "; background-blend-mode: overlay;" : '' );
                $solidCSS = ( $isSolid ? "background: " . esc_attr( $color ) . ";" : '' );
                $styles = ( 'gradient' === $type ? $gradientCSS : (( 'image' === $type ? $imageCSS : $solidCSS )) );
                return $styles;
            }

            static function generateCss( $value, $cssProperty ) {
                return ( !$value ? '' : "{$cssProperty}: {$value};" );
            }

            static function getTypoCSS( $selector, $typo, $isFamily = true ) {
                extract( $typo );
                $fontFamily = $fontFamily ?? 'Default';
                $fontCategory = $fontCategory ?? 'sans-serif';
                $fontVariant = $fontVariant ?? 400;
                $fontWeight = $fontWeight ?? 400;
                $isUploadFont = $isUploadFont ?? true;
                $fontSize = $fontSize ?? [
                    'desktop' => 15,
                    'tablet'  => 15,
                    'mobile'  => 15,
                ];
                $fontStyle = $fontStyle ?? 'normal';
                $textTransform = $textTransform ?? 'none';
                $textDecoration = $textDecoration ?? 'auto';
                $lineHeight = $lineHeight ?? '135%';
                $letterSpace = $letterSpace ?? '0px';
                $isEmptyFamily = !$isFamily || !$fontFamily || 'Default' === $fontFamily;
                $desktopFontSize = $fontSize['desktop'] ?? $fontSize;
                $tabletFontSize = $fontSize['tablet'] ?? $desktopFontSize;
                $mobileFontSize = $fontSize['mobile'] ?? $tabletFontSize;
                $styles = (( $isEmptyFamily ? '' : "font-family: '{$fontFamily}', {$fontCategory};" )) . self::generateCss( $fontWeight, 'font-weight' ) . 'font-size: ' . $desktopFontSize . 'px;' . self::generateCss( $fontStyle, 'font-style' ) . self::generateCss( $textTransform, 'text-transform' ) . self::generateCss( $textDecoration, 'text-decoration' ) . self::generateCss( $lineHeight, 'line-height' ) . self::generateCss( $letterSpace, 'letter-spacing' );
                // Google font link
                $linkQuery = ( !$fontVariant || 400 === $fontVariant ? '' : (( '400i' === $fontVariant ? ':ital@1' : (( false !== strpos( $fontVariant, '00i' ) ? ': ital, wght@1, ' . str_replace( '00i', '00', $fontVariant ) . ' ' : ": wght@{$fontVariant} " )) )) );
                $link = ( $isEmptyFamily ? '' : 'https://fonts.googleapis.com/css2?family=' . str_replace( ' ', '+', $fontFamily ) . "{$linkQuery}&display=swap" );
                return [
                    'googleFontLink' => ( !$isUploadFont || $isEmptyFamily ? '' : "@import url( {$link} );" ),
                    'styles'         => preg_replace( '/\\s+/', ' ', trim( "\r\n\t\t\t\t\t\t{$selector}{ {$styles} }\r\n\t\t\t\t\t\t@media (max-width: 768px) {\r\n\t\t\t\t\t\t\t{$selector}{ font-size: {$tabletFontSize}" . "px; }\r\n\t\t\t\t\t\t}\r\n\t\t\t\t\t\t@media (max-width: 576px) {\r\n\t\t\t\t\t\t\t{$selector}{ font-size: {$mobileFontSize}" . "px; }\r\n\t\t\t\t\t\t}\r\n\t\t\t\t\t" ) ),
                ];
            }

            public static function getBorderBoxCSS( $border ) {
                if ( empty( $border ) ) {
                    return '';
                }
                // Closure to generate individual border CSS
                $generateBorderCSS = function ( $borderObj ) {
                    $color = ( isset( $borderObj['color'] ) ? $borderObj['color'] : '#000000' );
                    $style = ( isset( $borderObj['style'] ) ? $borderObj['style'] : 'solid' );
                    $width = ( isset( $borderObj['width'] ) ? $borderObj['width'] : '0px' );
                    return "{$width} {$style} {$color}";
                };
                // If the border is an associative array
                if ( is_array( $border ) && array_keys( $border ) !== range( 0, count( $border ) - 1 ) ) {
                    $sides = [
                        'top',
                        'right',
                        'bottom',
                        'left'
                    ];
                    $css = '';
                    foreach ( $sides as $side ) {
                        if ( isset( $border[$side] ) ) {
                            $css .= "border-{$side}: " . $generateBorderCSS( $border[$side] ) . "; ";
                        }
                    }
                    // If no specific sides are defined, treat it as a general border
                    if ( empty( trim( $css ) ) ) {
                        $css = "border: " . $generateBorderCSS( $border ) . ";";
                    }
                    return trim( $css );
                }
                return '';
            }

        }

        new TCBPlugin();
    }
}