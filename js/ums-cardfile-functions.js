(function ($, Drupal) {
  Drupal.behaviors.umsCardfileBehavior = {
    attach: function (context, settings) {
      $(document, context).once('umsCardfileBehavior').each(function () {
        // LOAD any drupalSetting variables
        //var maxLockerItemsCheck = drupalSettings.arborcat.max_locker_items_check;

        $(function () {
            console.log('ums-cardfile-functions LOADED');
          // ---------------------------------INITIALIZATION/SETUP ---------------------------------

          // --------------------------------- METHODS ---------------------------------
           function submitForm() {
            $('#submitting').addClass('loading').css('position', 'relative'); // Shows the loading spinner
            $('#edit-submit').attr('disabled', true);
            $('#edit-submit').parents('form').submit();
          }

           // --------------------------------- EVENT HANDLERS ---------------------------------
          $('#umsvenues-table tbody td').click(function (e) {
            console.log('CLICKED TD');
          });

          // give confirmation before canceling requests
          $('#edit-submit').click(function () {
            buttonName = $(this).val();
            // --- Handle Cancel pickup requests
            if (buttonName.startsWith('Cancel'))  {
              var cancelHolds = confirm('Once the request is canceled, you will be removed from the waitlist');
              submitForm();
            }
            // --- Handle Schedule pickup requests
            if (buttonName.startsWith('Schedule')) {
              // do validation on location, date and that items are checked in the list to be scheduled for pickup
              if (true == locationSelected() && true == dateSelected() && checkedItems() > 0) {
                submitForm();
              }
              else {
                if (0 == checkedItems()) {
                  displayBanner('At least one item must be checked to make a pickup appointment', 'warning');
                }
              } 
            }

            return false;
          });
        });
      });
    }
  }
})(jQuery, Drupal);
