/**
 * Created by diegopalda on 20/04/15.
 */
jQuery(document).ready(function(){

    jQuery('.image-isotope').parent().on('touchstart',function(){
        jQuery(this).trigger('click');
    });

})