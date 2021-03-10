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

          function handle_performance_delete_artist(currentElement) {
            var buttonLink = currentElement.attr('href');
            var artist_name = currentElement.data('artist_name');  
            var work_title = currentElement.data('work_title');  
            console.log(buttonLink);
            console.log(artist_name);
            console.log(work_title);

            var confirmResult = confirm('Are you sure you want to remove ' + artist_name + ' as a creator from ' + work_title + '?');
            console.log('confirmResult = ' + confirmResult);
            debugger;

            if (confirmResult) {
              // $.ajax({
              //   url: buttonLink,
              //   type: "GET",
              //   success: function (result) {
              //   },
              //   error: function (error) {
              //     console.log('delete artist ' + artist_name + 'confirmation - ajax error' + JSON.stringify(error));
              //   }
              // });
            }
            else {
              
            }
            return confirmResult;
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
          
          $('a.performance_delete_artist').click(function(e) {
            console.log('performance_delete_artist clicked');
            result = handle_performance_delete_artist($(this));
            console.log('handle_performance_delete_artist return = ' + result);
            if (!result) {
              e.preventDefault();
            }
            else {
              return result;
            }
          });
       });
      });
    }
  }
})(jQuery, Drupal);
