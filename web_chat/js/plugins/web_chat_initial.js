/*!
 *
 * jQuery Plugin
 * Generate images using svg with Users Initials
 * Vandolph Reyes
 *
 * Configurable Settings
 * order:           (unique number or user_id),
 * initial:         fullname|firstname|lastname,
 * color:           (red) | rbg colors | hsl colors | hex colors,
 * fontSize:        '14px' | '50%',
 * height:          '50px' | '100%',
 * width:           '50px' | '100%',
 * borderWidth:     '1px',
 * borderStyle:     'solid' | 'dotted',
 * borderColor:     (red) | rbg colors | hsl colors | hex colors,
 * borderRadius:    '10px' | '10%'
 *
 * Example
    // call the script
    <script type="text/javascript" src="<?= WEB_CHAT_PLUGIN_URL; ?>web_chat_initial.js"></script>

     // display image with initials
     $('.profile-picture').web_chat_initials({
         order:        150,
         initial:      'fullname',
         firstname:    $(this).data('firstname'),
         lastname:     $(this).data('lastname'),
         color:        'white',
         fontSize:     '24px',
         height:       '52px',
         width:        '52px',
         borderRadius: '28px'
     });
 *
 */

(function($){

    $.fn.web_chat_initials = function(settings){

        $background_colors = [
            "#838B8B", "#7A8B8B", "#668B8B", "#03A89E", "#00C78C", "#00FA9A", "#54FF9F", "#698B69", "#32CD32", "#308014",
            "#D870AD", "#F69785", "#9BA37E", "#B49255", "#B49255", "#A94136", "#C63D0F", "#3B3738", "#7E8F7C", "#005A31",
            "#67BCDB", "#A2AB58", "#404040", "#6DBDD6", "#67BCDB", "#B8860B", "#CD8500", "#8B7E66", "#CD4F39", "#F08080",
            "#1ABC9C", "#CD00CD", "#27AE60", "#4682B4", "#FF00FF", "#16A085", "#F1C40F", "#F39C12", "#2ECC71", "#E67E22",
            "#B0171F", "#DC143C", "#8B5F65", "#8B636C", "#8B475D", "#8B8386", "#EE3A8C", "#FF69B4", "#CD6090", "#8B3A62",
            "#483D8B", "#4169E1", "#6E7B8B", "#1C86EE", "#36648B", "#00B2EE", "#3Af1C9", "#8EE5EE", "#00E5EE", "#3498DB",
            "#8B4789", "#8B7B8B", "#8B668B", "#BA55D3", "#D15FEE", "#7A378B", "#BF3EFF", "#BF3EFF", "#2980B9","#D35400",
            "#6E8B3D", "#B3EE3A", "#8B8B7A", "#CD9B1D", "#9C661F", "#FF8000", "#C76114", "#FF7F24", "#FF4040", "#7171C6",
            "#E74C3C", "#C0392B", "#9B59B6", "#8E44AD", "#BDC3C7", "#34495E", "#2C3E50", "#95A5A6", "#7F8C8D", "#EC87BF",
            "#A8CD1B", "#CBE32D", "#558C89", "#74AFAD", "#D9853B", "#2B2B2B", "#DE1B1B", "#7D1935", "#4A96AD", "#E44424"
        ];

        return this.each(function(){
            $this = $(this);

            // default settings
            var $settings = $.extend({
                fontFamily: 'Arial, sans-serif'
            }, settings);

            // overriding from data attributes
            $settings = $.extend($settings, $this.data());

            // check what initial to be displayed
            $firstname  = $settings.firstname;
            $lastname   = $settings.lastname;
            $fullname   = $firstname.charAt(0) + $lastname.charAt(0);
            $length     = $background_colors.length;
            $order      = ($settings.order > $length) ? ($settings.order % $length) : $settings.order;

            $this.css({
                backgroundColor: $background_colors[$order],
                height:          $settings.height,
                width:           $settings.width,
                borderWidth:     $settings.borderWidth,
                borderStyle:     $settings.borderStyle,
                borderColor:     $settings.borderColor,
                borderRadius:    $settings.borderRadius
            });

            if ($settings.initial == 'firstname') {
                $initial = $firstname.charAt(0);
            } else if ($settings.initial == 'lastname') {
                $initial = $lastname.charAt(0);
            } else {
                $initial = $firstname.charAt(0) + $lastname.charAt(0);
            }

            $svg  = '<svg xmlns="http://www.w3.org/2000/svg">';
            $svg += '<text text-anchor="middle" x="50%" y="50%" dy="0.35em" font-size="' + $settings.fontSize + '" font-family="' + $settings.fontFamily + '" fill="' + $settings.color + '">' + $initial.toUpperCase() + '</text>';
            $svg += '</svg>';

            $this.attr({
                'src': 'data:image/svg+xml;base64,' + window.btoa(unescape(encodeURIComponent($svg)))
            });
        });
    };

}(jQuery));