/**
 * Created by diegopalda on 27/05/15.
 */
jQuery(window).load(function(){
   var width = jQuery( window ).width();
    if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) && width <= 480 ) {
        var owl = jQuery(".slides").data('owlCarousel');

         // custom by dav.q
        var number = jQuery('.slides .owl-item').length;
        for (i = 1; i < number; i++) {
            owl.removeItem();
        }

       jQuery(".the-slideshow").show();
    }
});
