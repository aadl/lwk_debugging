(function ($, Drupal) {
  Drupal.behaviors.umsCardfileBehavior = {
    attach: function (context, settings) {
      $(document, context).once('umsCardfileBehavior').each(function () {
        // LOAD any drupalSetting variables
        //var maxLockerItemsCheck = drupalSettings.arborcat.max_locker_items_check;

        $(function () {
          // ---------------------------------INITIALIZATION/SETUP ---------------------------------

           // --------------------------------- EVENT HANDLERS ---------------------------------
          $('#umsvenues-table tbody td a').click(function (e) {
            buttonName = $(this).text();
            buttonlink = $(this).attr('href');
           // --- Handle Cancel pickup requests
            if (buttonName.startsWith('Delete'))  {
              venue_name = $(this).data('venue_name');
              var deleteVenueConfirmed = confirm('Are you sure you wish to delete the venue - "' + venue_name + '" ?');
              if (true == deleteVenueConfirmed) {
                // Remove the row fron the table 
                $(this).parents("tr:first").remove();                // Remove the row fron the table 
                $.ajax({
                  url: buttonlink,
                  type: "GET",
                  success: function (result) {
                  },
                  error: function (error) {
                    console.log('deleteVenueConfirmed - ajax error' + JSON.stringify(error));
                  }
                });
              }
            }
            return false;
          });
        });
      });
    }
  }
})(jQuery, Drupal);
