<style>

    .dropdown-search * {
        font-family: Lato,'Helvetica Neue',Arial,Helvetica,sans-serif;
    }

    .filtered {
        display: block;
    }

    .unfiltered, .hide, div.selected {
        display: none;
    }

    .options img.selected {
        opacity: 0.2;
    }

    .filtered-list {
        max-width: 720px;
        padding: 5px;
        border: 1px solid #bfbfbf;
    }

    .dropdown-search .filtered-list span {
        padding: 3px;
        margin: 2px;
        background: #2d3e50;
        color: black;
        border-radius: 5px;
        display: inline-block;
        overflow: hidden;
    }

    .filtered-list span i:hover,
    .toggle-slide {
        cursor: pointer;
    }

    .filtered-list .lists img {
        float: left;
        padding: 0 5px;
    }

    .filtered-list .lists i.remove {
        position: relative;
        top: 5px;
        color: #fff;
    }

    .filtered-list .lists b,
    .filtered-list div.campaign-name,
    .filtered-list div.agent-name {
        font-weight: normal;
        color: #fff;
    }

    .filtered-list i.search {
        float: left;
        padding: 5px 0;
    }

    .filtered-list i.triangle {
        float: right;
        padding: 5px;
        height: 25px;
        width: 25px;
    }

    .dropdown-search select {
        width: 720px;
        overflow: hidden;
    }

    .dropdown-search img {
        width: 25px;
        height: 25px;
        border-radius: 50%;
        padding: 10px 5px;
        float: left;
    }

    .dropdown-search .options {
        width: 730px;
        border: 1px solid #bfbfbf;
        max-height: 250px;
        overflow-y: scroll;
        position: absolute;
        z-index: 999;
        background-color: #fff;
    }

    .dropdown-search .user,
    .dropdown-search div.no-result {
        width: 720px;
        border-bottom: 1px solid #bfbfbf;
    }

    .dropdown-search .user div,
    .dropdown-search div.no-result span {
        color: #4e4545;
        padding: 15px 5px;
        display: inline-block;
        -webkit-box-sizing: border-box;
        -moz-box-sizing: border-box;
        box-sizing: border-box;
    }

    .dropdown-search input,
    .dropdown-search input:focus {
        border: none;
        outline: none;
        width: 90%;
        padding: 5px;
    }

    .separator {
        display: block;
        clear: both;
    }

    .dropdown-search .campaign-name,
    .dropdown-search .agent-name {
        float: left;
        padding: 5px;
        color: #4e4545;
    }
    
    .dropdown-search .campaign-details img {
        float: none;
    }

    .dropdown-search .agent-details {
        float: left;
    }
    
    b {
        font-weight: bolder;
        color: #000;
    }
</style>

<div class="dropdown-search">
    <div class="filtered-list">
        <div class="lists true">
        </div>
        <i class="search icon"></i>
        <input type="text" class="search" placeholder="Search for Name, Email or Campaign Name">
        <i class="toggle-slide triangle down icon"></i>
    </div>
    <div class="options hide">

        <div class="user campaign-details" data-campaign-name="one campaign">
            <div class="info campaign-name">one campaign</div>
            <img src="http://localhost/obsjr/v2/uploads/images_file/7_56148802170418194954414154.jpg" data-id="1" title="vandolph reyes">
            <img src="http://localhost/obsjr/v2/uploads/images_file/27_52143812992374023258350236.jpg" data-id="2" title="vandolph reyes">
            <img src="http://localhost/obsjr/v2/uploads/images_file/28_66145803362890042434242861.jpg" data-id="3" title="vandolph reyes">
        </div>

        <span class="separator"></span>

        <div class="user agent-details" data-campaign-name="one campaign">
            <img src="http://localhost/obsjr/v2/uploads/images_file/7_56148802170418194954414154.jpg" data-id="1" title="vandolph reyes">
            <div class="info agent-name">vandolph reyes (dummy_test@gmail.com)</div>
        </div>

        <span class="separator"></span>

        <div class="user agent-details" data-campaign-name="one campaign">
            <img src="http://localhost/obsjr/v2/uploads/images_file/27_52143812992374023258350236.jpg" data-id="2" title="vandolph reyes">
            <div class="info agent-name">jonnavel reyes (test123@gmail.com)</div>
        </div>

        <span class="separator"></span>

        <div class="user agent-details" data-campaign-name="one campaign">
            <img src="http://localhost/obsjr/v2/uploads/images_file/28_66145803362890042434242861.jpg" data-id="3" title="vandolph reyes">
            <div class="info agent-name">nathaniel vann reyes (fake.fake@gmail.com)</div>
        </div>

        <span class="separator"></span>

        <div class="user campaign-details" data-campaign-name="test campaign">
            <div class="info campaign-name">test campaign</div>
            <img src="http://localhost/obsjr/v2/uploads/images_file/1_35167902701553216922265074.jpg" data-id="4" title="vandolph reyes">
            <img src="http://localhost/obsjr/v2/uploads/images_file/3_74161902761289217314842864.jpg" data-id="5" title="vandolph reyes">
        </div>

        <span class="separator"></span>

        <div class="user agent-details" data-campaign-name="test campaign">
            <img src="http://localhost/obsjr/v2/uploads/images_file/1_35167902701553216922265074.jpg" data-id="4" title="vandolph reyes">
            <div class="info agent-name">charles vann reyes (games_123@gmail.com)</div>
        </div>

        <span class="separator"></span>

        <div class="user agent-details" data-campaign-name="test campaign">
            <img src="http://localhost/obsjr/v2/uploads/images_file/3_74161902761289217314842864.jpg" data-id="5" title="vandolph reyes">
            <div class="info agent-name">cherry vann reyes (fashion24@yahoo.com)</div>
        </div>

        <span class="separator"></span>

        <div class="user campaign-details" data-campaign-name="new campaign">
            <div class="info campaign-name">new campaign</div>
            <img src="http://localhost/obsjr/v2/uploads/images_file/7_56148802170418194954414154.jpg" data-id="6" title="vandolph reyes">
        </div>

        <span class="separator"></span>

        <div class="user agent-details" data-campaign-name="new campaign">
            <img src="http://localhost/obsjr/v2/uploads/images_file/3_74161902761289217314842864.jpg" data-id="6" title="vandolph reyes">
            <div class="info agent-name">new vann reyes (fashion24@yahoo.com)</div>
        </div>

        <span class="separator"></span>

        <div class="no-result hide">
            <span class="">No Results Found.</span>
        </div>

        <span class="separator"></span>
    </div>
</div>

<script>
    $(function(){

        $search        = $('.search');
        $options       = $('.options');
        $user_info     = $('.dropdown-search div.user');
        $filtered_list = $('.filtered-list');

        // escaping regex special characters
        function escapeRegExp(str) {
            return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
        }

        // searching recipients
        $search.on('keyup', function(e){
            $this         = $(this);
            $regex_search = new RegExp(escapeRegExp($this.val()), 'gi');

            // checking result
            if($this.val() != ''){
                $user_info.find('.info').each(function(){
                    $option = $(this).text();

                    // show/hide searched recipients
                    if($regex_search.test($option)){
                        $(this).parent().not('.selected').removeClass('unfiltered').addClass('filtered');

                        // search highligthing
                        highlight = $option.replace($regex_search, '<b>'+ $this.val() +'</b>');
                        $(this).html(highlight);
                    } else {
                        $(this).parent().removeClass('filtered').addClass('unfiltered');
                    }
                });

                if($options.find('.filtered').length == 0){
                    $('.no-result').show();
                } else {
                    $('.no-result').hide();
                }

                $filtered_list.find('.lists').removeClass('true');
            } else {
                $user_info.removeClass('filtered unfiltered');

                if($filtered_list.find('.lists').hasClass('true') && e.keyCode == 8) {
                    $this.parent().find('span:last i').click();
                    $this.parent().find('span:last').remove();
                }

                $filtered_list.find('.lists').addClass('true');
            }
        });

        // focus in/out for search                                                                                                                                                                                                                  box
        $search.on('focusin', function(){
            $options.slideDown('fast');
        });
        $search.on('blur', function(){
            if($(this).val() == ''){
                $user_info.removeClass('filtered unfiltered');
            }
        });

        // mousehovering on lists
        $user_info.hover(
            function() { // in
                $(this).css({
                    'background': '#2d3e50',
                    'cursor': 'pointer'
                });
                $(this).find('div').css('color', '#fff');
            },

            function() { // out
                $(this).removeAttr('style');
                $(this).find('div').removeAttr('style');
            }
        );

        // adding recipients on the lists
        $user_info.on('click', function(){
            $search.focusin();
            $campaign_name   = $(this).data('campaign-name');

            $(this).slideUp('fast', function(){
                $(this).removeClass('filtered').addClass('selected');
            });

            $filtered_list.find('.lists').append('<span data-campaign-name="'+$campaign_name+'" style="display:none">' + $(this).html() + '<i class="remove icon"></span>');
            $filtered_list.find('.lists span').show('fast');

            if($(this).hasClass('campaign-details')){
                $campaign_agents = $('div.agent-details[data-campaign-name*="'+$campaign_name+'"]');

                $(this).find('img').each(function(){
                    $('.user img[data-id*="' + $(this).data('id') + '"]').parent().hide(1000, function(){
                        $(this).addClass('selected');
                    });
                });

            } else {
                $agent_id = $(this).find('img').data('id');
                $campaign_lists = $options.find('div.campaign-details[data-campaign-name*="'+$campaign_name+'"]');

                $('.campaign-details').find('img').each(function(){
                    if($agent_id == $(this).data('id')){
                        $(this).addClass('selected');
                    }
                });

                $selected_agents = $campaign_lists.find('img.selected').length;
                $total_agents    = $campaign_lists.find('img').length;

                if($selected_agents == $total_agents){
                    $filtered_list.find('span[data-campaign-name*="'+$campaign_name+'"]').hide('fast', function(){
                        $filtered_list.find('span[data-campaign-name*="'+$campaign_name+'"]').remove();
                    });

                    $filtered_list.find('.lists').append('<span style="display:none">' + $campaign_lists.html() + '<i class="remove icon"></span>');
                    $filtered_list.find('.lists span').show('fast');

                    $campaign_lists.slideUp('fast', function(){
                        $(this).removeClass('filtered').addClass('selected');
                    });
                }
            }
        });

        // removing recipients from the lists
        $(document).on('click', '.filtered-list span i', function(e){
            $user_id = $(e.target).parent().find('img');

            $(e.target).parent().hide('show', function(){
                $(e.target).parent().remove();
            });

            $user_id.each(function(){
                $user = $('img[data-id*="'+ $(this).data('id') +'"]');

                $user.parent().slideDown('fast', function(){
                    $(this).removeClass('selected filtered unfiltered');
                });

                $user.removeClass('selected');
            });

            $('.no-result').hide();
        });

        // slide up recipients lists
        $('.toggle-slide').on('click', function(){
            if($(this).hasClass('down')){
                $(this).removeClass('triangle down icon').addClass('triangle up icon');
                $options.slideDown('fast');
            } else {
                $(this).removeClass('triangle up icon').addClass('triangle down icon');
                $options.slideUp('fast');
            }
        });
   });
</script>