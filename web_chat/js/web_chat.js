$(function() {

    $base_url                = 'https://www.outbounders.com/';
    $chat_box                = $('#chat-view-box');
    $web_chat_url            = $base_url + 'web_chat/';
    $job_id                  = $chat_box.data('id');
    $job_parent_id           = $chat_box.data('parent-id');
    $module                  = $chat_box.data('module');
    $editing_message         = $chat_box.data('edit');
    $deleting_message        = $chat_box.data('delete');
    $attaching_files         = $chat_box.data('attach');
    $allowed_user            = $chat_box.data('allowed');
    $sending_email           = $chat_box.data('email');
    $show_reply_box          = $chat_box.data('show-reply-box');
    $show_reply_to_reply_box = $chat_box.data('show-reply-to-reply-box');
    $default                 = $chat_box.data('default'); //true - all campaign agents //false - all users join a conversation
    $sending_sms             = $chat_box.data('sms');
    $refresh_storage         = 1500;
    $refresh_messages        = 3000;
    $new_topic_height        = 141;
    $extra_height            = 55;
    $key_up_timer            = '';

    // urls for web_chat
    $V2_URL                  = $base_url; // different usage from $base_url
    $EXPRESS_URL             = $chat_box.data('express-url');
    alert($V2_URL);

    // checking base url
    if ($base_url == $EXPRESS_URL) {
        $web_chat_url = $V2_URL + 'web_chat/';
    }

    // used for local storage connections
    localStorage.clear();
    function setLocalStorage(key, data) {
        return localStorage.setItem(key, data)
    }

    function getLocalStorage(data) {
        return localStorage.getItem(data)
    }

    function removeLocalStorage(key) {
        return localStorage.removeItem(key)
    }

    // used for cookie connections
    function setCookie(name, value) {
        return document.cookie = name + "=" + value;
    }

    function getCookie(name) {
        var re = new RegExp(name + "=([^;]+)");
        var value = re.exec(document.cookie);
        return (value != null) ? decodeURI(value[1]) : null;
    }

    function removeCookie(name) {
        document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:01 GMT;';
    }

    function getUrlVars() {
        var vars = {};
        var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
            vars[key] = value;
        });
        return vars;
    }

    // Always fetching the messages
    function start() {
        $start = setInterval(function() {
            //console.log('starting');
            fetch_chat_messages();

            //// remove elements from DOM for security
            //$chat_box.removeAttr('data-controller');
            //$chat_box.removeAttr('data-id');
            //$chat_box.removeAttr('data-parent-id');
            //$chat_box.removeAttr('data-allow');
            //$chat_box.removeAttr('data-edit');
            //$chat_box.removeAttr('data-module');
            //$chat_box.removeAttr('data-email');
            //$chat_box.removeAttr('data-delete');
            //$chat_box.removeAttr('data-attach');
            //$chat_box.removeAttr('data-allowed');
            //$chat_box.removeAttr('data-show-reply-box');
            //$chat_box.removeAttr('data-show-reply-to-reply-box');
            //$chat_box.removeAttr('data-default');
            //$chat_box.removeAttr('data-sms');
            //$chat_box.removeAttr('data-v2-url');
            //$chat_box.removeAttr('data-express-url');

        }, $refresh_messages);
    }
    //console.log('start on load');
    start();

    function pause() {
        //console.log('pausing');
        clearInterval($start);
    }

    // fetching messages
    function fetch_chat_messages() {

        $.ajax({
            type: 'post',
            data: {
                job_id:                  $job_id,
                job_parent_id:           $job_parent_id,
                module:                  $module,
                editing_message:         $editing_message,
                deleting_message:        $deleting_message,
                attaching_files:         $attaching_files,
                show_reply_box:          $show_reply_box,
                show_reply_to_reply_box: $show_reply_to_reply_box,
                default:                 $default,
                sending_sms:             $sending_sms
            },
            dataType: 'jsonp',
            url: $web_chat_url + 'fetch_parent_messages/',
            cache: false,
            success: function(data){
                $chat_box.find('.chat-box').html(data);

                // check if user allowed to send message in post job
                if($module == 'post_job' && $allowed_user != 1) {
                    $web_chat_box = $('.chat-box');

                    $chat_box.find('.new-topic').remove();
                    $web_chat_box.find('.topic-reply-box').remove();
                    $web_chat_box.find('.reply-button-one').addClass('hidden');
                    $web_chat_box.find('.report-message').addClass('hidden');
                }

                // enable/open editing while still receiving messages
                $('.message').each(function() {
                    _$message = this;
                    $li_group = $(_$message).parents('.li-group');
                    $reply_id = $li_group.data('id');

                    // restore edit messages if data exists in localStorage
                    for ($key in localStorage) {
                        $key_item           = 'li_' + $reply_id;
                        $key_item_height    = 'li_' + $reply_id + '_height';
                        $edit_message_count = 'edit_message_count_' + $reply_id;
                        $key_item_loader    = 'div_' + $reply_id + '_loader';

                        if($key == $key_item) {
                            $li_group.find('.edit-message').hide();
                            $li_group.find('.save-edit').show();
                            $li_group.find('.send-reply').hide();
                            $li_group.find('.cancel-edit').show();
                            $li_group.find('.delete-message').hide();
                            $li_group.find('.save-edit').attr('enabled','enabled');

                            // replacing p tag to textarea to enabled editing
                            $current_message = $li_group.find(_$message);
                            $current_message.replaceWith('<textarea maxlength="500" id="replaced">' + getLocalStorage($key_item) + '</textarea>');
                            $replaced = $('#replaced');

                            // copy attributes from replaced p tag to textarea
                            attributes = $current_message.prop("attributes");
                            $.each(attributes, function() {
                                $replaced.attr(this.name, this.value);
                            });
                            $replaced.putCursorAtEnd();

                            // setting dynamic height for all li group when editing
                            $replaced.height(getLocalStorage($key_item_height));

                            // for ajax loader
                            if(getLocalStorage($key_item_height) > 56) {
                                $('ul div[data-id*="' + $reply_id + '"].editing-message-loader').css('marginTop', getLocalStorage($key_item_loader) + 'px');
                            }

                            // show and restore remaining characters while editing
                            if(getLocalStorage($edit_message_count)) {
                                $replaced_li_group = $replaced.parents('.li-group');

                                $replaced_li_group.find('.date-created').addClass('hide');
                                $replaced_li_group.find('p.message-count').removeClass('hide');
                                $replaced_li_group.find('p.message-count span').html(getLocalStorage($edit_message_count));
                            }
                        }
                    }

                    // set position after checking if element have files
                    $attached_file = $li_group.find('.attached-files');
                    if($attached_file.children().length == 0) {
                        $attached_file.parents('.li-group').find('.buttons').css('position', 'static');
                    } else {
                        $attached_file.parents('.li-group').find('.buttons').css('position', 'absolute');
                        if ($base_url == $EXPRESS_URL) {
                            $attached_file.parents('.li-group').find('.attached-files').find('i').removeClass('attachment icon').addClass('attach icon');
                        }
                    }
                });

                // check all parent messages
                $('.parent .message').each(function() {
                    $reply_id   = $(this).parents('.li-group').data('id');
                    $li_message = $('ul [data-id*="' + getCookie($reply_id) + '"]');

                    // auto slideUp reply messages
                    if(getCookie($reply_id) == $reply_id) {
                        $li_message.find('a.toggle-reply').addClass('accordion-off');
                        $li_message.find('a.toggle-reply i.angle').removeClass('up icon').addClass('down icon');
                        $li_message.find('span.toggle-label').text('Show all ' + getCookie($reply_id + '_value') + ' replies');
                        $li_message.find('span.count_unread').removeClass('hide');
                        $('ul [data-topic-id*="' + getCookie($reply_id) + '"]').addClass('hide');
                    }
                });

                // get and insert data if data exists in localStorage
                // reply to 1 topic
                $('.topic-message').each(function () {
                    $reply_id = $(this).parents('.div-group').data('id');
                    for ($key in localStorage) {
                        $key_item               = 'div_' + $reply_id;
                        $key_item_height        = 'div_' + $reply_id + '_height';
                        $key_item_button        = 'div_' + $reply_id + '_button';
                        $key_item_loader        = 'div_' + $reply_id + '_loader';
                        $li_box                 = $('ul li[data-id*="' + $reply_id + '"]');
                        $reply_message_count    = 'reply_message_count_' + $reply_id;

                        if ($key == $key_item) {
                            $(this).val(getLocalStorage($key_item));
                            $(this).height(getLocalStorage($key_item_height));
                            $(this).putCursorAtEnd();

                            // for ajax loader
                            if(getLocalStorage($key_item_height) > 56){
                                $(this).parents('.div-group').find('.topic-reply-button').css('marginTop', getLocalStorage($key_item_button) + 'px');
                                $('ul div[data-id*="' + $reply_id + '"].sending-message-loader').css('marginTop', getLocalStorage($key_item_loader) + 'px');
                            }

                            // show and restore remaining characters while editing
                            if(getLocalStorage($reply_message_count)) {
                                $li_box.find('div.message-count').removeClass('hide').addClass('visible');
                                $li_box.find('div.message-count span').html(getLocalStorage($reply_message_count));
                            }
                        }
                    }
                });

                // get and insert data if data exists in localStorage
                // reply to 1 message
                $('.reply-message-one').each(function () {
                    $parent                = $(this).parents('.div-group');
                    $reply_id              = $(this).parents('.div-group').data('id');
                    $li_box                = $('ul li[data-id*="' + $reply_id + '"]');
                    $reply_message_count   = 'reply_message_count_' + $reply_id;

                    for ($key in localStorage) {
                        $key_item        = 'div_' + $reply_id;
                        $key_item_height = 'div_' + $reply_id + '_height';

                        if ($key == $key_item) {
                            $parent.show();
                            $li_box.addClass('open');
                            $li_box.find('.edit-message').hide();
                            $li_box.find('.report-message').hide();
                            $li_box.find('.send-reply').hide();
                            $li_box.find('.delete-message').hide();
                            $li_box.find('.cancel-reply-own').show();
                            $li_box.find('.cancel-reply').show();

                            $(this).val(getLocalStorage($key_item));
                            $(this).height(getLocalStorage($key_item_height));
                            $(this).putCursorAtEnd();

                            // show and restore remaining characters while editing
                            if(getLocalStorage($reply_message_count)) {
                                $li_box.find('div.message-count').removeClass('hide').addClass('visible');
                                $li_box.find('div.message-count span').html(getLocalStorage($reply_message_count));
                            }
                        }
                    }
                });

                // show main textbox
                $chat_message = $('.chat-message');
                $new_topic    = $('.new-topic');
                $new_topic.removeClass('hide').find('.sending-message-loader').addClass('hide');
                $chat_message.css('opacity', 1);
                if(getLocalStorage('chat-message') === null) {
                    $chat_message.val('');
                    $chat_message.height($new_topic_height);
                    $chat_message.parent().height($new_topic_height);
                    $new_topic.find('button').removeAttr('style');
                    $new_topic.find('button').addClass('top');
                }

                // enable send button for new topic
                $('.new-topic button').removeAttr('disabled');


                if($show_reply_box != 'TRUE'){
                    $('.topic-send-button').remove();
                }

                if($sending_email == 'TRUE'){
                    $('.sending-email').removeClass('hide');
                }

                if($chat_box.find('ul').data('user') == 'client' && $sending_sms == 'TRUE'){
                    $('.sending-sms').removeClass('hidden');
                } else {
                    $('.sending-sms').remove();
                }

                // enable send button for new topic
                if($module == 'campaign_discussion' || $module == 'wall'){
                    if($('.chat-box ul li').length == 0){
                        if($module == 'wall'){
                            window.location.href = $EXPRESS_URL + $module + '/lists/?excs_id=' + getUrlVars()["excs_id"];
                        } else {
                            $current_url = window.location.href;
                            window.location.href = $base_url + $module +  '/lists/' + $job_parent_id;
                        }
                    }
                }

                //if($module == 'messages')
                //    if($('.chat-box ul li').length == 0)
                //        window.location.href = $base_url + $module;
            }
        });

        $.ajax({
            type: 'post',
            data: { },
            dataType: 'html',
            url: $web_chat_url + 'count_unread_messages_json/',
            cache: false,
            success: function(data) {
                $('.parent .message').each(function () {
                    $reply_id = $(this).parents('.parent').data('id');

                    // check if topic reply messages are open
                    if(!$(this).parents('.parent').find('a.toggle-reply').hasClass('accordion-off')) {
                        if(data > 0) {
                            //console.log('reading');

                            // add current datetime when the message was read/open
                            $.post($web_chat_url + 'read_date/', { om_id: $reply_id }, 'json');
                        }
                    }
                });
            }
        });
    }



    // set the focus/caret to the end of the textarea plugin
    // https://css-tricks.com/snippets/jquery/move-cursor-to-end-of-textarea-or-input/
    jQuery.fn.putCursorAtEnd = function() {
        return this.each(function() {
            $(this).focus();
            // If this function exists...
            if (this.setSelectionRange) {
                // ... then use it (Doesn't work in IE)
                // Double the length because Opera is inconsistent about whether a carriage return is one character or two. Sigh.
                var len = $(this).val().length * 2;
                this.setSelectionRange(len, len);
            } else {
                // ... otherwise replace the contents with itself
                // (Doesn't work in Google Chrome)
                $(this).val($(this).val());
            }
            // Scroll to the bottom, in case we're in a tall textarea
            // (Necessary for Firefox and Google Chrome)
            this.scrollTop = 999999;
        });
    };

    // show message count for creating new topic
    $(document).on('click', '.chat-message', function() {
        $('#chat-view-box form > div.message-count').removeClass('hidden').addClass('visible');
    });

    // auto adjust the height of textarea when editing
    $(document).on('keyup', '.chat-message', function (e) {
        e.preventDefault();
        $(this).height(0);
        $(this).parents('.new-topic').height(this.scrollHeight + 6);
        $(this).height(this.scrollHeight);

        $(e.target).parent().find('.send-chat').css('marginTop', ($(this).height() - $extra_height));

        $box_height = $(this).height();
        $(this).parents('.new-topic').find('button').removeClass('top');
        $(this).height($box_height);

        if($(this).val() != '') {
            clearInterval($key_up_timer);
            if ($(this).val()) {
                $key_up_timer = setInterval(function() {
                    fetch_chat_messages();
                    //console.log('keyup chat-message');
                    //console.log('storage running 3 sec');
                }, $refresh_storage);
            }
            setLocalStorage('chat-message', $(this).val());
        } else {
            clearInterval($key_up_timer);
            $key_up_timeout = setTimeout(function() {
                //console.log('keyup chat-message');
                pause();
                start();
                //console.log('storage running 1.5 sec, timeout chat-message');
            }, $refresh_storage);
            //$(this).blur();
            removeLocalStorage('chat-message');
        }

    });

    // enable editing
    $(document).on('click', '.edit-message', function(e) {
        e.preventDefault();

        pause();

        $li_group        = $(e.target).parents('.li-group');
        $current_message = $(e.target).parents('.message-options').find('.message');
        $getHeight       = $current_message.height();

        // replacing p tag to textarea to enabled editing
        $current_message.replaceWith('<textarea maxlength="500" id="replaced">' + $current_message.text() + '</textarea>').putCursorAtEnd();
        $replaced = $(e.target).parents('.message-options').find('#replaced');

        // copy attributes from replaced p tag to textarea
        attributes = $current_message.prop("attributes");
        $.each(attributes, function() {
            $replaced.attr(this.name, this.value);
        });
        $replaced.putCursorAtEnd();

        // setting dynamic height for all li group when editing
        //$replaced.height($getHeight);
        //$replaced.css('height', $getHeight);
        $replaced.css({
            'height'    : $getHeight,
            'fontFamily': 'Arial'
        });

        $(this).hide();
        $li_group.find('.save-edit').attr('enabled','enabled').show();
        $li_group.find('.send-reply').hide();
        $li_group.find('.date-created').addClass('hide');
        $li_group.find('.delete-message').hide();
        $li_group.find('.cancel-edit').show();
        $li_group.find('.message-count span').html(500 - $current_message.text().length);
        $li_group.find('.message-count').removeClass('hide');

        $reply_id   = $li_group.data('id');
        $message    = $li_group.find('textarea');

        // store at localStorage
        setLocalStorage('li_' + $reply_id, $message.val());
        setLocalStorage('li_' + $reply_id + '_height', $getHeight);
        setLocalStorage('current_message_' + $reply_id, $message.val());
        setLocalStorage('current_height_' + $reply_id, $getHeight);

        if($(this).val() != '') {
            clearInterval($key_up_timer);
            if ($(this).val()) {
                $key_up_timer = setInterval(function() {
                    fetch_chat_messages();
                    //console.log('click edit-message');
                    //console.log('storage running 3 sec');
                }, $refresh_storage);
            }
        }
    });

    // cancel editing message
    $(document).on('click', '.cancel-edit', function(e) {
        e.preventDefault();
        $buttons    = $(e.target).parents('.buttons');
        $li_group   = $(e.target).parents('.li-group');

        $(this).hide();
        $buttons.find('.save-edit').hide();
        $buttons.find('.edit-message').show();
        $buttons.find('.send-reply').show();
        $buttons.find('.delete-message').show();

        $li_group.find('.message-count').addClass('hide');
        $li_group.find('.date-created').removeClass('hide');
        $li_group.find('.message').val(getLocalStorage('current_message_' + $reply_id));
        $li_group.find('.message').height(getLocalStorage('current_height_' + $reply_id));

        localStorage.clear();
        clearInterval($key_up_timer);
        //console.log('click cancel-edit');
        pause();
        start();
    });

    // cancel reply to 1 message
    $(document).on('click', '.cancel-reply-own', function(e) {
        e.preventDefault();
        $li_group = $(e.target).parents('.li-group');

        $reply_id   = $li_group.data('id');
        $reply_box  = $('.reply-box-' + $reply_id);
        $reply_box.slideUp('slow');
        $(this).hide();
        $reply_box.removeClass('open');
        $li_group.find('.send-reply').show();
        $li_group.find('.edit-message').show();
        $li_group.find('.report-message').show();
        $li_group.find('.delete-message').show();

        //hide message count
        $('ul li[data-id*="' + $reply_id + '"]').find('div.message-count').hide();

        localStorage.clear();
        clearInterval($key_up_timer);
        //console.log('click cancel-reply-own');
        pause();
        start();
    });

    // saving changes on edit
    $(document).on('click', '.save-edit', function(e) {
        e.preventDefault();
        $buttons    = $(e.target).parents('.buttons');
        $li_group   = $(e.target).parents('.li-group');

        $message_id = $li_group.data('id');
        $message    = $(e.target).parents('.message-options').find('.message');

        // check if message was modified
        if($message.val() != getLocalStorage('current_message_' + $reply_id)) {

            // save modified message
            if ($.trim($message.val()) != '') {
                $.post($web_chat_url + 'update_message', {
                    id: $message_id,
                    message: $message.val().trim()
                }, 'json');

                //$message.val(getLocalStorage('li_' + $reply_id));
                $message.attr('spellcheck', 'false');
                $(this).hide();
                $buttons.find('.cancel-edit').hide();
                $buttons.find('.edit-message').show();
                $buttons.find('.send-reply').show();
                $buttons.find('.send-reply').show();

                $li_group.find('.message-count').addClass('hide');
                $li_group.find('.date-created').removeClass('hide');
                $li_group.find('.delete-message').show();

                // show loader
                $('ul div[data-id*="' + $message_id + '"].editing-message-loader').removeClass('hide');
                $message.css('opacity', '0.5');

                localStorage.clear();
                clearInterval($key_up_timer);
                //console.log('click save-edit');
                fetch_chat_messages();
                pause();
                start();
            } else {
                $message.putCursorAtEnd();
            }
        } else {
            //console.log('same');
            localStorage.clear();
            clearInterval($key_up_timer);
            fetch_chat_messages();
            //console.log('click save-edit else');
            pause();
            start();
        }
    });

    //updating subject/title of the topic/discussion
    $(document).on('click', '.save-title', function(){
        $subject_id = $(this).parents('.edit_comment').data('id');
        $subject    = $('.edit_comment .title');

        if ($.trim($subject.val()) != '') {
            $.post($web_chat_url + 'update_subject', {
                id:         $subject_id,
                message:    $subject.val().trim()
            }, 'json');
            location.reload();
        }
    });

    // opening reply box / replying to the message / hide edit and report button when reply box is open
    $(document).on('click', '.reply-button-one', function(e) {
        e.preventDefault();
        $li_group   = $(e.target).parents('.li-group');

        $reply_id   = $li_group.data('id');
        $reply_box  = $('.reply-box-' + $reply_id);

        pause();

        $reply_box.slideToggle('slow').toggleClass('open');
        $('.reply-box-' + $reply_id + ' textarea').putCursorAtEnd();


        if ($reply_box.hasClass('open')) {

            $(this).hide();
            $li_group.find('.saved-edit').show();
            $li_group.find('.edit-message').hide();
            $li_group.find('.delete-message').hide();
            $li_group.find('.cancel-reply').show();
            $li_group.find('.report-message').hide();
            $li_group.find('.cancel-reply-own').show();
            $li_group.find('.message-count').addClass('hide');
            $('ul li[data-id*="' + $reply_id + '"]').find('div.message-count').removeClass('hide').show();

            $message_one = $('.reply-box-' + $reply_id + ' textarea');
            $box_height  = $message_one.height();

            setLocalStorage('div_' + $reply_id, $message_one.val());
            setLocalStorage('div_' + $reply_id + '_height', $box_height);
            if($(this).val() != '') {
                clearInterval($key_up_timer);
                if ($(this).val()) {
                    $key_up_timer = setInterval(function() {
                        fetch_chat_messages();
                        //console.log('click reply-button-one');
                        //console.log('storage running 3 sec');
                    }, $refresh_storage);
                }
            }
        } else {
            $reply_box.slideUp('slow');
            $(this).hide();
            $li_group.find('.send-reply').show();
            $li_group.find('.edit-message').show();
            $li_group.find('.cancel-reply').hide();
            $li_group.find('.report-message').show();
            $li_group.find('.report-message').show();
            $li_group.find('.save-edit').removeAttr('enabled');

            clearInterval($key_up_timer);
            //console.log('click reply-button-one else');
            pause();
            start();
        }
    });

    // clicking send button / Replying to the one message
    $(document).on('click', '.send-reply-one', function(e) {
        e.preventDefault();

        $message_one    = $(e.target).parents('.div-group').find('.reply-message-one');
        $reply_id       = $(e.target).parents('.div-group').data('parent-id');
        $parent         = $(".message-reply-"+ $reply_id);
        $reply_box      = $('.reply-box-' + $reply_id);

        if($.trim($message_one.val()) != '') {
            $.post($web_chat_url + 'send/',
            {
                job_id:         $job_id,
                job_parent_id:  $job_parent_id,
                ref_table_id:   $job_parent_id,
                parent_id:      $reply_id,
                module:         $module,
                default:        $default,
                message:        $message_one.val().trim(),
            }, 'json');

            $parent.find('.send-reply').show();
            $parent.find('.cancel-edit').hide();
            $parent.find('.edit-message').show();
            $parent.find('.cancel-reply').hide();
            $parent.find('.report-message').show();
            $parent.find('.cancel-reply-own').hide();
            $parent.find('.delete-message').show();
            $(this).prop('disabled', true);

            //hide message count
            $('ul li[data-id*="' + $reply_id + '"]').find('div.message-count').hide();
            $reply_box.slideUp('slow');

            localStorage.clear();
            clearInterval($key_up_timer);
            //fetch_chat_messages(); // no need, will cause problem with slideup event
            //console.log('click send-reply-one');
            pause();
            start();
        } else {
            $message_one.putCursorAtEnd();
            pause();
        }

    });

    // clicking send button / Replying to the one topic
    $(document).on('click', '.topic-reply-button', function(e) {
        e.preventDefault();
        $reply_id       = $(e.target).parents('.div-group').data('id');
        $topic_message  = $(e.target).parents('.topic-reply-box').find('.topic-message');


        if($.trim($topic_message.val()) != '') {
            $.post($web_chat_url + 'send/',
            {
                job_id:         $job_id,
                job_parent_id:  $job_parent_id,
                ref_table_id:   $job_parent_id,
                parent_id:      $reply_id,
                module:         $module,
                default:        $default,
                title:          '',
                message:        $topic_message.val()
            }, 'json');

            // show loader
            $('ul div[data-id*="' + $reply_id + '"].sending-message-loader').removeClass('hide');
            $topic_message.css('opacity', '0.5');
            $('ul li[data-id*="' + $reply_id + '"]').find('div.message-count').removeClass('visible').addClass('hidden');
            $(this).prop('disabled', true);

            localStorage.clear();
            clearInterval($key_up_timer);
            fetch_chat_messages();
            //console.log('click topic-reply-button');
            pause();
            start();
        } else {
            $topic_message.putCursorAtEnd();
            pause();
        }
    });

    // Editing message
    // auto adjust the height of textarea when editing
    $(document).on('keyup', 'textarea.message', function (e) {
        e.preventDefault();
        $(this).height(0);
        $(this).height(this.scrollHeight);

        pause();

        $reply_id   = $(e.target).parents('.li-group').data('id');
        $message    = $(e.target).parents('.li-group').find('textarea');
        $box_height = $(this).height();

        // for ajax loader
        $('ul div[data-id*="' + $reply_id + '"].editing-message-loader').css('margin-top', $box_height - ($box_height/2) + 10);
        setLocalStorage('div_' + $reply_id + '_loader', $box_height - ($box_height/2) + 10);

        setLocalStorage('li_' + $reply_id, $message.val());
        setLocalStorage('li_' + $reply_id + '_height', $box_height);

        clearInterval($key_up_timer);
        if ($(this).val()) {
            $key_up_timer = setInterval(function() {
                fetch_chat_messages();
                //console.log('keyup textarea.message');
                //console.log('storage running 3 sec');
            }, $refresh_storage);
        }
    });

    // reply message to 1 topic
    $(document).on('keyup', '.topic-message', function (e) {
        e.preventDefault();

        pause();

        $(this).height(0);
        $(this).height(this.scrollHeight);
        $reply_id   = $(e.target).parents('.div-group').data('id');
        $message    = $(e.target).parents('.div-group').find('textarea');
        $box_height = $(this).height();

        if($message.height() > 56) {
            $(e.target).parent().find('.topic-reply-button').css('marginTop', ($box_height - $extra_height));
            $('ul div[data-id*="' + $reply_id + '"].sending-message-loader').css('margin-top', $box_height - ($box_height/2) - 10);
        }

        setLocalStorage('div_' + $reply_id, $message.val());
        setLocalStorage('div_' + $reply_id + '_height', $box_height);

        // for ajax-loader
        setLocalStorage('div_' + $reply_id + '_button', ($box_height - $extra_height));
        setLocalStorage('div_' + $reply_id + '_loader', $box_height - ($box_height/2) - 10);

        if($(this).val() != '') {
            clearInterval($key_up_timer);
            if ($(this).val()) {
                $key_up_timer = setInterval(function() {
                    fetch_chat_messages();
                    //console.log('keyup topic-message');
                    //console.log('storage running 3 sec');
                }, $refresh_storage);
            }
        } else {
            clearInterval($key_up_timer);
            $key_up_timeout = setTimeout(function() {
                removeLocalStorage('div_' + $reply_id);
                removeLocalStorage('div_' + $reply_id + '_height');
                //console.log('keyup topic-message');
                pause();
                start();
                //console.log('storage running 1.5 sec, timeout topic-message');
            }, $refresh_storage);
        }
    });

    // reply message to 1 message
    $(document).on('keyup', '.reply-message-one', function (e) {
        e.preventDefault();
        $(this).height(0);
        $(this).height(this.scrollHeight);
        $reply_id   = $(e.target).parents('.div-group').data('id');
        $message    = $(e.target).parents('.div-group').find('textarea');
        $box_height = $(this).height();

        if($message.height() > 56) {
            $(e.target).parent().find('.send-reply-one').css('marginTop', ($box_height - $extra_height));
        }

        $('ul li[data-id*="' + $reply_id + '"]').find('div.message-count').removeClass('hidden').addClass('visible');
        setLocalStorage('div_' + $reply_id, $message.val());
        setLocalStorage('div_' + $reply_id + '_height', $box_height);
        if($(this).val() != '') {
            clearInterval($key_up_timer);
            if ($(this).val()) {
                $key_up_timer = setInterval(function() {
                    fetch_chat_messages();
                    //console.log('keyup reply-message-one');
                    //console.log('storage running 3 sec');
                }, $refresh_storage);
            }
        }
        pause();
    });

    // activate cursor on the clicked textbox // show message count
    $(document).on('click', '.topic-message', function (e) {
        e.preventDefault();
        clearInterval($key_up_timer);
        pause();
        $reply_id   = $(e.target).parents('.div-group').data('id');
        $box_height = $(this).height();

        setLocalStorage('div_' + $reply_id, $(this).val());
        setLocalStorage('div_' + $reply_id + '_height', $box_height);

        //hide message count
        $('ul li[data-id*="' + $reply_id + '"]').find('div.message-count').removeClass('hidden').addClass('visible');

        $key_up_timer = setInterval(function() {
            fetch_chat_messages();
            //console.log('click topic-message');
            //console.log('storage running 3 sec');
        }, $refresh_storage);
    });

    // message count / check the remaining characters
    $(document).on('keyup', 'textarea[maxlength]', function(e) {
        e.preventDefault();
        $li_group = $(e.target).parents('.li-group');

        $li_id               = $li_group.data('id');
        $div_id              = $(e.target).parents('.div-group').data('id');
        $reply_message_count = $('ul li[data-id*="' + $div_id + '"]');

        $maxlength  = $(e.target).attr('maxlength');
        $deducted   = $(e.target).val().length;
        $remaining  = $maxlength - $deducted;

        // edit message count
        if($li_id != undefined) {
            setLocalStorage('edit_message_count_' + $li_id, $remaining);

            $li_group.find('.message-count').removeClass('hidden').addClass('visible');
            $li_group.find('.message-count span').html(getLocalStorage('edit_message_count_' + $li_id));
            $('#replaced').height(getLocalStorage('li_' + $li_id + '_height'));
        }

        // reply message count
        if($div_id != undefined) {
            setLocalStorage('reply_message_count_' + $div_id, $remaining);

            if($(e.target).val() != ''){
                $reply_message_count.find('div.message-count').removeClass('hidden').addClass('visible');
                $reply_message_count.find('div.message-count span').html(getLocalStorage('reply_message_count_' + $div_id));
            } else {
                $reply_message_count.find('div.message-count').removeClass('visible').addClass('hidden');
            }

        }

        // new topic message count
        if($li_id == undefined && $div_id == undefined) {
            $(this).height($(this).height());
            $new_topic_count = $('#chat-view-box form > div.message-count');

            if($(e.target).val() != ''){
                $new_topic_count.removeClass('hidden').addClass('visible');
                $new_topic_count.find('span').html($remaining);
            } else {
                $new_topic_count.removeClass('visible').addClass('hidden');
            }

            // for ajax loader
            $('.new-topic .sending-message-loader').css('margin-top', $(this).height() - ($(this).height()/2) - 15);
        }

    });

    // reply message to 1 topic / keyboard long press
    $(document).on('keydown', 'textarea', function (e) {

        clearInterval($key_up_timer);
        pause();
    });

    // show/hide or minimize reply messages
    $(document).on('click', '.toggle-label', function(e) {
        e.preventDefault();
        $li_group             = $(e.target).parents('.li-group');
        $parent_id            = $li_group.data('id');
        $count_reply_messages = $('ul li[data-topic-id*="' + $parent_id + '"]');
        $reply_messages       = $('ul [data-topic-id*="' + $parent_id + '"]');

        if($(e.target).parent().hasClass('accordion-off')) {
            removeCookie($parent_id);
            removeCookie($parent_id + '_value');
            $(e.target).parent().removeClass('accordion-off');

            $reply_messages.each(function() {
                $(this).slideDown('fast');
                $li_group.find('.toggle-reply i.angle').removeClass('down icon').addClass('up icon');
                $li_group.find('.toggle-reply .toggle-label').text('Hide all replies in this topic');

                // add current datetime when the message was read/open
                $.post($web_chat_url + 'read_date/', { om_id: $parent_id }, 'json');
            });
        } else {
            setCookie($parent_id, $parent_id);
            setCookie($parent_id + '_value', ($count_reply_messages.length));
            $(e.target).parent().addClass('accordion-off');

            $reply_messages.each(function() {
                $(this).slideUp();
                $li_group.find('.toggle-reply i.angle').removeClass('up icon').addClass('down icon');
                $li_group.find('.toggle-reply .toggle-label').text('Show all ' + getCookie($parent_id + '_value') + ' replies');
                $li_group.find('.toggle-reply .count_unread').removeClass('hide');
            });
        }
    });

    // open Report Box / Modal box / Sending Report
    $(document).on('click', '.report-message', function(e) {
        e.preventDefault();
        $report_box_modal = $(".report-box");

        // needed datas
        $reply_id       = $(e.target).parents('.li-group').data('id');
        $chat_message   = $(e.target).parents('.li-group').find('.message');

        setLocalStorage('chat_' + $reply_id, $chat_message.text());

        // Show Report Box / Modal box
        $report_box_modal.modal('setting', { }).modal('show all ');
        $report_box_modal.attr('data-id', $reply_id);
    });

    // open View image Box / Modal box / Viewing image
    $(document).on('click', '.view-image', function(e) {
        e.preventDefault();
        $image_box_modal = $(".view-box");
        $path            = $(this).data('path');

        // Show Image Box / Modal box
        $image_box_modal.modal('setting', { }).modal('show all ');
        $image_box_modal.css({
            'box-shadow' : 'none',
        });
        $image_box_modal.find('img').attr('src', $path);
    });

    // close View image Box
    $(document).on('click', '.view-box', function(e) {
        $image_box_modal = $(".view-box");

        $image_box_modal.modal('setting', { }).modal('hide');
    });


    // Save the report message entered by user to localStorage
    $(document).on('keyup', '.message-report', function() {

        $report_box = $(this).parents('.report-box');
        $reply_id   = $report_box.data('id');

        setLocalStorage('message_report_' + $reply_id , $(this).val());

        // disable send button if report box is empty
        if(getLocalStorage('message_report_' + $reply_id) == '') {
            $report_box.find('.green').addClass('disabled');
            $report_box.find('.green').removeClass('send-report');
        } else {
            $report_box.find('.green').removeClass('disabled');
            $report_box.find('.green').addClass('send-report');
        }
    });

    // Send/Report the Message
    $(document).on('click', '.send-report', function() {
        $report_box     = $(this).parents('.report-box');
        $reply_id       = $report_box.data('id');
        $chat_message   = getLocalStorage('chat_' + $reply_id);
        $message_report = getLocalStorage('message_report_' + $reply_id);

        $.post($web_chat_url + 'report_message', {
            message_id: $reply_id,
            table_id:   $job_id,
            notes:      'Flagged comment ['+ $chat_message +']<br />reporter notes ['+ $message_report +']'
        }, 'json');

        $('.message-report').val('');
        removeLocalStorage('chat_' + $reply_id);
        removeLocalStorage('message_report_' + $reply_id );

        $report_box.find('.green').addClass('disabled');
        $report_box.find('.green').removeClass('send-report');

    });

    // open Confirm delete Box
    $(document).on('click', '.delete-message', function(e) {
        e.preventDefault();

        $topic_id   = $(e.target).parents('.li-group').data('topic-id');
        $message_id = $(e.target).parents('.li-group').data('id');

        $(".confirm-delete-box").modal('setting', { }).modal('show');
        setLocalStorage('delete_message', $message_id);
        //console.log($message_id);

        if($module == 'campaign_discussion' || $module == 'wall'){
            if($(e.target).parents('.li-group').hasClass('parent')){
                setLocalStorage('message_type', 'parent_message');
            } else {
                setLocalStorage('message_type', 'reply_message');
            }
        }
    });

    // delete message
    $(document).on('click', '.delete', function(e) {
        e.preventDefault();
        $message_id = getLocalStorage('delete_message');

        $.post($web_chat_url + 'delete_message', { id: $message_id }, 'json');
        removeLocalStorage('delete_message');

        if($module == 'campaign_discussion' || $module == 'wall'){
            if(getLocalStorage('message_type') == 'parent_message'){
                if($module == 'wall'){
                    window.location.href = $EXPRESS_URL + $module + '/lists/?excs_id=' + getUrlVars()["excs_id"];
                } else {
                    window.location.href = $base_url + $module +  '/lists/' + $job_parent_id;
                }

            }
        }
    });

    /************** HTML ELEMENTS appended or to be added to the DOM ***********/

    /**
     *
     * Textbox for Creating New topic
     * Append textbox inside #chat-view-box element
     *
     */

    if($show_reply_box == 'TRUE'){
        $placeholder = 'Type your message here to add a new discussion or topic';
    } else {
        $placeholder = 'Type your message to this discussion';
    }

    $main_text_box  = '<form action="'+$web_chat_url+'send'+'" method="post" enctype="multipart/form-data" id="send-form">';
    $main_text_box += '<div class="topic-reply-box new-topic hide">';
    $main_text_box += '<div class="sending-message-loader hide">';
    $main_text_box += '<img src="' + $V2_URL + 'images/preloader/obloader-40.gif" alt="ajax-loader">';
    $main_text_box += '</div>';

    $main_text_box += '<textarea maxlength="500" name="message" class="chat-message" cols="30" rows="10" placeholder="' + $placeholder + '"></textarea>';
    $main_text_box += '<button class="send-chat top" title="Send the new Topic"><img src="' + $V2_URL + 'images/web_chat/send_icon.png"></button>';
    $main_text_box += '</div>';
    $main_text_box += '<div class="message-count chat-right hidden"><span>500</span> characters remaining</div>';
    $main_text_box += '<p class="clear"></p>';
    $main_text_box += '</form>';

    $chat_box.append($main_text_box);

    // clicking send button / Starting the conversation
    $(document).on('click', '.send-chat', function(e) {
        e.preventDefault();
        $chat_message   = $('.chat-message');
        $new_topic      = $('.new-topic');
        $campaign_name  = $('.campaign-name').text();
        $topic_title    = $('.topic-title').text();
        $total_files    = getLocalStorage('total_files');
        $reply_id       = $('.chat-box ul li:first').data('id');

        if($.trim($chat_message.val()) != '') {

            $(e.target).parents('.new-topic').find('.sending-message-loader').removeClass('hide');
            $chat_message.css('opacity', '0.5');

            if($show_reply_box == 'TRUE'){
                if($module == 'post_job'){
                    $ref_table_id = $job_parent_id;
                    $parent_id    = '';
                } else {
                    $ref_table_id = $job_id;
                    $parent_id    = '';
                }
            } else {

                if($module == 'post_job'){
                    if($('.chat-box ul li').length == 0){
                        $parent_id = '';
                    } else {
                        $parent_id = $reply_id;
                    }
                } else if($module == 'interviews') {
                    $parent_id = $reply_id;
                } else {
                    $parent_id = $job_id;
                }

                $ref_table_id = $job_parent_id;
                $title        = '';
            }

            $('#send-form').ajaxSubmit({
                data: {
                    job_id:         $job_id,
                    job_parent_id:  $job_parent_id,
                    ref_table_id:   $ref_table_id,
                    parent_id:      $parent_id,
                    module:         $module,
                    default:        $default,
                    sending_sms:    $sending_sms,
                    message:        $chat_message.val(),
                    total_files:    $total_files
                },
                success: function(){
                    if($sending_email == 'TRUE') {

                        $email_option = $("input[name=sending-email]:checked").val();

                        if ($email_option == 1) {

                            $.ajax({
                                type: 'post',
                                data: {
                                    job_id:         $job_id,
                                    job_parent_id:  $job_parent_id,
                                    module:         $module,
                                    default:        $default,

                                    email_option:   $email_option,
                                    campaign_name:  $campaign_name,
                                    topic_title:    $topic_title,
                                    message:        $chat_message.val(),
                                    total_files:    $total_files

                                },
                                url: $web_chat_url + 'send_email/'
                            });

                        } else if ($email_option == 2) {
                            $checkbox   = $('input[name=recipients]');
                            $total      = $checkbox.length;
                            $unchecked  = $checkbox.not(':checked').length;

                            // fetch selected checkboxes
                            $checked = $('input[name=recipients]:checked').map(function () {
                                return $(this).val();
                            }).get();

                            if($unchecked == 0)
                                $email_option = 1;

                            if ($total == $unchecked)
                                $email_option = 3;

                            if($email_option != 3) {

                                $.ajax({
                                    type: 'post',
                                    data: {
                                        job_id:         $job_id,
                                        job_parent_id:  $job_parent_id,
                                        module:         $module,
                                        default:        $default,

                                        email_option:   $email_option,
                                        campaign_name:  $campaign_name,
                                        topic_title:    $topic_title,
                                        message:        $chat_message.val(),
                                        recipients:     $checked.join(','),
                                        total_files:    $total_files

                                    },
                                    url: $web_chat_url + 'send_email/'
                                });
                            }

                        } else { }

                        $('.email-recipients').hide();
                        $('.sending-email .field:nth-child(1) input').prop('checked', true);
                        $('.sending-sms').find('input').attr('checked', false);
                    }
                    //console.log('sucess');
                },
                complete:   function(){
                    $('#chat-view-box form > div.message-count').removeClass('visible').addClass('hidden');
                    $(this).prop('disabled', true);
                    $('.choose-file').val('');
                    $('.more-files').remove();
                    clearInterval($key_up_timer);
                    $('.chat-message').val('');
                    //console.log('click send-chat');
                    pause();
                    localStorage.clear();
                    start();
                }
            });
            e.preventDefault();
            return false;
        } else {
            $chat_message.putCursorAtEnd();
            //pause();
        }
    });

    /**
     *
     * Radio Buttons and input file for Sending Email
     * Used in campaign discussion module only
     *
     */
    $email_form  = '<div class="ui form sending-email hide">';
    $email_form += '<div class="grouped fields">';

    $email_form += '<div class="field">';
    $email_form += '<div class="ui radio checkbox">';
    $email_form += '<input type="radio" name="sending-email" checked="checked"  id="1" value="1">';
    $email_form += '<label for="1">When I post this message, email it to all </label>';
    $email_form += '</div>';
    $email_form += '</div>';

    $email_form += '<div class="field">';
    $email_form += '<div class="ui radio checkbox">';
    $email_form += '<input type="radio" name="sending-email" id="2" value="2">';
    $email_form += '<label for="2">Let me choose who should get an email…</label>';
    $email_form += '</div>';
    $email_form += '</div>';

    $email_form += '<div class="email-recipients"></div>';

    $email_form += '<div class="field">';
    $email_form += '<div class="ui radio checkbox">';
    $email_form += '<input type="radio" name="sending-email" id="3" value="3">';
    $email_form += '<label for="3">Don’t email anyone.</label>';
    $email_form += '</div>';
    $email_form += '</div>';

    if($attaching_files == 'TRUE') {
        $attach_form  = '<div class="file-group"><a href="#" class="file-remove" title="Remove"><i class="remove icon"></i></a>';
        $attach_form += '<input type="file" name="userfile[]" class="choose-file" />';
        $attach_form += '</div>';
        $attach_form += '<p><a href="#" class="add-file"><i class="plus icon"></i>Add more files</a></p>';
    }

    if($sending_sms == 'TRUE') {
        $sms_form  = '<div class="ui checked checkbox sending-sms hidden">';
        $sms_form += '<input type="checkbox" name="sms" id="sms" value="1">';
        $sms_form += '<label for="sms">Send as sms message as well ($.02).</label>';
        $sms_form += '</div>';
    }

    $email_form += '</div>';
    $email_form += '</div>';

    // checking the file first before uploading and sending
    $(document).on('change', 'input[type=file]', function(){

        $fileCount = this.files.length;

        if($fileCount == 1){
            $(this).attr('included','included');

            $file_size      = ($fileCount == 1) ? this.files[0].size : 0;
            $file_extension = $(this).val().split('.').pop().toLowerCase();
            $allowed_types  = ['jpg','jpeg','png','gif','pdf','key','pptx','pps','ppsx','odt','xls','xlsx','zip','mp3','m4a','wav'];

            if($.inArray($file_extension, $allowed_types) == -1 && $file_size >= 10000000){

                $(this).removeAttr('included');
                $(this).next('.red').remove();
                $(this).after("<p class='red'>File size exceeded and File format is not valid.</p>");
                $(this).parent().height(50);

            } else if($.inArray($file_extension, $allowed_types) == -1){

                $(this).removeAttr('included');
                $(this).next('.red').remove();
                $(this).after("<p class='red'>File format is not valid.<br>" +
                    "Available formats. .jpg, .jpeg, .png, .gif, .pdf, .key, .pptx, .pps, .ppsx, .odt, .xls, .xlsx, .zip, .mp3, .m4a, .wav.</p>");
                $(this).parent().height(65);

            } else if($file_size >= 10000000){

                $(this).removeAttr('included');
                $(this).next('.red').remove();
                $(this).after("<p class='red'>File size exceeded. Max size should be 10mb.");
                $(this).parent().height(50);

            } else {
                $(this).next('.red').remove();
                $(this).parent().height(20);
            }
        } else {
            $(this).removeAttr('included');
            $(this).next('.red').remove();
            $(this).parent().height(20);
        }
        $('.red').css('float','left');

        // save total number of files for email
        $total_files = $('.choose-file[included="included"]').length;
        setLocalStorage('total_files', $total_files);
    });

    // email recipients for campaign_discussion module
    if($sending_email == 'TRUE'){
        $('.message-count.chat-right').after($email_form);
        if($attaching_files == 'TRUE'){
            $('.sending-email .fields').append($attach_form);
        }
    } else {
        if($attaching_files == 'TRUE'){
            $('.message-count.chat-right').after('<div class="attach-form-only">'+$attach_form+'</div>');
            $('.attach-form-only').css({
                'float': 'left',
                'margin': '10px'
            });

            $('#file-upload').css('padding','20px')
        }
    }

    // email recipients for campaign_discussion module
    if($sending_sms == 'TRUE'){
        $('.message-count.chat-right').after($sms_form);
    }

    $.ajax({
        type: 'post',
        data: {
            job_id:         $job_id,
            job_parent_id:  $job_parent_id,
            module:         $module,
            default:        $default
        },
        dataType: 'html',
        url: $web_chat_url + 'fetch_email_recipients/',
        cache: false,
        success: function(data) {
            $('.email-recipients').html(data);
        }
    });

    //console.log($job_parent_id);

    // check radio button value
    $('input[name=sending-email]:radio').on('change', function() {
        $selected = $("input[name=sending-email]:checked").val();
        if($selected == 2){
            $('.email-recipients').show();
        } else {
            $('.email-recipients').hide();
        }
    });

    // add more files
    $('.add-file').on('click', function(e){
        e.preventDefault();
        $('.file-group:last').after('<div class="file-group more-files"><a href="#" class="file-remove" title="Remove"><i class="remove icon"></i></a><input type="file" name="userfile[]" class="choose-file" /></div>');
    });

    // remove file
    $(document).on('click', '.file-remove', function(e){
        e.preventDefault();
        $(this).parent().find('input').removeAttr('included');
        $(this).parent().remove();

        // save total number of files for email
        $total_files = $('.choose-file').length;
        setLocalStorage('total_files', $total_files);
    });


    /**
     *
     * HTML elements for the Modal Box / Report Box
     * Used when reporting a message
     *
     */

    $report_box  = '<div class="report-box ui modal small">';
    $report_box += '<i class="close icon"></i>';
    $report_box += '<div class="header">Send Report Message</div>';
    $report_box += '<div class="content">';
    $report_box += '<div class="notify">';
    $report_box += '<div class="ui success message hide">';
    $report_box += '<i class="close icon"></i>';
    $report_box += '<div class="header"></div>';
    $report_box += '</div>';
    $report_box += '<div class="ui error message hide">';
    $report_box += '<i class="close icon"></i>';
    $report_box += '<div class="header"></div>';
    $report_box += '</div>';
    $report_box += '</div>';
    $report_box += '<div class="ui form">';
    $report_box += '<div class="field">';
    $report_box += '<label>Reason for reporting:</label>';
    $report_box += '<div class="ui labeled icon input">';
    $report_box += '<textarea class="message-report" placeholder="Please Enter Reason for Reporting"></textarea>';
    $report_box += '<div class="ui corner label">';
    $report_box += '<i class="icon asterisk"></i>';
    $report_box += '</div>';
    $report_box += '</div>';
    $report_box += '</div>';
    $report_box += '</div>';
    $report_box += '</div>';
    $report_box += '<div class="actions">';
    $report_box += '<div class="ui button green disabled">Send</div>';
    $report_box += '</div>';
    $report_box += '</div>';
    $chat_box.append($report_box);

    /**
     *
     * HTML elements for the Modal Box / View image box
     * Used when viewing images
     *
     */

    $view_image  = '<div class="view-box ui modal" style="width: 100%;">';
    $view_image += '<div class="image content">';
    $view_image += '<img class="image">';
    $view_image += '</div>';
    $view_image += '</div>';
    $chat_box.after($view_image);

    /**
     *
     * HTML elements for the Modal Box / Confirm Delete Box
     * Used when deleting a message
     *
     */

    $confirm_delete  = '<div class="confirm-delete-box ui small modal">';
    $confirm_delete += '<div class="header">Delete Your Message</div>';
    $confirm_delete += '<div class="content">';
    $confirm_delete += '<p>Are you sure you want to delete your message?</p>';
    $confirm_delete += '</div>';
    $confirm_delete += '<div class="actions">';
    $confirm_delete += '<div class="ui negative button cancel">No</div>';
    $confirm_delete += '<div class="ui positive right labeled icon button delete">YES<i class="checkmark icon"></i></div>';
    $confirm_delete += '</div>';
    $confirm_delete += '</div>';
    $chat_box.append($confirm_delete);
});
