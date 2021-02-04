(function ($, Drupal) {
  Drupal.behaviors.umsCardfileBehavior = {
    attach: function (context, settings) {
      $(document, context).once('umsCardfileBehavior').each(function () {
        // LOAD any drupalSetting variables
        //var maxLockerItemsCheck = drupalSettings.arborcat.max_locker_items_check;
        $(function () {
          // ---------------------------------INITIALIZATION/SETUP ---------------------------------
          function doDeleteConfirmation(typeName, currentElement) {
            var buttonLink = currentElement.attr('href');
            var itemName = currentElement.data('name');            
            var deleteVenueConfirmed = confirm('Are you sure you wish to delete the ' + typeName + ' - "' + itemName + '" ?');
            if (true == deleteVenueConfirmed) {
              // visually grey the whole row immediately after the dialog is closed with a true confirmation
              currentElement.parents("tr:first").css("background", "lightslategrey");
              currentElement.css("background", "lightslategrey");
              $.ajax({
                url: buttonLink,
                type: "GET",
                success: function (result) {
                  // after successful deletion from the DB, remove the row from the table
                  currentElement.parents("tr:first").remove();                // Remove the row fron the table 
                },
                error: function (error) {
                  console.log('delete ' + typeName + 'confirmation - ajax error' + JSON.stringify(error));
                }
              });
            }
          }
           // --------------------------------- EVENT HANDLERS ---------------------------------
          $('#ums-venues-table tbody td a').click(function (e) {
            console.log('ums-venues-table tbody CLICK');
            var buttonName = $(this).text();           // --- Handle Cancel pickup requests
            console.log('ums-venues-table buttonName = ' + buttonName);
            if (buttonName.startsWith('Delete'))  {
              doDeleteConfirmation('venue', $(this));
            }
            return false;
          });
          
          $('#ums-events-table tbody td a').click(function (e) {
            console.log('ums-events-table tbody CLICK');
            var buttonName = $(this).text();           // --- Handle Cancel pickup requests
            console.log('ums-events-table buttonName = ' + buttonName);
           if (buttonName.startsWith('Delete'))  {
              doDeleteConfirmation('event', $(this));
            }
            return false;
          });        

          $('#ums-artists-table tbody td a').click(function (e) {
            console.log('ums-artists-table tbody CLICK');
            var buttonName = $(this).text();           // --- Handle Cancel pickup requests
            console.log('ums-artists-table buttonName = ' + buttonName);
           if (buttonName.startsWith('Delete'))  {
              doDeleteConfirmation('artist', $(this));
            }
            return false;
          });        

        });
      });
    }
  }
})(jQuery, Drupal);
