<?php
/**
 * Modified WordPress Installer
 *
 * @package WordPress
 * @subpackage Administration
 */

/**
 * We are installing WordPress.
 *
 * @since 1.5.1
 * @var bool
 */
define( 'WP_INSTALLING', true );

/** Load WordPress Bootstrap */
require_once( dirname( dirname( __FILE__ ) ) . '/wp-load.php' );

/** Load WordPress Administration Upgrade API */
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

/** Load WordPress Translation Install API */
require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );

/** Load wpdb */
require_once( ABSPATH . WPINC . '/wp-db.php' );

nocache_headers();

$step = isset( $_GET['step'] ) ? (int) $_GET['step'] : 0;

/**
 * Display install header.
 *
 * @since 2.5.0
 *
 * @param string $body_classes
 */
function display_header( $body_classes = '' ) {
	header( 'Content-Type: text/html; charset=utf-8' );
	if ( is_rtl() ) {
		$body_classes .= 'rtl';
	}
	if ( $body_classes ) {
		$body_classes = ' ' . $body_classes;
	}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
	<meta name="viewport" content="width=device-width" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php _e( 'WordPress &rsaquo; Installation' ); ?></title>
	<?php
		wp_admin_css( 'install', true );
		wp_admin_css( 'dashicons', true );
	?>
</head>
<body class="wp-core-ui<?php echo $body_classes ?>">
<h1 id="logo"><a href="<?php echo esc_url( __( 'https://wordpress.org/' ) ); ?>" tabindex="-1"><?php _e( 'WordPress' ); ?></a></h1>

<?php
} // end display_header()

// check if blog is already installed
if ( is_blog_installed() ) {
	display_header();
	die( '<h1>' . __( 'Already Installed' ) . '</h1><p>' . __( 'You appear to have already installed WordPress. To reinstall please clear your old database tables first.' ) . '</p><p class="step"><a href="../wp-login.php" class="button button-large">' . __( 'Log In' ) . '</a></p></body></html>' );
}

if ( ! empty( $language ) && load_default_textdomain( $language ) ) {
	$loaded_language = $language;
	$GLOBALS['wp_locale'] = new WP_Locale();
} else {
	$loaded_language = 'en_US';
}

if ( ! empty( $wpdb->error ) )
	wp_die( $wpdb->error->get_error_message() );

display_header();
// Fill in the data we gathered
$weblog_title = isset( $_ENV['WPINSTALL_TITLE'] ) ? trim( wp_unslash( $_ENV['WPINSTALL_TITLE'] ) ) : '';
$user_name = isset($_ENV['WPINSTALL_USERNAME']) ? trim( wp_unslash( $_ENV['WPINSTALL_USERNAME'] ) ) : '';
$admin_password = isset($_ENV['WPINSTALL_PASSWORD']) ? wp_unslash( $_ENV['WPINSTALL_PASSWORD'] ) : '';
$admin_email  = isset( $_ENV['WPINSTALL_EMAIL'] ) ?trim( wp_unslash( $_ENV['WPINSTALL_EMAIL'] ) ) : '';
$public       = isset( $_ENV['WPINSTALL_PUBLIC'] ) ? (int) $_ENV['WPINSTALL_PUBLIC'] : 0;

$result = wp_install( $weblog_title, $user_name, $admin_email, $public, '', wp_slash( $admin_password ), $loaded_language );

$setting = Array();
$setting['post_meta_version'] = 3;
$setting['manual_bucket'] = 1;
$setting['bucket'] = str_replace('s3://', '', getenv('CDN_S3_BUCKET')); 
$setting['region'] = getenv('CDN_REGION');
$setting['domain'] = 'cloudfront';
$setting['expires'] = 0;
$setting['cloudfront'] = str_replace('http://', '', getenv('CDN_URL'));
$setting['object-prefix'] = 'app/uploads/';
$setting['copy-to-s3'] = 1;
$setting['serve-from-s3'] = 1;
$setting['remove-local-file'] = 1;
$setting['ssl'] = 'request';
$setting['hidpi-images'] = 0;
$setting['object-versioning'] = 1;
$setting['use-yearmonth-folders'] = 1;
$setting['enable-object-prefix'] = 1;

add_option( 'tantan_wordpress_s3', serialize($setting), '', 'yes' );      
$plugin1 = activate_plugin( 'amazon-web-services/amazon-web-services.php' );
$plugin2 = activate_plugin( 'amazon-s3-and-cloudfront/wordpress-s3.php' );

?>

<h1><?php _e( 'Success!' ); ?></h1>

<p><?php _e( 'WordPress has been installed. Were you expecting more steps? Sorry to disappoint.' ); ?></p>


<p class="step"><a href="../wp-login.php" class="button button-large"><?php _e( 'Log In' ); ?></a></p>

<?php
if ( !wp_is_mobile() ) {
?>
<script type="text/javascript">var t = document.getElementById('weblog_title'); if (t){ t.focus(); }</script>
<?php } ?>
<?php wp_print_scripts( 'user-profile' ); ?>
<?php wp_print_scripts( 'language-chooser' ); ?>
<script type="text/javascript">
jQuery( function( $ ) {
	$( '.hide-if-no-js' ).removeClass( 'hide-if-no-js' );
} );
</script>
</body>
</html>
