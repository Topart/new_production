/**
 * Created by diegopalda on 27/05/15.
 */
jQuery(window).load(function(){
   var width = jQuery( window ).width();
    if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) && width <= 480 ) {
        var owl = jQuery(".slides").data('owlCarousel');
        owl.removeItem(0);
        owl.removeItem(1);
       jQuery(".the-slideshow").show();
    }
});
