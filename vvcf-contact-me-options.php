<?php


function vvcf_my_plugin_options()
{
    if (!current_user_can('administrator')) {
        wp_die(__(esc_html('You do not have sufficient permissions to access this page.')));
    } else {
        $options = get_option('checkbox_option');
        
        
        echo '<div class="wrap">
		<h1>Contact Me - Options</h1> </div>
		<h3>Managing form options</h3>

    <form action="options.php" method="post">';
        settings_fields('setting_upload');
        do_settings_sections('setting_upload');
        echo '
      <label class="vvcf-admin-label" for="checkbox">' . esc_html('Allow user to upload files:') . '</label>
      <input class="vvcf-admin-check" type="checkbox" id="checkbox_example" name="checkbox_option" value="1"';
        checked(get_option('checkbox_option'), 1);
        echo '/>
			<label class="vvcf-admin-subtitle">' . esc_html('(The allowed formats are "pdf", "doc", "docx", "gif", "jpeg", "jpg", "png", "txt")') . '</label>
      <input type="hidden" name="vv-nonce" value="' . wp_create_nonce('vv-nonce') . '"/>
      <input class="button-primary vvcf-admin-submit" type="submit" value="Save Settings" />
    </form>';
    }
    
    function vvcf_verify_nonce_check()
    {
        if (isset($_POST['vv-nonce'])) {
            if (wp_verify_nonce($_POST['vv-nonce'], 'vv-nonce')) {
            } else {
                echo 'fail';
                exit;
            }
        }
    }
    add_action('init', 'vvcf_verify_nonce_check');
}

?>