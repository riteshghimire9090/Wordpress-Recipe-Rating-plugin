
<div id="products">
   <div style="float:right; width:350px; border:1px solid #ccc; padding:1em;">
     
     <ul id="response">
      
     </ul>
     </div>
     <ul>
         <h4>RATE_I18N</h4>
         RATE_I18N: <div data-rateit-resetable="false" data-productid="RECIPE_ID"  READONLY_PLACEHOLDER data-rateit-value="RECIPE_RATING" class="rateit"></div>
         
     </ul>
    
 </div>

 <script type ="text/javascript">
     //we bind only to the rateit controls within the products div
     $('#products .rateit').bind('rated ', function (e) {
         var ri = $(this);
 
         //if the use pressed reset, it will get value: 0 (to be compatible with the HTML range control), we could check if e.type == 'reset', and then set the value to  null .
         var value = ri.rateit('value');
         var productID = ri.data('productid'); // if the product id was in some hidden field: ri.closest('li').find('input[name="productid"]').val()
 
         //maybe we want to disable voting?
         ri.rateit('readonly', true);

         var form ={
             action:"r_rate_recipe",
             rid    :  productID,
             rating :    value 
         }
 
         $.post( recipe_obj.ajax_url    , form  , function(data){
            
        });
     });
 </script>