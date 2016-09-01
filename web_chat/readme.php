## WEB_CHAT MODULE GUIDELINES

# Assets
    1. web_chat css file
        - all css codes for this module
        - required
        - <link type="text/css" rel="stylesheet" href="<?= WEB_CHAT_CSS_URL; ?>" property=""/>

    2. web_chat js file
        - all js codes and front end transactions are here
        - <script type="text/javascript" src="<?= WEB_CHAT_JS_URL; ?>"></script>

    3. ajax form js file
        - jquery plugin for sending form compatible in all browsers
        - <script type="text/javascript" src="http://malsup.github.com/jquery.form.js"></script>

    4. web_chat initial plugin js file
        - plugin for displaying images as profile pictures with it's initials names inside web_chat conversations
        - <script type="text/javascript" src="<?= WEB_CHAT_PLUGIN_URL; ?>web_chat_initial.js"></script>

        - used in v2/campaign_discussion and express/wall only where client_postjob.type = 'E'


        UPDATE:
        - ob_helper.php has a function with the same functionality of this plugin
        - $this->load->helper(HELPER_PATH . 'ob_helper');
        - avatar_initial($initial, $avatar_size, $font_size, $sequence_number)

# HTML CODE
    <div id="chat-view-box">
        <div class="chat-box">
            <div class="ajax-loader">
                <img src="<?= OBS_URL; ?>images/preloader/obloader-80.gif" alt="ajax-loader">
            </div>
            <br>

            <p class="chat-center">Preparing Messages</p>
        </div>
    </div>

# Configurables / Data Attributes

    1. data-module
        - select the module using the web_chat module
        - http://localhost/obsjr/v2/[post_job]/view/316900648ca2099afeb3c535b86041ac (data-module="post_job")
        - used to display the table names based on this data-module

        - data-module="<?= $this->router->fetch_class(); ?>"

    2. data-id
        - select the id(table id) of the message from obs_message
        - used to display the messages
        - id is encrypted, but will be decrypted once called
        - http://localhost/obsjr/v2/POST_JOB/view/[316900648ca2099afeb3c535b86041ac]
        - sample: (data-id="1056")

        - obs_message.id
        - data-id="<?= $message_id; ?>"

    3. data-parent-id
        - id from the referenced table (tables that are used from the modules using web_chat module)
        - select the id from the referenced table (most likely job_ids or campaign_ids)
        - used to display the messages
        - sample: (data-parent-id="156")

        - obs_message.ref_table_id
        - data-parent-id="<?= $job_id; ?>"

    4. data-edit
        - allowing and not allowing users to edit messages
        - TRUE [show edit button for messages]
        - FALSE [hide edit button for messages]

        - data-edit="TRUE"

    5. data-delete
        - allowing and not allowing users to delete messages
        - TRUE [show delete button for messages]
        - FALSE [hide delete button for messages]

        - data-delete="TRUE"

    6. data-attach
        - allowing and not allowing users to attach files while sending messages
        - TRUE [show attach button for messages]
        - FALSE [hide attach button for messages]

        - data-attach="TRUE"

    7. data-email
        - allowing and not allowing users to send and email
        - TRUE [show email radio buttons for sending emails]
        - FALSE [hide email radio buttons for sending emails]

        - data-email="TRUE"

    8. data-show-reply-box
        - show/display the reply box of each topic
        - TRUE  [users can create/add a new topic/discussion, can have more that 1 thread inside web_chat]
        - FALSE [users can't create/add a new topic/discussion, 1 thread only]

        - data-show-reply-box="TRUE"
        - note: whenever this config will be updated either v2/campaign_discussion or express/wall module,
                both of the module should be updated too as express/wall module is also a campaign_discussion

    9. data-show-reply-to-reply-box
        - show/display the reply to reply box of each message
        - TRUE  [users can reply to a reply message inside web_chat]
        - FALSE [users can't reply to a reply message inside web_chat]

        - data-show-reply-to-reply-box="FALSE"

    10. data-default
        - (for campaign_discussion and wall module only)
        - select the recipients
        - TRUE  [select all campaign agents as recipients]
        - FALSE [select only users that joined the conversation(for campaign_discussion and wall module only)]

        - data-default="TRUE"
        - note: if module is not v2/CAMPAIGN_DISCUSSION or expres/WALL module, set data-default="FALSE"


    11. data-allowed
        - (for post_job module only)
        - check if users is logined and verified
        - allowing and not allowing users to join the conversation
        - not required to add inside the html code
        - TRUE  [show the textbox and send button for the conversation]
        - FALSE [hide the textbox and send button for the conversation]

        - data-allowed="TRUE"

    12. data-v2-url
        - used to get the exact V2 URL
        - data-v2-url="<?= WEB_CHAT_V2_URL; ?>"

    13. data-express-url
        - used to get the exact Express URL
        - data-express-url="<?= WEB_CHAT_EXPRESS_URL; ?>">


## HOW TO USE

Copy and paste the code below inside VIEW.PHP on your module
```sh
<!-- Web Chat Starts Here -->
<link type="text/css" rel="stylesheet" href="<?= WEB_CHAT_CSS_URL; ?>" property=""/>
<script type="text/javascript" src="http://malsup.github.com/jquery.form.js"></script>
<script type="text/javascript" src="<?= WEB_CHAT_PLUGIN_URL; ?>web_chat_initial.js"></script> // add this plugin if needed
<script type="text/javascript" src="<?= WEB_CHAT_JS_URL; ?>"></script>

<div id="chat-view-box" data-module="<?= $this->router->fetch_class(); ?>"
     data-id="<?= $message_id; ?>" data-parent-id="<?= $job_id; ?>" data-edit="TRUE"
     data-delete="TRUE" data-attach="TRUE" data-email="TRUE" data-show-reply-box="TRUE"
     data-show-reply-to-reply-box="FALSE" data-default="TRUE"
     data-v2-url="<?= WEB_CHAT_V2_URL; ?>" data-express-url="<?= WEB_CHAT_EXPRESS_URL; ?>">

    <div class="chat-box">
        <div class="ajax-loader">
            <img src="<?= base_url(); ?>images/preloader/obloader-80.gif" alt="ajax-loader">
        </div>
        <br>
        <p class="chat-center">Preparing Messages</p>
    </div>
</div>

<!-- Web Chat Starts Here -->
```

#Notes: Update data-id and data-parent-id based on what variable you assigned the id and parent_id/job_id


