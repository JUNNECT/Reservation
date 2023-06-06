jQuery(document).ready(function() {
    $ = jQuery;

    $('#reservation_date').datepicker({
        minDate: 1, 
        beforeShowDay: function(date) {
            return [date.getDay() != 0, '']; 
        }
    });

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
        if($(this).val() == 'lunch' || $(this).val() == 'lente_lunch') {
            $('.reservation_time_lunch').show();
            $('.reservation_time_high_tea').hide();
            $('#high_tea_none').attr('value', 'none');
            $('#lunch_none').attr('value', '');
        } else if($(this).val() == 'high_tea') {
            $('.reservation_time_lunch').hide();
            $('.reservation_time_high_tea').show();
            $('#lunch_none').attr('value', 'none');
            $('#high_tea_none').attr('value', '');
        } else {
            $('.reservation_time_lunch').hide();
            $('.reservation_time_high_tea').hide();
        }
    });

    $('#reservation_form').on('submit', function(e) {

        // Prevent the form from submitting
        e.preventDefault();

        // Display the loading animation
        $('#loading-spinner').css('display', 'flex');

        this.submit();

    });
      
});