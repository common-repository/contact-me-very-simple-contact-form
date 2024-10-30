<?php
/*
Plugin Name: Contact Me - Very Simple Contact Form
Plugin URI: https://ronisset.000webhostapp.com/contact-form
Description: Simple contact form with upload feature.
Version: 0.0.3
Author: Rorinka
Author URI: https://ronisset.000webhostapp.com/
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin, like WordPress, is licensed under the GPL.
*/

// disable direct access
if (!defined('ABSPATH')) {
    exit;
}
function vvcf_load_custom_wp_admin_style($hook)
{
    // Load only on ?page=mypluginname
    if ($hook != $GLOBALS['my_plugin_page']) {
        return;
    }
    wp_enqueue_style('custom_wp_admin_css', plugins_url('/styles/admin-style.css', __FILE__));
}
add_action('admin_enqueue_scripts', 'vvcf_load_custom_wp_admin_style');

function vvcf_load_plugin_css()
{
    $plugin_url = plugin_dir_url(__FILE__);
    
    wp_enqueue_style('style1', $plugin_url . '/styles/style.css');
}
add_action('wp_enqueue_scripts', 'vvcf_load_plugin_css');

//html form

function vvcf_html_form_code()
{
    
    /* if ( !current_user_can( 'manage_options' ) )  {
    wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    } */
    echo '<form action="' . esc_url($_SERVER['REQUEST_URI']) . '" method="post" enctype="multipart/form-data" class="attachment-form">';
    echo '<p>';
    echo 'Name <br />';
    echo '<input type="text" name="namep" placeholder="'. esc_attr('Your Name').'"/>';
    echo '</p>';
    echo '<p>';
    echo 'Email<br />';
    echo '<input type="email" name="email" placeholder="'. esc_attr('Your email').'"/>';
    echo '</p>';
    echo '<p>';
    echo 'Subject<br />';
    echo '<input type="text" placeholder="'. esc_attr('Your subject').'" name="sbj" pattern="[a-zA-Z ]+" size="40"  />';
    echo '</p>';
    echo '<p>';
    echo 'Message<br />';
    echo '<textarea name="msg" placeholder="'. esc_attr('Your message').'"></textarea>';
    echo '</p>';
    echo '<p class="';
    echo !get_option('checkbox_option') ? 'vvcf-hide' : '';
    echo '">';
    echo '<input  type="file" name="attach1"/><label class="plugin-subtitle">(Allowed formats: "pdf", "doc", "docx", "gif", "jpeg", "jpg", "png", "txt")</label><br>';
    echo '</p>';
    echo '<p> <input type="hidden" name="vv-nonce1" value="' . wp_create_nonce('vv-nonce1') . '"/><input type="submit" name="vvcf-submit" value="Send"></p>';
    
    echo '</form>';
}

// nonce
function vvcf_verify_nonce_send()
{
    if (isset($_POST['vv-nonce1'])) {
        if (wp_verify_nonce($_POST['vv-nonce1'], 'vv-nonce1')) {
        } else {
            echo 'Your nonce is not verified!';
            exit;
        }
    }
    
}
add_action('init', 'vvcf_verify_nonce_send');


//delivery mail
function vvcf_deliver_mail()
{
    // if the submit button is clicked, send the email

    if (isset($_FILES) && (bool) $_FILES) {
        
        $allowedExtensions = array(
            "pdf",
            "PDF",
            "doc",
            "DOC",
            "docx",
            "DOCX",
            "gif",
            "GIF",
            "jpeg",
            "JPEG",
            "jpg",
            "JPG",
            "png",
            "PNG",
            "txt",
            "TXT"
        );
         $maxsize    = 2097152;
        
        $files = array();
        foreach ($_FILES as $name => $file) {
            $file_name  = $file['name'];
            $temp_name  = $file['tmp_name'];
            $file_type  = $file['type'];
            $path_parts = pathinfo($file_name);
            $ext        = $path_parts['extension'];
            $size       = $file['size'];
            if (get_option('checkbox_option')) {
                if (!in_array($ext, $allowedExtensions)) {
                    echo "File $file_name has the extensions $ext which is not allowed";
                }
                if ($size > $maxsize) {
                    echo "File $file_nameFile must be less than 2 megabyte";
                }
                
            }
            
            
            array_push($files, $file);
        }
        
        // sanitize form values
        
        // get the blog administrator's email address
        $to      = get_option('admin_email');
        $from    = sanitize_email($_POST["email"]); //your website email type here
        $subject = sanitize_text_field($_POST['sbj']);
        $msg     = sanitize_text_field($_POST['msg']);
        $namep   = sanitize_text_field($_POST['namep']);
        
        
        $headers[]     = 'Reply-To: <' . $from . '>';
        $message       = '<div style="background-color: #f7f7f7; color:#8a8a8a; padding:1px 13px;"><p><b>Sent from:</b> ' . $from . '  ( ' . $namep . ' )</p><p><b>Subject:</b> ' . $subject . '</p></div><div style="border-weight:1px;border-color:#f7f7f7;border-style:solid; padding:10px 13px;">' . $msg . '</div>';
        $semi_rand     = md5(time());
        $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";
        $headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed; charset=UTF-8;\n" . " boundary=\"{$mime_boundary}\"";
        
        $message = "This is a multi-part message in MIME format.\n\n" . "--{$mime_boundary}\n" . "Content-Type: text/html; charset=\"iso-8859-1\"\n" . "Content-Transfer-Encoding: 7bit\n\n" . $message . "\n\n";
        $message .= "--{$mime_boundary}\n";
        
        
        if (get_option('checkbox_option')) {
            // preparing attachments
            for ($x = 0; $x < count($files); $x++) {
                $file = fopen($files[$x]['tmp_name'], "rb");
                $data = fread($file, filesize($files[$x]['tmp_name']));
                fclose($file);
                $data = chunk_split(base64_encode($data));
                $name = $files[$x]['name'];
                $message .= "Content-Type: {\"application/octet-stream\"};\n" . " name=\"$name\"\n" . "Content-Disposition: attachment;\n" . " filename=\"$name\"\n" . "Content-Transfer-Encoding: base64\n\n" . $data . "\n\n";
                $message .= "--{$mime_boundary}\n";
            }
        }
        
        //validations
        if (isset($_POST['vvcf-submit'])) {
            $errors = array();
            
            if ( !preg_match ("/^[a-zA-Z\s]+$/",$namep) || strlen($namep) > 50) {
               $errors[] = "Please enter valid name ;";
            } 
           if (!filter_var($from, FILTER_VALIDATE_EMAIL) || strlen($namep) > 50) {
                $errors[] ='Please enter valid email;';
            } else {
                $from = $from;
            }
           if (empty($subject)|| strlen($namep) > 50) {
                $errors[] = 'Please enter subject;';
            } else {
                $subject = $subject;
            }
           if (empty($msg)|| strlen($namep) > 1000) {
                $errors[] = 'Please enter message;';
            } else {
                $msg = $msg;
            }
          
            if (isset($errors)) {
                echo '<ul class="vvcf-errors">';
                
                foreach ($errors as $error)
                    echo '<li>' . $error . '</li>';
                echo '</ul>';
            }
            
            if (empty($errors)) {
                $ok = wp_mail($to, $subject, $message, $headers);
            }
            
        }
        
        if ($ok) {
            echo "<p>". esc_html('Thanks for contacting me, expect a response soon')."</p>";
        } else {
            echo "<p>". esc_html('The form wasnt send.')."</p>";
        }
    }
    
}

//shortcode 
function vvcf_shortcode()
{
    ob_start();
    vvcf_deliver_mail();
    vvcf_html_form_code();
    
    return ob_get_clean();
}

add_shortcode('vv-contact-form', 'vvcf_shortcode');


/** Step 2 (from text above). */
add_action('admin_menu', 'vvcf_my_plugin_menu');

/** Step 1. */
function vvcf_my_plugin_menu()
{
    $GLOBALS['my_plugin_page'] = add_options_page('Contact Me - Options', 'Contact Me', 'manage_options', 'vvcf-contact-me', 'vvcf_my_plugin_options');
    //call register settings function
    
}

/** Step 3. */

add_action('admin_init', 'vvcf_register_mysettings');
function vvcf_register_mysettings()
{
    //register our settings
    register_setting('setting_upload', 'checkbox_option');
}
include 'vvcf-contact-me-options.php';
?>