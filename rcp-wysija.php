<?php
/*
Plugin Name: Restrict Content Pro - Wysija
Plugin URL: http://pippingplugins.com/rcp-wysija
Description: Include a Wysija signup option with your Restrict Content Pro registration form
Version: 1.0
Author: Pippin Williamson
Author URI: http://pippinsplugins.com
Contributors: mordauk
Text Domain: rcp_wysija
Domain Path: languages
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


/*
|--------------------------------------------------------------------------
| INTERNATIONALIZATION
|--------------------------------------------------------------------------
*/

function rcp_wysija_textdomain() {

	// Set filter for plugin's languages directory
	$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
	$lang_dir = apply_filters( 'rcp_wysija_languages_directory', $lang_dir );

	// Load the translations
	load_plugin_textdomain( 'rcp_wysija', false, $lang_dir );
}
add_action('init', 'rcp_wysija_textdomain');


/*
|--------------------------------------------------------------------------
| Settings
|--------------------------------------------------------------------------
*/

function rcp_Wysija_settings_menu() {
	// add settings page
	add_submenu_page( 'rcp-members', __( 'Restrict Content Pro Wysija Settings', 'rcp_wysija' ), __('Wysija', 'rcp_wysija' ),'manage_options', 'rcp-Wysija', 'rcp_wysija_settings_page');
}
add_action('admin_menu', 'rcp_Wysija_settings_menu', 100);

// register the plugin settings
function rcp_wysija_register_settings() {

	// create whitelist of options
	register_setting( 'rcp_wysija_settings_group', 'rcp_wysija_settings' );
}
add_action( 'admin_init', 'rcp_Wysija_register_settings', 100 );

// Render out settings page
function rcp_wysija_settings_page() {

	$options = get_option('rcp_wysija_settings');

	?>
	<div class="wrap">
		<h2><?php _e('Restrict Content Pro Wysija Settings', 'rcp_wysija'); ?></h2>
		<?php
		if ( ! isset( $_REQUEST['updated'] ) )
			$_REQUEST['updated'] = false;
		?>
		<?php if ( false !== $_REQUEST['updated'] ) : ?>
		<div class="updated fade"><p><strong><?php _e( 'Options saved', 'rcp_wysija' ); ?></strong></p></div>
		<?php endif; ?>
		<form method="post" action="options.php" class="rcp_options_form">

			<?php settings_fields( 'rcp_wysija_settings_group' ); ?>
			<?php $lists = rcp_wysija_get_lists(); ?>

			<table class="form-table">
				<tr>
					<th>
						<label for="rcp_wysija_settings[wysija_list]"><?php _e( 'Newsletter List', 'rcp_wysija' ); ?></label>
					</th>
					<td>
						<select id="rcp_wysija_settings[wysija_list]" name="rcp_wysija_settings[wysija_list]">
							<?php
								if($lists) :
									foreach( $lists as $list_id => $list ) :
										echo '<option value="' . esc_attr( $list_id ) . '"' . selected( $options['wysija_list'], $list_id, false ) . '>' . esc_html( $list ) . '</option>';
									endforeach;
								else :
							?>
							<option value="no list"><?php _e( 'no lists', 'rcp_wysija' ); ?></option>
						<?php endif; ?>
						</select>
						<div class="description"><?php _e( 'Choose the list to subscribe users to', 'rcp_wysija' ); ?></div>
					</td>
				</tr>
				<tr>
					<th>
						<label for="rcp_wysija_settings[signup_label]"><?php _e( 'Form Label', 'rcp_wysija' ); ?></label>
					</th>
					<td>
						<input class="regular-text" type="text" id="rcp_wysija_settings[signup_label]" name="rcp_wysija_settings[signup_label]" value="<?php if( ! empty( $options['signup_label'] ) ) { echo esc_html( $options['signup_label'] ); } ?>"/>
						<div class="description"><?php _e( 'Enter the label to be shown on the "Signup for Newsletter" checkbox', 'rcp_wysija' ); ?></div>
					</td>
				</tr>
			</table>
			<!-- save the options -->
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e( 'Save Options', 'rcp_wysija' ); ?>" />
			</p>

		</form>
	</div><!--end .wrap-->
	<?php
}


/*
|--------------------------------------------------------------------------
| Retrieves Lists and Subscribe User and Signup Field
|--------------------------------------------------------------------------
*/

// Retrieve an array of list IDs and names
function rcp_wysija_get_lists() {

	$options = get_option('rcp_wysija_settings');

	$lists = array();

	if( ! class_exists( 'WYSIJA' ) )
		return $lists;

	$modelList   = &WYSIJA::get( 'list','model' );
	$wysijaLists = $modelList->get( array( 'name', 'list_id' ), array( 'is_enabled' => 1 ) );


	if( ! empty( $wysijaLists ) ) {
		foreach( $wysijaLists as $list ) {

			$lists[ $list['list_id'] ] = $list['name'];
		}
	}
	return $lists;
}

// adds an email to the wysija subscription list
function rcp_wysija_subscribe_email( $user_info ) {

	$options = get_option('rcp_wysija_settings');

	if( ! class_exists( 'WYSIJA' ) )
		return false;
	//echo 'test'; exit;
	$list_id = isset( $options['wysija_list'] ) ? $options['wysija_list'] : false;

	if( $list_id ) {

		$user_data = array(
			'email'     => $user_info['email'],
			'firstname' => $user_info['first_name'],
			'lastname'  => $user_info['last_name'],
		);

		$data = array(
	      	'user'      => $user_data,
	      	'user_list' => array( 'list_ids' => array( $list_id ) )
	    );

		$userHelper = &WYSIJA::get( 'user','helper' );
		$userHelper->addSubscriber( $data );

	} else {
		return false;
	}
}


// displays the wysija checkbox
function rcp_wysija_fields() {


	$options = get_option('rcp_wysija_settings');

	ob_start();
		if( isset( $options['wysija_list'] ) ) { ?>
		<p>
			<input name="rcp_wysija_signup" id="rcp_wysija_signup" type="checkbox" checked="checked"/>
			<label for="rcp_wysija_signup"><?php echo isset( $options['signup_label'] ) ? $options['signup_label'] : __( 'Sign up for our mailing list', 'rcp_wysija' ); ?></label>
		</p>
		<?php
	}
	echo ob_get_clean();
}
add_action( 'rcp_before_registration_submit_field', 'rcp_wysija_fields', 100 );

// checks whether a user should be signed up for he wysija list
function rcp_check_for_email_signup( $posted = array(), $user_id = 0 ) {
	if( isset( $posted['rcp_wysija_signup'] ) ) {

		$user_info = array();

		if( is_user_logged_in() ) {
			$user_data 	             = get_userdata( $user_id );
			$user_info['email']      = $user_data->user_email;
			$user_info['first_name'] = $user_data->first_name;
			$user_info['last_name']  = $user_data->last_name;
		} else {
			$user_info['email']      = sanitize_email( $posted['rcp_user_email'] );
			$user_info['first_name'] = sanitize_text_field( $posted['rcp_user_first'] );
			$user_info['last_name']  = sanitize_text_field( $posted['rcp_user_last'] );
		}
		rcp_wysija_subscribe_email( $user_info );
	}
}
add_action( 'rcp_form_processing', 'rcp_check_for_email_signup', 10, 2 );