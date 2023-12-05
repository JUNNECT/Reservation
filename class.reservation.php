<?php
class ReservationPlugin {
    public static function init() {
        add_action('admin_post_nopriv_submit_reservation', array('ReservationPlugin', 'handle_reservation_form'));
        add_action('admin_post_submit_reservation', array('ReservationPlugin', 'handle_reservation_form'));
        add_shortcode('reservation_form', array('ReservationPlugin', 'render_reservation_form'));
    }

    public static function handle_reservation_form() {
        // Check nonce for security
        check_admin_referer('reservation_verify');

        if (!isset($_POST['g-recaptcha-response'])) {
            // Redirect to current page with error message
            $query = 'error';
            $query_val = 'recaptcha_not_sent';
            wp_redirect(home_url('/reservering_geaccepteerd/?success=recaptcha&error=recaptcha_not_sent'));
            exit();
        } else {
        
            // Verify reCAPTCHA
            $recaptcha_secret = get_option('reservation_recaptche_secret');
            $recaptcha_response = sanitize_text_field($_POST['g-recaptcha-response']);
            
            $response = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', array(
                'body' => array(
                    'secret' => $recaptcha_secret,
                    'response' => $recaptcha_response,
                )
            ));
            
            $response_keys = json_decode(wp_remote_retrieve_body($response), true);
            
            if (intval($response_keys["success"]) !== 1) {
                // Turnstile verification failed
                $query = 'error';
                $query_val = 'recaptcha_verify_failed';
                wp_redirect(home_url('/reservering_geaccepteerd/?success=recaptcha&error=recaptcha_verify_failed'));
                exit();
            } else {

                // Process form data and send email
                $reservation_holder_voornaam = sanitize_text_field($_POST['reservation_holder_voornaam']);
                $reservation_holder_achternaam = sanitize_text_field($_POST['reservation_holder_achternaam']);
                $reservation_email = sanitize_email($_POST['reservation_email']);
                $reservation_phone = sanitize_text_field($_POST['reservation_phone']);
                $reservation_guests = sanitize_text_field($_POST['guests']);
                if(isset($_POST['reservation_type'])) {
                    $reservation_type = sanitize_text_field($_POST['reservation_type']);
                } else {
                    $reservation_type = 'lentes_menu';
                }

                $reservation_time_lunch = sanitize_text_field($_POST['reservation_time_lunch']);

                $reservation_time_high_tea = sanitize_text_field($_POST['reservation_time_high_tea']);

                $reservation_time_menu = sanitize_text_field($_POST['reservation_time_menu']);

                $reservation_date = sanitize_text_field($_POST['reservation_date']);

                // reformat date to DD-MM-YYYY
                // $reservation_date = date("d-m-Y", strtotime($reservation_date));

                $special_request_text = sanitize_text_field($_POST['special_request_text']);
                $reservation_id = self::generate_random_id();

                // Store reservation data in the database
                global $wpdb;
                $table_name = $wpdb->prefix . 'reservations';

                $wpdb->insert(
                    $table_name,
                    array(
                        'encrypted_id' => $reservation_id,
                        'email' => $reservation_email,
                    ),
                    array(
                        '%s',  // The format of the id field
                        '%s',  // The format of the email field
                    )
                );

                // Create reservation acceptance link
                $accept_reservation_link = add_query_arg(array(
                    'reservation_accept' => $reservation_id,
                ), home_url('/'));

                $reservation_restaurant_email = get_option('reservation_email');
                
                // Check if the reservation holder has entered an email address and if not, use the default admin email address
                if($reservation_restaurant_email == '') {
                    $reservation_restaurant_email = get_option('admin_email');
                }

                // Send email to restaurant owner
                $to = $reservation_restaurant_email;
                $subject = "Nieuwe reservering bij Eetboetiek Festina Lente";
                // Construct the email message
                $message = "<b>Reservering details: </b>\n\n<bR>";
                $message .= "Reservering houder: $reservation_holder_voornaam $reservation_holder_achternaam\n<bR>";
                $message .= "E-mail: $reservation_email\n<bR>";
                $message .= "Telefoonnummer: $reservation_phone\n<bR><br>";
                $message .= "Aantal gasten: $reservation_guests\n<bR>";
                if($reservation_type == 'lunch') {
                    $message .= "Reservring type: Lunch\n<bR>";
                } else if($reservation_type == 'high_tea') {
                    $message .= "Reservring type: High Tea\n<bR>";
                } else if($reservation_type == 'lente_lunch') {
                    $message .= "Reservring type: Lentes Lunch\n<bR>";
                } else {
                    $message.= "Reservering type: Lentes Menu\n<bR>";
                }

                if($reservation_type == 'lunch' || $reservation_type == 'lente_lunch') {
                    $message .= "Reservering tijd (Lunch): $reservation_time_lunch\n<bR>";
                } else if($reservation_type == 'high_tea') {
                    $message .= "Reservering tijd (High Tea): $reservation_time_high_tea\n<bR>";
                } else if($reservation_type == 'lentes_menu') {
                    $message .= "Reservering tijd (Lentes Menu): $reservation_time_menu\n<bR>";
                }
                $message .= "Reservering datum: $reservation_date\n<bR>";
                $message .= "Opmerkingen: $special_request_text\n\n<bR><bR><br>";
                $message .= "Klik op de volgende link om de reservering te accepteren: <a href='$accept_reservation_link'>Reservering bevestigen</a>";

                $headers = array('Content-Type: text/html; charset=UTF-8');
                wp_mail($to, $subject, $message, $headers);

                // Send email to reservation holder
                $to = $reservation_email;
                $subject = "Bedankt voor je reservering bij Eetboetiek Festina Lente";
                $message = "Bedankt voor je reservering bij Eetboetiek Festina Lente. Je ontvangt binnen 24 uur een bevestiging.\n\n<br><br>";
                $message .= "Je reservering details: \n\n<bR>";
                $message .= "Reservering houder: $reservation_holder_voornaam $reservation_holder_achternaam\n<bR>";
                $message .= "E-mail: $reservation_email\n<bR>";
                $message .= "Telefoonnummer: $reservation_phone\n<bR><bR>";
                $message .= "Aantal gasten: $reservation_guests\n<bR>";
                if($reservation_type == 'lunch') {
                    $message .= "Reservring type: Lunch\n<bR>";
                } else if($reservation_type == 'high_tea') {
                    $message .= "Reservring type: High Tea\n<bR>";
                } else if($reservation_type == 'lente_lunch') {
                    $message .= "Reservring type: Lentes Lunch\n<bR>";
                } else {
                    $reservering_type = 'lentes_menu';
                    $message.= "Reservering type: Lentes Menu\n<bR>";
                }

                if($reservation_type == 'lunch' || $reservation_type == 'lente_lunch') {
                    $message .= "Reservering tijd (Lunch): $reservation_time_lunch\n<bR>";
                } else if($reservation_type == 'high_tea') {
                    $message .= "Reservering tijd (High Tea): $reservation_time_high_tea\n<bR>";
                } else if($reservation_type == 'lentes_menu') {
                    $message .= "Reservering tijd (Lentes Menu): $reservation_time_menu\n<bR>";
                }
                $message .= "Reservering datum: $reservation_date\n<bR>";
                $message .= "Opmerkingen: $special_request_text\n\n<bR><bR>";
                $headers = array('Content-Type: text/html; charset=UTF-8');
                wp_mail($to, $subject, $message, $headers);

                // Redirect to a thank you page
                wp_redirect(esc_url(add_query_arg($query, $query_val, home_url('/bedankt-voor-je-reservering'))));
                exit;
            }
        }
    }

    private static function generate_random_id() {
        return wp_generate_password(10, false);
    }
    
    public static function render_reservation_form($atts) {
        // Merge user provided attributes with known attributes and fill in defaults when needed
        $atts = shortcode_atts(
            array(
                'name' => 'default_name',
                // add more attributes here as needed
            ),
            $atts,
            'reservation_form' // The shortcode tag
        );

        $name = esc_attr($atts['name']);

        ob_start();

        ?>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?compat=recaptcha" async defer></script>

        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" id="reservation_form">
            <input type="hidden" name="action" value="submit_reservation">
            <?php wp_nonce_field('reservation_verify'); ?>
            
            <div class="two-columns-form-row">
                <div class="two-columns-form-column">
                    <label for="reservation_holder_voornaam">Voornaam:</label>
                    <input type="text" id="reservation_holder_voornaam" name="reservation_holder_voornaam" required>
                </div>
                <div class="two-columns-form-column">
                    <label for="reservation_holder_achternaam">Achternaam:</label>
                    <input type="text" id="reservation_holder_achternaam" name="reservation_holder_achternaam" required>
                </div>
            </div>
    
            <label for="reservation_email">E-mail:</label>
            <input type="email" id="reservation_email" name="reservation_email" required>

            <label for="reservation_phone">Telefoonnummer:</label>
            <input type="tel" id="reservation_phone" name="reservation_phone" required>

            <label for="reservation_guests">Aantal gasten:</label>
            <div class="square-radios">
                <?php for ($i = 1; $i <= 8; $i++): ?>
                <input type="radio" id="guests_<?php echo $i; ?>" name="guests" value="<?php echo $i; ?>">
                <label for="guests_<?php echo $i; ?>"><?php echo $i; ?></label>
                <?php endfor; ?>
                <input type="radio" id="guests_more" name="guests" value="more" required>
                <label for="guests_more">>8</label>
            </div>

            <div class="rest_of_form">
                <?php if($name != 'lentes_menu') { ?>
                <label for="reservation_type">Waarvoor wil je reserveren:</label>
                <select id="reservation_type" name="reservation_type" required>
                    <option value="none" selected disabled hidden>Selecteer een optie</option>
                    <option value="lunch">Lunch</option>
                    <option value="high_tea">High Tea</option>
                    <option value="lente_lunch">Lentes Lunch</option>
                </select>

                <div class="reservation_time_lunch" style="display: none;">
                    <label for="reservation_time_lunch">Tijd:</label>
                    <select id="reservation_time_lunch" name="reservation_time_lunch">
                        <option id="lunch_none" value="" selected disabled hidden>Selecteer een optie</option>
                        <?php
                            $start = new DateTime("09:30");
                            $end = new DateTime("16:00");
                            $interval = DateInterval::createFromDateString('15 min');
                            $times = new DatePeriod($start, $interval, $end);
                            foreach ($times as $time) {
                                echo '<option value="' . $time->format('H:i') . '">' . $time->format('H:i') . '</option>';
                            }
                        ?>
                    </select>
                </div>

                <div class="reservation_time_high_tea" style="display: none;">
                    <label for="reservation_time_high_tea">Tijd:</label>
                    <select id="reservation_time_high_tea" name="reservation_time_high_tea">
                        <option id="high_tea_none" value="" selected disabled hidden>Selecteer een optie</option>
                        <option value="10:00-12:00">10:00 - 12:00</option>
                        <option value="14:30-17:00">14:30 - 17:00</option>
                    </select>
                </div>
                <?php } else { ?>

                <div class="reservation_time_menu">
                    <label for="reservation_time_menu">Tijd:</label>
                    <select id="reservation_time_menu" name="reservation_time_menu">
                        <option id="menu_none" value="" selected disabled hidden>Selecteer een optie</option>
                        <?php
                            $start = new DateTime("17:00");
                            $end = new DateTime("19:00");
                            $interval = DateInterval::createFromDateString('30 min');
                            $times = new DatePeriod($start, $interval, $end);
                            foreach ($times as $time) {
                                echo '<option value="' . $time->format('H:i') . '">' . $time->format('H:i') . '</option>';
                            }
                        ?>
                    </select>
                </div>

                <?php } ?>

                <label for="reservation_date">Datum:</label>
                <input type="text" id="reservation_date<?php if($name == 'lentes_menu'): echo '_lunch'; endif; ?>" name="reservation_date" autocomplete="off" required>
                        
                <label for="special_request_text">Overige opmerkingen (zoals allergieën):</label>
                <textarea id="special_request_text" name="special_request_text"></textarea>
                <div class="cf-turnstile" data-sitekey="0x4AAAAAAAOIKhilbmFzNmzN"></div>
                <div class="submit-div">
                    <input type="submit" value="Reservering plaatsen">
                    <div id="loading-spinner" style="display:none; align-items: center;">
                        <img width="64px" src="<?php echo plugin_dir_url(__FILE__).'spinner.svg'; ?>" style="vertical-align: middle;" alt="Loading spinner">
                        <p style="display:inline; color: #333!important; font-size: 16px">Versturen...</p>
                    </div>
                </div>
            </div>
            <div class="reservation_type_more_than_10" style="display: none;">
                <p class="reservation_more_p">Gezellig dat je met zo'n grote groep wilt komen! We zouden je willen vragen om even telefonisch te reserveren via 0165 – 85 72 53 om je wensen te bespreken.</p>
                <a class="form_button" href="tel:0165–857253">Direct bellen</a>
            </div>
        </form>

        <?php
        return ob_get_clean();
    }
}
