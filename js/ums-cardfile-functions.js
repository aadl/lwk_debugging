(function ($, Drupal) {
  Drupal.behaviors.umsCardfileBehavior = {
    attach: function (context, settings) {
      $(document, context).once('umsCardfileBehavior').each(function () {
        // LOAD any drupalSetting variables
        //var maxLockerItemsCheck = drupalSettings.arborcat.max_locker_items_check;
        $(function () {
          // ---------------------------------INITIALIZATION/SETUP ---------------------------------
          function handleButtonClick(typeName, currentElement) {
            var buttonName = currentElement.text();           // --- Handle Cancel pickup requests
            var buttonLink = currentElement.attr('href');
            var itemName = currentElement.data('name');  

            if (buttonName.startsWith('Delete'))  {
              var confirmResult = confirm('Are you sure you wish to delete the ' + typeName + ' - "' + itemName + '" ?');
              if (confirmResult) {
               $.ajax({
                  url: buttonLink,
                  type: "GET",
                  success: function (result) {
                    // after successful deletion from the DB, remove the row from the table
                    if (buttonName.startsWith('Delete'))  {
                      currentElement.parents("tr:first").remove();
                    }           // Remove the row fron the table 
                  },
                  error: function (error) {
                    console.log('delete ' + typeName + 'confirmation - ajax error' + JSON.stringify(error));
                  }
                });
              }
              return confirmResult;
            }
          }
                    // --------------------------------- EVENT HANDLERS ---------------------------------
          $('#ums-venues-table tbody td a').click(function (e) {
            return handleButtonClick('venue', $(this));
          });
          
          $('#ums-events-table tbody td a').click(function (e) {
            return handleButtonClick('event', $(this));
          });        

          $('#ums-artists-table tbody td a').click(function (e) {
            return handleButtonClick('artist', $(this));
          });        

          $('#ums-series-table tbody td a').click(function (e) {
            return handleButtonClick('series', $(this));
          });        
          
          $('#ums-perfrole-table tbody td a').click(function (e) {
            return handleButtonClick('performance role', $(this));
          });        
 
          $('#ums-workrole-table tbody td a').click(function (e) {
            return handleButtonClick('creator role', $(this));
          });        

       });
      });
    }
  }
})(jQuery, Drupal);
