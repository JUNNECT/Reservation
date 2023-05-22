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