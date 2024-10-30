<?php 
/**
 * @internal never define functions inside callbacks.
 * these functions could be run multiple times; this would result in a fatal error.
 */
 
/**
 * custom option and settings
 */
function ithink_settings_init() {
 // register a new setting for "ithink" page
 register_setting( 'ithink', 'ithink_options' );
 
 // register a new section in the "ithink" page
 add_settings_section(
 'ithink_section_developers',
 __( 'iThink Shipping settings ', 'ithink' ),
 'ithink_section_developers_cb',
 'ithink'
 );
 

 add_settings_field( 'ithink_field_secretkey',  __( 'Secret key', 'ithink' ), 'ithink_field_secretkey_cb', 'ithink', 'ithink_section_developers', [ 'label_for' => 'ithink_field_secretkey',
 'class' => 'ithink_row', 'ithink_custom_data' => 'custom2', ] );
 
 add_settings_field( 'ithink_field_accesstoken',  __( 'Access token', 'ithink' ), 'ithink_field_accesstoken_cb', 'ithink', 'ithink_section_developers', [ 'label_for' => 'ithink_field_accesstoken',
 'class' => 'ithink_row', 'ithink_custom_data' => 'custom3', ] );
}
 
/**
 * register our ithink_settings_init to the admin_init action hook
 */
add_action( 'admin_init', 'ithink_settings_init' );
 
/**
 * custom option and settings:
 * callback functions
 */
 

function ithink_field_secretkey_cb($args) {
// get the value of the setting we've registered with register_setting()
 $options = get_option( 'ithink_options' );?>
  <input style="width:29%" type="text" name="ithink_options[<?php echo esc_attr( $args['label_for'] ); ?>]"  id="<?php echo esc_attr( $args['label_for'] ); ?>"
 data-custom="<?php echo esc_attr( $args['ithink_custom_data'] ); ?>" value="<?php echo  $options[ $args['label_for'] ] ;?>">

<?php }

function ithink_field_accesstoken_cb($args) {
// get the value of the setting we've registered with register_setting()
 $options = get_option( 'ithink_options' );?>
  <input style="width:29%"  type="text" name="ithink_options[<?php echo esc_attr( $args['label_for'] ); ?>]"  id="<?php echo esc_attr( $args['label_for'] ); ?>"
 data-custom="<?php echo esc_attr( $args['ithink_custom_data'] ); ?>" value="<?php echo  $options[ $args['label_for'] ] ;?>">

<?php }
/**
 * top level menu
 */
function ithink_options_page() {
 // add top level menu page
 add_menu_page(
 'ithink',
 'ithink Options',
 'manage_options',
 'ithink',
 'ithink_options_page_html'
 );
}
 
/**
 * register our ithink_options_page to the admin_menu action hook
 */
add_action( 'admin_menu', 'ithink_options_page' );
 
/**
 * top level menu:
 * callback functions
 */
function ithink_options_page_html() {
 // check user capabilities
 if ( ! current_user_can( 'manage_options' ) ) {
 return;
 }
 
 // add error/update messages
 
 // check if the user have submitted the settings
 // wordpress will add the "settings-updated" $_GET parameter to the url
 if ( isset( $_GET['settings-updated'] ) ) {
 // add settings saved message with the class of "updated"
 add_settings_error( 'ithink_messages', 'ithink_message', __( 'Settings Saved', 'ithink' ), 'updated' );
 }
 
 // show error/update messages
 settings_errors( 'ithink_messages' );
 ?>
 <div class="wrap">
 <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
 <form action="options.php" method="post">
 <?php
 // output security fields for the registered setting "ithink"
 settings_fields( 'ithink' );
 // output setting sections and their fields
 // (sections are registered for "ithink", each field is registered to a specific section)
 do_settings_sections( 'ithink' );
 // output save settings button
 submit_button( 'Save Settings' );
 ?>
 </form>
 </div>
 <?php
}