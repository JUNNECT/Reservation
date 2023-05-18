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

        // Process form data and send email
        $reservation_holder_voornaam = sanitize_text_field($_POST['reservation_holder_voornaam']);
        $reservation_holder_achternaam = sanitize_text_field($_POST['reservation_holder_achternaam']);
        $reservation_email = sanitize_email($_POST['reservation_email']);
        $reservation_phone = sanitize_text_field($_POST['reservation_phone']);
        $reservation_guests = sanitize_text_field($_POST['guests']);
        $reservation_type = sanitize_text_field($_POST['reservation_type']);
        $reservation_time_lunch = sanitize_text_field($_POST['reservation_time_lunch']);
        $reservation_time_high_tea = sanitize_text_field($_POST['reservation_time_high_tea']);
        $reservation_date = sanitize_text_field($_POST['reservation_date']);

        // reformat date to DD-MM-YYYY
        $reservation_date = date("d-m-Y", strtotime($reservation_date));

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

        // TODO: Create reservation acceptance link
        // Create reservation acceptance link
        $accept_reservation_link = add_query_arg(array(
            'reservation_accept' => $reservation_id,
        ), home_url('/'));

        // Send email to restaurant owner
        $to = "levi@junnect.nl";
        $subject = "Nieuwe reservering bij Eetboetiek Festina Lente";
        // Construct the email message
        $message = "Reservering details: \n\n<bR>";
        $message .= "Reservering houder: $reservation_holder_voornaam $reservation_holder_achternaam\n<bR>";
        $message .= "E-mail: $reservation_email\n<bR>";
        $message .= "Telefoonnummer: $reservation_phone\n<bR>";
        $message .= "Aantal gasten: $reservation_guests\n<bR>";
        $message .= "Reservering type: $reservation_type\n<bR>";
        $message .= "Reservering tijd (bij Lunch): $reservation_time_lunch\n<bR>";
        $message .= "Reservering tijd (bij High Tea): $reservation_time_high_tea\n<bR>";
        $message .= "Reservering datum: $reservation_date\n<bR>";
        $message .= "Opmerkingen: $special_request_text\n\n<bR><bR>";
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
        $message .= "Telefoonnummer: $reservation_phone\n<bR>";
        $message .= "Aantal gasten: $reservation_guests\n<bR>";
        $message .= "Reservering type: $reservation_type\n<bR>";
        $message .= "Reservering tijd (bij Lunch): $reservation_time_lunch\n<bR>";
        $message .= "Reservering tijd (bij High Tea): $reservation_time_high_tea\n<bR>";
        $message .= "Reservering datum: $reservation_date\n<bR>";
        $message .= "Opmerkingen: $special_request_text\n\n<bR><bR>";
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($to, $subject, $message, $headers);

        // Redirect to a thank you page
        wp_redirect(home_url('/bedankt-voor-je-reservering'));
        exit;
    }

    private static function generate_random_id() {
        return wp_generate_password(10, false);
    }
    
    public static function render_reservation_form() {
        ob_start();
        ?>
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" id="reservation_form">
            <input type="hidden" name="action" value="submit_reservation">
            <?php wp_nonce_field('reservation_verify'); ?>
            
            <!-- Your form fields here. For example: -->
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
                <?php for ($i = 1; $i <= 10; $i++): ?>
                <input type="radio" id="guests_<?php echo $i; ?>" name="guests" value="<?php echo $i; ?>">
                <label for="guests_<?php echo $i; ?>"><?php echo $i; ?></label>
                <?php endfor; ?>
                <input type="radio" id="guests_more" name="guests" value="more">
                <label for="guests_more">>10</label>
            </div>

            <div class="rest_of_form">

                <label for="reservation_type">Waarvoor wil je reserveren:</label>
                <select id="reservation_type" name="reservation_type">
                    <option value="none" selected disabled hidden>Selecteer een optie</option>
                    <option value="Lunch">Lunch</option>
                    <option value="High Tea">High Tea</option>
                    <option value="Lente's Lunch">Lente's Lunch</option>
                </select>

                <div class="reservation_time_lunch" style="display: none;">
                    <label for="reservation_time_lunch">Tijd:</label>
                    <select id="reservation_time_lunch" name="reservation_time_lunch" required>
                        <option value="none" selected disabled hidden>Selecteer een optie</option>
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
                    <select id="reservation_time_high_tea" name="reservation_time_high_tea" required>
                        <option value="none" selected disabled hidden>Selecteer een optie</option>
                        <option value="10:00-12:00">10:00 - 12:00</option>
                        <option value="14:30-17:00">14:30 - 17:00</option>
                    </select>
                </div>
        
                <label for="reservation_date">Datum:</label>
                <input type="text" id="reservation_date" name="reservation_date" autocomplete="off" required>
                        
                <label for="special_request_text">Overige opmerkingen (zoals allergieën):</label>
                <textarea id="special_request_text" name="special_request_text"></textarea>

                <input type="submit" value="Reservering plaatsen">
            </div>
            <div class="reservation_type_more_than_10" style="display: none;">
                <p>Gezellig dat je met zo'n grote groep wilt komen! We zouden je willen vragen om even telefonisch te reserveren via 0165 – 85 72 53 om je wensen te bespreken.</p>
                <a class="form_button" href="tel:0165–857253">Direct bellen</a>
            </div>
        </form>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
        <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

        <script>
        jQuery(document).ready(function() {
            $('#reservation_date').datepicker({
                minDate: 1, // Disable past dates and today
                beforeShowDay: function(date) {
                    return [date.getDay() != 0, '']; // Disable Sundays (day 0)
                }
            });

            // Show/hide rest of form based on guests (more then 10, hide, any of the others, show)
            $('#guests_more').change(function() {
                if($(this).is(':checked')) {
                    $('.rest_of_form').hide();
                    $('.reservation_type_more_than_10').show();
                }
            });

            $('#guests_1, #guests_2, #guests_3, #guests_4, #guests_5, #guests_6, #guests_7, #guests_8, #guests_9, #guests_10').change(function() {
                if($(this).is(':checked')) {
                    $('.rest_of_form').show();
                    $('.reservation_type_more_than_10').hide();
                }
            });

            $('#reservation_type').change(function() {
                if($(this).val() == 'Lunch' || $(this).val() == 'Lente\'s Lunch') {
                    $('.reservation_time_lunch').show();
                    $('.reservation_time_high_tea').hide();
                } else if($(this).val() == 'High Tea') {
                    $('.reservation_time_lunch').hide();
                    $('.reservation_time_high_tea').show();
                } else {
                    $('.reservation_time_lunch').hide();
                    $('.reservation_time_high_tea').hide();
                }
            });

        });
        </script>

        <?php
        return ob_get_clean();
    }
    
}
