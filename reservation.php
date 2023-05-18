<?php
/*
Plugin Name: Reservatie
Plugin URI: https://junnect.nl
Description: A simple reservation plugin 
Version: 1.2
Author: JUNNECT
Author URI: https://junnect.nl
*/

// Make sure we don't expose any info if called directly
if (!function_exists('add_action')) {
    echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
    exit;
}

define('LRESERVATION_VERSION', '1.2');
define('LRESERVATION_PLUGIN_DIR', plugin_dir_path(__FILE__));

require_once(LRESERVATION_PLUGIN_DIR . 'class.reservation.php');

register_activation_hook(__FILE__, 'reservation_install');

function reservation_style() {
    wp_enqueue_style('reservation_style', plugins_url('style.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'reservation_style');

function reservation_install() {
    global $wpdb;
    global $reservation_db_version;

    $table_name = $wpdb->prefix . 'reservations';
    
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        encrypted_id varchar(55) NOT NULL,
        email varchar(55) NOT NULL,
        date varchar(55) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    add_option('reservation_db_version', $reservation_db_version);
}

function handle_reservation_acceptance() {
    // Check if the reservation_accept query parameter is present
    if (!empty($_GET['reservation_accept'])) {
        // Get the reservation ID
        $reservation_id = sanitize_text_field($_GET['reservation_accept']);

        // Query the database to get the reservation holder's email
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservations';

        $reservation_email = $wpdb->get_var($wpdb->prepare("SELECT email FROM $table_name WHERE encrypted_id = %s", $reservation_id));

        echo '<script type="text/javascript">alert("'.$reservation_email.'");</script>';
        echo '<script type="text/javascript">alert("'.$reservation_id.'");</script>';

        // Check if the reservation exists
        if ($reservation_email) {
            // Send an email to the reservation holder
            $to = $reservation_email;
            $subject = 'Je reservering is geaccepteerd door Eetboetiek Festina Lente';
            $message = "Je reservering is geaccepteerd door Eetboetiek Festina Lente. Tot snel!";
            $headers = array('Content-Type: text/html; charset=UTF-8');

            wp_mail($to, $subject, $message, $headers);
            
            // Remove the reservation from the database
            $wpdb->delete($table_name, array('encrypted_id' => $reservation_id));
            // Redirect to a thank you page
            wp_redirect(home_url('/reservering_geaccepteerd/?success=true'));
            exit;
        }
        // Redirect to a thank you page
        wp_redirect(home_url('/reservering_geaccepteerd/?success=false'));
        exit;
    }
}
add_action('init', 'handle_reservation_acceptance');

// Add a shortcode to show the succes to reservering_geaccepteerd page
function reservation_success_shortcode() {
    if (!empty($_GET['success']) && $_GET['success'] == 'true') {
        return '<p class="reservation-success">De reservering is bevestigd en de klant is ingelicht! Je hoeft verder niets te doen.</p>';
    } else {
        return '<p class="reservation-success failure">Er is iets mis gegaan met de reservering. Je kunt het beste de klant zelf even inlichten.</p>';
    }
}
add_shortcode('reservation_success', 'reservation_success_shortcode');

// Instantiate the class and set up WordPress actions/filters
ReservationPlugin::init();