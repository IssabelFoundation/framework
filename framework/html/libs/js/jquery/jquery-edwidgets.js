(function( $ ){

   /* FUNCTION ed_colorpicker */
   $.fn.ed_colorpicker = function() {
      // Inicio
      $(this).html("<span class='colorpicker-box'></span><span class='datepicker-button'><i class='fa fa-eyedropper'></i></span>" +
                   "<div style='display:none; top: 5px; left:-2px; z-index:4000; width:250px; height: 200px;'>" +
                   "   <table cellpadding=0 cellspacing=0>" +
                   "   <tr style='height:50px;'>" +
                   "      <td style='width:50px;background-color: #f39c12'></td>" +
                   "      <td style='width:50px;background-color: #d35400'></td>" +
                   "      <td style='width:50px;background-color: #c0392b'></td>" +
                   "      <td style='width:50px;background-color: #bdc3c7'></td>" +
                   "      <td style='width:50px;background-color: #7f8c8d'></td>" +
                   "   </tr>" +
                   "   <tr style='height:50px;'>" + 
                   "      <td style='width:50px;background-color: #f1c40f'></td>" +
                   "      <td style='width:50px;background-color: #e67e22'></td>" +
                   "      <td style='width:50px;background-color: #e74c3c'></td>" +
                   "      <td style='width:50px;background-color: #ecf0f1'></td>" +
                   "      <td style='width:50px;background-color: #95a5a6'></td>" +
                   "   </tr>" +
                   "   <tr style='height:50px;'>" +
                   "      <td style='width:50px;background-color: #16a085'></td>" +
                   "      <td style='width:50px;background-color: #27ae60'></td>" +
                   "      <td style='width:50px;background-color: #2980b9'></td>" +
                   "      <td style='width:50px;background-color: #8e44ad'></td>" +
                   "      <td style='width:50px;background-color: #2c3e50'></td>" +
                   "   </tr>" +
                   "   <tr style='height:50px;'>" +
                   "      <td style='width:50px;background-color: #1abc9c'></td>" +
                   "      <td style='width:50px;background-color: #2ecc71'></td>" +
                   "      <td style='width:50px;background-color: #3498db'></td>" +
                   "      <td style='width:50px;background-color: #9b59b6'></td>" +
                   "      <td style='width:50px;background-color: #34495e'></td>" +
                   "   </tr>" +
                   "   </table>" +
                   "</div>");

      jQuery.data(this,'displayed', false );

      /* To make appear and disappear the palette */
      $(this).click(function(e) {
         if(jQuery.data(this,'displayed')==true) {
            // Find the color clicked
            color = $(e.target).css('background-color');
            $('.colorpicker-box').css('background-color', color); 
            $(this).find("div").css('display', 'none');          
            jQuery.data(this,'displayed', false );
         } else {
            $(this).find("div").css('display', 'inline-block');
            jQuery.data(this,'displayed', true );
         }
      });

      return this;
   }; 
})( jQuery );
