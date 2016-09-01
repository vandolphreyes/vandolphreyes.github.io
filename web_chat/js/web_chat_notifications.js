/*
*
* Count and Return number of
* unread messages based on user_id
* Fetched from obs_message_recipients table
*
 */

$(function(){
    $base_url           = $('#base_url').html();
    $refresh_messages   = 1500;

    // check if app is express or not
    if (window.location.href.indexOf("express") != -1) {
        $base_url = 'http://localhost/obsjr/v2/';
    }

    $count = setInterval(function(){
        count_unread_messages();
    }, $refresh_messages);

    // fetch number of unread messages based on recipient's id
    function count_unread_messages(){
        $.get($base_url + 'web_chat/count_unread_messages/', function(data){
            if(data.count_result > 0){
                $(".tab-det li:nth-child(5)").append('<div class="unread"></div>');
                $('.unread')
                    .addClass('ui red circular tabb label floating')
                    .attr('style', 'position: inherit !important;')
                    .html(data.count_result);
            } else {
                $('.unread').remove();
            }

        }, 'json');
    }
    count_unread_messages();

});