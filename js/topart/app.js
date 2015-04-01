/**
 * Created by diegopalda on 01/04/15.
 */
jQuery(document).ready(function(){
    olark('api.chat.onOperatorsAvailable', function() {
        olark('api.rules.defineRule', {
            id: '10001',
            description: "offer help to a visitor after 30 seconds",
            condition: function(pass) {
                // Use the Visitor API to get information the page count
                olark('api.visitor.getDetails', function(details){
                    if (details.secondsSpentForThisVisit > 30) {
                        pass();
                    }

                });
            },
            action: function() {

                olark('api.chat.sendMessageToVisitor', {
                    body: "Hey! Let me know if you have any questions."
                });

            },
            // Restrict this action to execute only once per visit
            perVisit: true
        });
    });
});
