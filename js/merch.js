jQuery(document).ready(function()
{
      
      jQuery('ul.tabs li').click(function()
      {
        var tab_id = jQuery(this).attr('data-tab');

        jQuery('ul.tabs li').removeClass('current');
        jQuery('.tab-content').removeClass('current');

        jQuery(this).addClass('current');
        jQuery("#"+tab_id).addClass('current');
      })


      jQuery(".wc_merch_container form").submit(function () 
      {
        
        var isFormValid = true;
        
      if(jQuery('.sku-field').val().length == 0)
      {
        isFormValid = false;
      }
      else
      {
        console.log('Success');
      }

      if(isFormValid == false)
      {
        alert(" Please fill fields ")
      }
      else
      {
        console.log("Success")
      }
        return isFormValid;

     });

    jQuery(".wc_status_container form").submit(function () 
    {
        
        var isFormValid = true;
        
        if(jQuery('.order-number').val().length == 0)
        {
          isFormValid = false;
        }
        else
        {
          console.log('Success');
        }

        if(isFormValid == false)
        {
          alert(" Please fill fields ")
        }
        else
        {
          console.log("Success")
        }
        return isFormValid;

    });

})