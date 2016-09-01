<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Web_chat extends User_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model(MODEL_PATH . 'obs_message_model');
        $this->load->model(MODEL_PATH . 'obs_message_recipients_model');
        $this->load->model(MODEL_PATH . 'user_reports_model');
        $this->load->model(MODEL_PATH . 'campaign_comments_attachment_model');
        $this->load->model(MODEL_PATH . 'main_notification_model');
        $this->load->model(MODEL_PATH . 'uploads_model');
        $this->load->model(MODEL_PATH . 'client_postjob_model');
        $this->load->model(MODEL_PATH . 'client_job_posting_model');
        $this->load->model(MODEL_PATH . 'main_messaging_model');
        $this->load->model(MODEL_PATH . 'main_messages_set_model');
        $this->load->model(MODEL_PATH . 'main_messaging_users_model');

        $this->load->library(LIBRARY_PATH . 'obmail');
        $this->load->library('upload');

        $this->load->helper(HELPER_PATH . 'text_helper');
        $this->load->helper(HELPER_PATH . 'ob_helper');
        $this->load->helper(array('url'));
    }

    public function send()
    {
        if ($this->input->post('message') != '') {

            $job_id        = $this->input->post('job_id');
            $job_parent_id = $this->input->post('job_parent_id');
            $ref_table_id  = $this->input->post('ref_table_id');
            $module        = $this->input->post('module');
            $default       = $this->input->post('default');
            $total_files   = $this->input->post('total_files');
            $sms           = $this->input->post('sms');

            // data for obs_message table
            if ($this->input->post('parent_id') != NULL) {
                $parent_id  = $this->input->post('parent_id');
                $type       = 0;
            } else {
                $parent_id  = NULL;
                $type       = 1;
            }

            if ($module == 'post_job') {
                $ref_table      = 'client_job_posting';
                $message_type   = 1;
            } elseif ($module == 'messages') {
                $ref_table      = 'main_messaging';
                $type           = $this->obs_message_model->find_single_data('type', ['id' => $job_id]);
                $message_type   = 2;
            } elseif ($module == 'interviews') {
                $ref_table      = 'main_messaging';
                $message_type   = 4;
            } else {
                $ref_table      = 'client_postjob';
                $message_type   = 3;
            }

            $data_message = [
                'ref_table'     => $ref_table,
                'ref_table_id'  => $ref_table_id,
                'user_id'       => $this->user_id,
                'parent_id'     => $parent_id,
                'message'       => $this->input->post('message'),
                'type'          => $type,
                'message_type'  => $message_type,
                'status'        => 1,
            ];

            $last_id = $this->obs_message_model->insert($data_message);

            //data for obs_message_recipients table
            $condition = ($module == 'campaign_discussion' || $module == 'wall') && $default == 'TRUE';

            if($condition){
                $recipients = $this->obs_message_model->fetch_campaign_agents($job_parent_id, $this->user_id);
            } else {
                if($module == 'messages'){
                    $recipients = $this->obs_message_recipients_model->message_recipients($job_id);

                    // set message as new
                    $this->obs_message_model->update_where(['id' => $job_parent_id], ['message_sequence' => date('Y-m-d h:i:s')]);
                } elseif($module == 'interviews') {
                    if($this->usermodel->is_client($this->user_id) == TRUE){
                        $recipients = $this->obs_message_recipients_model->interviewee($job_parent_id);
                    } else {
                        $recipients = $this->obs_message_model->interviewer($job_parent_id);
                    }
                } else {
                    $recipients = $this->obs_message_model->fetch_all_recipients($job_id, $ref_table_id, $this->user_id);
                }
            }

            foreach($recipients->result() as $info){
                if($condition) {
                    $recipient_id = $info->agent_id;
                } elseif($module == 'messages' || $module == 'interviews'){
                    $recipient_id = $info->recipient_id;
                } else {
                    $recipient_id = $info->user_id;
                }

                $data_info = [
                    'om_id'         => $last_id,
                    'recipient_id'  => $recipient_id,
                ];
                $this->obs_message_recipients_model->insert($data_info);
            }

            if($total_files > 0)
                $this->_upload_files($last_id);

            $this->_trash_to_inbox($ref_table_id);
        }
    }

    private function _trash_to_inbox($id)
    {
        $this->obs_message_recipients_model->update_where(['om_id' => $id, 'recipient_id !=' => $this->user_id], ['trashed_by' => null]);

        $reply_ids = $this->obs_message_model->find_all(['ref_table_id' => $id]);
        foreach($reply_ids as $ids){
            $this->obs_message_recipients_model->update_where(['om_id' => $ids['id'], 'recipient_id !=' => $this->user_id], ['trashed_by' => null]);
        }

    }

    private function _upload_files($message_id)
    {
        $files = $_FILES;
        $total = count($_FILES['userfile']['name']);

        for($i = 0; $i < $total; $i++){
            $_FILES['userfile']['name']     = $files['userfile']['name'][$i];
            $_FILES['userfile']['type']     = $files['userfile']['type'][$i];
            $_FILES['userfile']['tmp_name'] = $files['userfile']['tmp_name'][$i];
            $_FILES['userfile']['error']    = $files['userfile']['error'][$i];
            $_FILES['userfile']['size']     = $files['userfile']['size'][$i];

            $base_dir = user_basedir($this->user_id, $this->user_dir);
            $path = UPLOAD_PATH . $base_dir;

            $config['allowed_types'] = 'jpg|jpeg|png|gif|pdf|key|pptx|pps|ppsx|odt|xls|xlsx|zip|mp3|m4a|wav';
            $config['upload_path']   = $path;
            $config['max_size']      = 10000; //10 mb
            $config['file_name']     = generate_filename($this->user_id); //10 mb

            $this->upload->initialize($config);

            if (!$this->upload->do_upload('userfile')) {
                $error = array('error' => $this->upload->display_errors());
                $res['msg'] = 'Error: ' . implode('<br />', $error);

            } else {

                $upload_data = $this->upload->data();

                $this->uploads_model->save_upload($this->user_id, 'obs_message', $message_id, $base_dir, $upload_data['raw_name'],
                    $upload_data['client_name'], $upload_data['file_ext'], $upload_data['file_size'], 23);

            }
        }

        echo json_encode($res);
    }

    public function fetch_parent_messages()
    {
        $job_id                  = $this->input->post('job_id');
        $job_parent_id           = $this->input->post('job_parent_id');
        $module                  = $this->input->post('module');
        $editing_message         = $this->input->post('editing_message');
        $deleting_message        = $this->input->post('deleting_message');
        $attaching_files         = $this->input->post('attaching_files');
        $show_reply_box          = $this->input->post('show_reply_box');
        $show_reply_to_reply_box = $this->input->post('show_reply_to_reply_box');
        $default                 = $this->input->post('default');
        $sending_sms             = $this->input->post('sms');

        // check if editing is allowed in the module, data-edit="TRUE"
        if($editing_message == 'TRUE'){
            $edit_button = '<a href="#" class="edit-message button-expire" title="Edit Message"><i class="circular edit icon"></i></a>';
        } else {
            $edit_button = '<span class="hidden"><i class="circular icon"></i></span>';
        }

        // check if deleting is allowed in the module, data-delete="TRUE"
        $delete_button = ($deleting_message == 'TRUE') ? '<a href="#" class="delete-message button-expire" title="Delete Message"><i class="circular trash outline icon"></i></a>' : '';

        // check campaign_type
        $campaign_type = $this->client_postjob_model->find_single_data('type', ['id' => $job_parent_id]);

        $parent_message = $this->obs_message_model->fetch_parent_messages($module, $job_id, $job_parent_id);
        if($parent_message->num_rows() == 0){

            if($module == 'post_job'){
                $fetch_result = '<div class="zero-messages"><i class="comments icon"></i> Start the conversation by sending a Discussion or Topic below</div>';
            } elseif($module == 'campaign_discussion' || $module == 'wall') {
                $fetch_result = '';
            } else {

            }

        } else {
            $data_user     = ($this->usermodel->is_client($this->user_id) == TRUE) ? 'client': 'agent';
            $fetch_result  = '<ul data-user="'.$data_user.'">';
//            $fetch_result .= $this->user_dir;
//            $fetch_result .= '<div class="send_test"></div>';
//            $fetch_result .= '<br>id: ' . $job_id;
//            $fetch_result .= '<br>parent_id: ' . $job_parent_id;
//            $fetch_result .= '<br>module: ' . $module;
//            $fetch_result .= '<br>editing: ' . $editing_message;
//            $fetch_result .= '<br>deleting: ' . $deleting_message;
//            $fetch_result .= '<br>attach: ' . $attaching_files;
//            $fetch_result .= '<br>replybox: ' . $show_reply_box;
//            $fetch_result .= '<br>replytoreply: ' . $show_reply_to_reply_box;
//            $fetch_result .= '<br>default: ' . $default;
//            $fetch_result .= '<br>sms: ' . $sending_sms;
//            $fetch_result .= '<br>'.$parent_message->num_rows();
        }

        foreach ($parent_message->result() as $parent_info) {
            // fetch data from main_users table based on user_id
            $main_users = $this->usermodel->find_row(['user_id' => $parent_info->user_id]);

            $total_reply = count($this->obs_message_model->count_reply_messages($parent_info->id)->result());

            if ($this->data['user_id'] == $parent_info->user_id) {

                // set expiration time for edit button //
                // EDIT_MESSAGE_EXPIRE = 5 minutes
                $date_created = strtotime($parent_info->date_created);
                $edit_enabled = $date_created + (EDIT_MESSAGE_EXPIRE);
                $current_time = strtotime(date('Y-m-d H:i:s'));

                if ($edit_enabled > $current_time) {
                    $button  = $edit_button;
                    $button .= '<a href="#" class="save-edit hide" title="Save Edit"><i class="circular save icon"></i></a>';
                } else {
                    $button = '<a href="#" class="save-edit hidden" title="Save Edit"><i class="circular save icon"></i></a>';
                }

                $button .= '<a href="#" class="cancel-edit hide" title="Cancel Edit"><i class="circular remove icon"></i></a>';

                // set 1 hour expiration for delete button
                $date_created   = strtotime($parent_info->date_created);
                $delete_enabled = $date_created + (DELETE_MESSAGE_EXPIRE);
                $current_time   = strtotime(date('Y-m-d H:i:s'));
                $button .= ($delete_enabled > $current_time) ? $delete_button : '';

            } else {
                $button = '<a href="#" class="report-message"> <i class="circular warning sign icon"></i> </a>';
            }

            // check if message is sent from either client or agent
            if($main_users['account_type'] == 'C'){
                $user_type = 'client-user'; // set border-color to lightblue (#0ab6f0) for client user

                // profile link
                $profile_link  = '<a href="'.base_url().'cl/'.$this->_client_profile_link($parent_info->user_id).'" target="_blank" title="View Profile">';
                $profile_link .= $main_users['firstname'].' '.$main_users['lastname'][0];
                $profile_link .= '.</a>';

            } else {
                $user_type = 'agent-user'; // set border-color to lightgreen (#afd34e) for agent user

                // profile link
                $profile_link  = '<a href="'.base_url().'agent/'.$this->_agent_profile_link($parent_info->user_id).'" target="_blank" title="View Profile">';
                $profile_link .= $main_users['firstname'].' '.$main_users['lastname'][0];
                $profile_link .= '.</a>';
            }

            // set message color to orange for the first 5 seconds to show up
            $pop_up = (strtotime($parent_info->date_created) + 5 > strtotime(date('Y-m-d H:i:s'))) ? 'pop-up' : '';

            $topic_total_unread = $this->_count_unread_messages($parent_info->id);
            if ($topic_total_unread > 0) {
                $total = '<a class="toggle-reply chat-left"><i class="angle up icon"></i><span class="toggle-label">Hide all replies in this topic</span><span class="count_unread hide"> (' . $topic_total_unread . ' unread message)</span></a>';
            } else {
                $total = '<a class="toggle-reply chat-left"><i class="angle up icon"></i><span class="toggle-label">Hide all replies in this topic</span></a>';
            }

            // toggle show/hide button if reply messages is more than 0
            $toggle_reply = ($total_reply > 0) ? $total : '';

            // show edited label when message is updated
            $edited = ($parent_info->date_updated != NULL) ? '<span class="message_edited" title="'.date('h:i:sa, M d Y', strtotime($parent_info->date_updated)).'"><i class="write icon"></i>Edited  </span>' : '';

            // fetch filename of attached file
            $attach_files = $this->uploads_model->find_all_details(['ref_table_id' => $parent_info->id]);

            // check if user have avatar/picture
            $photo_details = profile_picture($parent_info->user_id);
            $avatar_url    = $photo_details['photo'];

            $fetch_result .= '<li data-id="'.$parent_info->id.'" class="'.$user_type.' clear group li-group '.$pop_up.' parent">';
            $fetch_result .= '<div data-id="'.$parent_info->id.'" class="ajax-loader editing-message-loader hide">';
            $fetch_result .= '<img src="'.base_url().'images/preloader/obloader-40.gif" alt="ajax-loader">';
            $fetch_result .= '<span style="width: 42px;"></span>';
            $fetch_result .= '</div>';
            $fetch_result .= '<div class="under-li">';
            $fetch_result .= '<div class="message-options chat-bubble">';
            $fetch_result .= '<pre class="message">' . $parent_info->message . '</pre>';
            $fetch_result .= '<div class="buttons">';
            $fetch_result .= $button;
            $fetch_result .= '</div>';

            // fetch attached files
            $fetch_result .= '<div class="attached-files">';
            foreach ($attach_files as $file){

                if(in_array($file['file_ext'], ['.jpg','.jpeg','.png','.gif'])){
                    $path = $attach_files['img_url'];
                    $fetch_result .= '<span class="file view-image" data-path="'.$path.'"><a href="#"><i class="attachment icon"></i>' . shorten_string($file['orig_filename'], 15) . '</a>  </span>';
                } else {
                    $path = $attach_files['download_url'];
                    $fetch_result .= '<span class="file"><a href="'.$path.'" download><i class="attachment icon"></i>' . shorten_string($file['orig_filename'], 15) . '</a>  </span>';
                }
            }
            $fetch_result .= '</div>';

            $fetch_result .= '</div>';
            $fetch_result .= '<div class="user-info">';
            $fetch_result .= '<p class="picture">';

            if($campaign_type == 'E'){
                $fetch_result .= avatar_initial($main_users['firstname'][0].$main_users['lastname'][0], 52, 24, $parent_info->user_id);
            } else {
                $fetch_result .= '<img src="'.$avatar_url.'" width="42" alt="Profile Image" title="'.$main_users['firstname'].' '.$main_users['lastname'][0].'">';
            }

            $fetch_result .= '<p class="name">';
            $fetch_result .= $profile_link;
            $fetch_result .= '</p>';
            $fetch_result .= '</div>';
            $fetch_result .= '</div>';
            $fetch_result .= '<div class="date">';
            $fetch_result .= '<p class="message-count chat-right hide"><span>500</span> characters remaining</p>';
            //$fetch_result .= $filename;
            $fetch_result .= $toggle_reply;
            $fetch_result .= '<span class="date-created chat-right" title="'.date('h:ia, M d Y', strtotime($parent_info->date_created)).'">'. $edited . ' ' .$this->_time_passed(strtotime($parent_info->date_created)).'</span>';
            $fetch_result .= '</div>';
            $fetch_result .= '</li>';

            // fetch and generate all the reply messages based on parent_id parameter
            $fetch_result .= $this->_fetch_reply_messages($parent_info->id, $parent_info->id, '', $job_parent_id, $editing_message, $deleting_message, $show_reply_to_reply_box);

            // reply box for one topic inserted inside div tag
            if($show_reply_box == 'TRUE') {
                $fetch_result .= '<hr class="clear hidden">';
                $fetch_result .= '<div data-topic-id="'.$parent_info->id.'" data-id="'.$parent_info->id . '" data-parent-id="' . $parent_info->id . '" class="topic-reply-box group div-group topic-send-button">';
                $fetch_result .= '<div data-id="' . $parent_info->id . '" class="ajax-loader sending-message-loader hide">';
                $fetch_result .= '<img src="'.base_url().'images/preloader/obloader-40.gif" alt="ajax-loader">';
                $fetch_result .= '</div>';
                $fetch_result .= '<button class="topic-reply-button top" title="Send Message Now"><img src="'.base_url().'images/web_chat/send_icon.png" alt="send-button"></button>';
                $fetch_result .= '<textarea maxlength="500" data-id="' . $parent_info->id . '" class="topic-message" name="topic-message" placeholder="Type your message to this discussion"></textarea>';
                $fetch_result .= '</div>';
                $fetch_result .= '<li data-id="' . $parent_info->id . '" class="li-group"><div class="message-count chat-right hidden"><span>500</span> characters remaining</div></li>';
            }
        }
        $fetch_result .= '</ul>';

        echo $fetch_result;
    }

    private function _fetch_reply_messages($parent, $id, $fetch_result, $job_parent_id, $editing_message, $deleting_message, $show_reply_to_reply_box)
    {
        $reply_messages = $this->obs_message_model->fetch_reply_messages($id, $job_parent_id);

        // check if editing is allowed in the module, data-edit="TRUE"
        if($editing_message == 'TRUE'){
            $edit_button = '<a href="#" class="edit-message button-expire" title="Edit Message"><i class="circular edit icon"></i></a>';
        } else {
            $edit_button = '<span class="hidden"><i class="circular icon"></i></span>';
        }

        // check if deleting is allowed in the module, data-delete="TRUE"
        $delete_button = ($deleting_message == 'TRUE') ? '<a href="#" class="delete-message button-expire" title="Delete Message"><i class="circular trash outline icon"></i></a>' : '';

        // check campaign_type
        $campaign_type = $this->client_postjob_model->find_single_data('type', ['id' => $job_parent_id]);

        // check if reply to reply is allowed
        if($show_reply_to_reply_box == 'TRUE'){
            $reply_button_one = '<a href="#" class="reply-button-one send-reply"><i class="circular reply mail icon" title="Reply to this message"></i></a>';
        }

        $hidden_button = '<a href="#" class="hidden"><i class="circular reply mail icon" title=""></i></a>';

        if($reply_messages->num_rows() > 0) {
            foreach ($reply_messages->result() as $reply_info) {
                // fetch data from main_users table based on user_id
                $main_users = $this->usermodel->find_row(['user_id' => $reply_info->user_id]);

                if ($this->data['user_id'] == $reply_info->user_id) {

                    // set expiration time for edit button // EDIT_MESSAGE_EXPIRE = 5 minutes
                    $date_created = strtotime($reply_info->date_created);
                    $edit_enabled = $date_created + (EDIT_MESSAGE_EXPIRE);
                    $current_time = strtotime(date('Y-m-d h:i:s'));

                    if ($edit_enabled > $current_time) {
                        $button  = $edit_button;
                        $button .= $reply_button_one;
                    } else {
                        $button = $reply_button_one;
                    }

                    $button .= '<a href="#" class="save-edit hide" title="Save Edit"><i class="circular save icon"></i></a>';
                    $button .= '<a href="#" class="cancel-edit hide" title="Cancel Edit"><i class="circular remove icon"></i></a>';
                    $button .= '<a href="#" class="cancel-reply-own hide" title="Cancel reply"><i class="circular remove icon"></i>cancel</a>';

                    // set 1 hour expiration for delete button
                    $date_created = strtotime($reply_info->date_created);
                    $delete_enabled = $date_created + (DELETE_MESSAGE_EXPIRE);
                    $current_time = strtotime(date('Y-m-d H:i:s'));
                    $button .= ($delete_enabled > $current_time) ? $delete_button : '';

                } else {
                    $button  = $reply_button_one;
                    $button .= '<a href="#" class="cancel-reply-own hide" title="Cancel Reply"><i class="circular remove icon"></i>cancel</a>';
                    $button .= '<a href="#" class="report-message"><i class="circular warning sign icon"></i></a>';
                }

                // check if message is sent from either client or agent
                if($main_users['account_type'] == 'C'){
                    $user_type = 'client-user'; // set border-color to lightblue (#0ab6f0) for client user

                    // profile link
                    $profile_link  = '<a href="'.base_url().'cl/'.$this->_client_profile_link($reply_info->user_id).'" target="_blank" title="View Profile">';
                    $profile_link .= $main_users['firstname'].' '.$main_users['lastname'][0];
                    $profile_link .= '.</a>';

                } else {
                    $user_type = 'agent-user'; // set border-color to lightgreen (#afd34e) for agent user

                    // profile link
                    $profile_link  = '<a href="'.base_url().'agent/'.$this->_agent_profile_link($reply_info->user_id).'" target="_blank" title="View Profile">';
                    $profile_link .= $main_users['firstname'].' '.$main_users['lastname'][0];
                    $profile_link .= '.</a>';
                }

                // set message color to orange for the first 5 seconds to show up
                $pop_up = (strtotime($reply_info->date_created) + 5 > strtotime(date('Y-m-d H:i:s'))) ? 'pop-up' : '';

                // show edited label when message is updated
                $edited = ($reply_info->date_updated != NULL) ? '<span class="message_edited" title="'.date('h:i:sa, M d Y', strtotime($reply_info->date_updated)).'"><i class="write icon"></i>Edited  </span>' : '';

                $photo_details = profile_picture($reply_info->user_id);
                $avatar_url    = $photo_details['photo'];

                // fetch filename of attached file
                $attach_files = $this->uploads_model->find_all_details(['ref_table_id' => $reply_info->id]);

                $fetch_result .= '<li data-topic-id="'.$parent.'" data-id="'.$reply_info->id.'" data-parent-id="'.$reply_info->parent_id.'" class="message-reply-'.$reply_info->id.' '.$user_type.' group li-group clear '.$pop_up.'">';
                $fetch_result .= '<div data-id="'.$reply_info->id.'" class="ajax-loader editing-message-loader hide">';
                $fetch_result .= '<img src="'.base_url().'images/preloader/obloader-40.gif" alt="ajax-loader">';
                $fetch_result .= '</div>';
                $fetch_result .= '<div class="under-li">';
                $fetch_result .= '<div class="message-options chat-bubble">';
                $fetch_result .= '<pre class="message">' . $reply_info->message . '</pre>';
                $fetch_result .= '<div class="buttons">';
                $fetch_result .= $hidden_button;
                $fetch_result .= $button;
                $fetch_result .= '</div>';

                // fetch attached files
                $fetch_result .= '<div class="attached-files">';
                foreach ($attach_files as $file){

                    if(in_array($file['file_ext'], ['.jpg','.jpeg','.png','.gif'])){

                        $path = $file['img_url'];
                        $fetch_result .= '<span class="file view-image" data-path="'.$path.'"><a href="#"><i class="attachment icon"></i>' . shorten_string($file['orig_filename'], 15) . '</a>  </span>';
                    } else {
                        $path = $file['download_url'];
                        $fetch_result .= '<span class="file"><a href="'.$path.'" download><i class="attachment icon"></i>' . shorten_string($file['orig_filename'], 15) . '</a>  </span>';
                    }
                }
                $fetch_result .= '</div>';

                $fetch_result .= '</div>';
                $fetch_result .= '<div class="user-info">';
                $fetch_result .= '<p class="picture">';

                if($campaign_type == 'E'){
                    $fetch_result .= avatar_initial($main_users['firstname'][0].$main_users['lastname'][0], 52, 24, $reply_info->user_id);
                } else {
                    $fetch_result .= '<img src="'.$avatar_url.'" width="42" alt="Profile Image" title="'.$main_users['firstname'].' '.$main_users['lastname'][0].'">';
                }

                $fetch_result .= '<p class="name">';
                $fetch_result .= $profile_link;
                $fetch_result .= '</p>';
                $fetch_result .= '</div>';
                $fetch_result .= '</div>';
                $fetch_result .= '<div class="date">';
                $fetch_result .= '<p class="message-count chat-right hide"><span>500</span> characters remaining</p>';
                $fetch_result .= '<span class="date-created chat-right" title="'.date('h:ia, M d Y', strtotime($reply_info->date_created)).'">'. $edited . ' ' . $this->_time_passed(strtotime($reply_info->date_created)) . '</span>';
                $fetch_result .= '</div>';
                $fetch_result .= '</li>';

                // reply box for one message inserted inside div tag
                $fetch_result .= '<hr class="clear hidden">';
                $fetch_result .= '<div data-id="'.$reply_info->id.'" data-parent-id="'.$reply_info->id.'" class="reply-box-'.$reply_info->id.' message-reply-'.$reply_info->id.' group div-group" hidden>';
                $fetch_result .= '<button class="send-reply-one top" title="Send Message Now"><img src="'.base_url().'images/web_chat/send_icon.png" alt="send-button"></button>';
                $fetch_result .= '<textarea maxlength="500" class="reply-message-one" name="reply-message-one" placeholder="Type your reply to this message"></textarea>';
                $fetch_result .= '</div>';
                $fetch_result .= '<li data-id="'.$reply_info->id.'" data-parent-id="'.$reply_info->id.'" class="li-group"><div class="message-count chat-right hide"><span>500</span> characters remaining</div></li>';

                // fetch and generate all the reply messages based on parent_id all over again
                $fetch_result .= $this->_fetch_reply_messages($parent, $reply_info->id, '', $job_parent_id, $editing_message, $deleting_message, $show_reply_to_reply_box);
            }
            return $fetch_result;
        }
    }

    private function _time_passed($timestamp)
    {
        //type cast, current time, difference in timestamps
        $timestamp    = (int)$timestamp;
        $current_time = time();
        $diff         = $current_time - $timestamp;

        if ($diff < 0) {
            $diff = 0;
        }

        //intervals in seconds
        $intervals = [
            'year'   => 31556926,
            'month'  => 2629744,
            'week'   => 604800,
            'day'    => 86400,
            'hour'   => 3600,
            'minute' => 60
        ];

        //now we just find the difference
        if ($diff == 0) {
            return 'just now';
        }

        if ($diff < 60) {
            return $diff == 1 ? $diff . ' second ago' : $diff . ' seconds ago';
        }

        if ($diff >= 60 && $diff < $intervals['hour']) {
            $diff = floor($diff / $intervals['minute']);

            return $diff == 1 ? $diff . ' minute ago' : $diff . ' minutes ago';
        }

        if ($diff >= $intervals['hour'] && $diff < $intervals['day']) {
            $diff = floor($diff / $intervals['hour']);

            return $diff == 1 ? $diff . ' hour ago' : $diff . ' hours ago';
        }

        if ($diff >= $intervals['day'] && $diff < $intervals['week']) {
            $diff = floor($diff / $intervals['day']);

            return $diff == 1 ? $diff . ' day ago' : $diff . ' days ago';
        }

        if ($diff >= $intervals['week'] && $diff < $intervals['month']) {
            $diff = floor($diff / $intervals['week']);

            return $diff == 1 ? $diff . ' week ago' : $diff . ' weeks ago';
        }

        if ($diff >= $intervals['month'] && $diff < $intervals['year']) {
            $diff = floor($diff / $intervals['month']);

            return $diff == 1 ? $diff . ' month ago' : $diff . ' months ago';
        }

        if ($diff >= $intervals['year']) {
            $diff = floor($diff / $intervals['year']);

            return $diff == 1 ? $diff . ' year ago' : $diff . ' years ago';
        }
        return true;
    }

    public function update_subject()
    {
        if ($this->input->post('message') != '') {
            $subject_id = $this->input->post('id');
            $subject    = $this->input->post('message');

            $this->obs_message_model->update($subject_id, ['subject' => $subject, 'date_updated' => date('Y-m-d H:i:s')]);
        }
    }

    public function update_message()
    {
        if ($this->input->post('message') != '') {
            $message_id = $this->input->post('id');
            $message    = $this->input->post('message');

            $this->obs_message_model->update($message_id, ['message' => $message, 'date_updated' => date('Y-m-d H:i:s')]);
        }
    }

    public function report_message()
    {
        // fetch the id/sender of the message
        $message_id     = $this->input->post('message_id');
        $table_id       = $this->input->post('table_id');
        $report_message = $this->input->post('notes');
        $chat_user_id   = $this->obs_message_model->find_id($message_id);

        $data_reports = [
            'user_id'       => $chat_user_id['user_id'],
            'added_by_id'   => $this->user_id,
            'table_id'      => $table_id,
            'notes'         => $report_message,
            'type'          => 2
        ];
        $this->user_reports_model->insert($data_reports);
    }

    private function _read_date($id)
    {
        $reply_id = $this->obs_message_model->fetch_reply_id($id);

        foreach ($reply_id->result() as $data){
            $this->obs_message_recipients_model->read_messages($data->id, $this->user_id);
            $this->_read_date($data->id);
        }
    }

    public function read_date()
    {
        $id = $this->input->post('om_id');

        // read parent message
        $this->obs_message_recipients_model->update_where(['om_id' => $id, 'recipient_id' => $this->user_id], ['read_date' => date('Y-m-d H:i:s')]);

        // read reply messages
        $reply_id = $this->obs_message_model->fetch_reply_id($id);
        foreach ($reply_id->result() as $data){
            $this->obs_message_recipients_model->read_messages($data->id, $this->user_id);
            $this->_read_date($data->id);
        }

    }

    private function _count_unread_reply_messages($id)
    {
        $reply_count = 0;

        $reply_id = $this->obs_message_model->fetch_reply_id($id);
        foreach($reply_id->result() as $data){
            $unread = $this->obs_message_recipients_model->topic_unread_messages($data->id, $this->user_id);
            $reply_count += count($unread->result());
            $reply_count += $this->_count_unread_reply_messages($data->id);
        }
        return $reply_count;

    }

    private function _count_unread_messages($parent_id)
    {
        $count    = 0;
        $reply_id = $this->obs_message_model->fetch_reply_id($parent_id);
        foreach ($reply_id->result() as $data){
            $unread = $this->obs_message_recipients_model->topic_unread_messages($data->id, $this->user_id);
            $count += count($unread->result());
            $count += $this->_count_unread_reply_messages($data->id);
        }
        return $count;
    }

    public function count_unread_messages_json()
    {
        $unread = $this->obs_message_recipients_model->count_unread_messages($this->user_id);
        $count_result = count($unread->result());

        echo $count_result;
    }

    private function _delete_reply_messages()
    {
        // delete reply messages once parent message is deleted / obs_message table
        $deleted_messages = $this->obs_message_model->deleted_messages();
        if(count($deleted_messages->result()) > 0) {
            foreach($deleted_messages->result() as $info) {
                $this->obs_message_model->update_where(['parent_id' => $info->id], ['date_deleted' => date('Y-m-d H:i:s'), 'status' => 0]);
                $this->obs_message_recipients_model->update_where(['om_id' => $info->id, 'read_date' => NULL], ['read_date' => date('Y-m-d H:i:s')]);
            }
            $this->_delete_reply_messages();
        }
    }

    public function delete_message()
    {
        $id = $this->input->post('id');
        $this->obs_message_model->update($id, ['date_deleted' => date('Y-m-d H:i:s'), 'status' => 0]);

        // set reply messages to read (current datetime) even though it's unread if parent messages is deleted / obs_message_recipients table
        $this->obs_message_recipients_model->update_where(['om_id' => $id, 'read_date' => NULL], ['read_date' => date('Y-m-d H:i:s')]);
        $reply_id = $this->obs_message_model->fetch_reply_id($id);
        if(count($reply_id->result()) > 0){
            foreach ($reply_id->result() as $data){
                $this->obs_message_recipients_model->update_where(['om_id' => $data->id, 'read_date' => NULL], ['read_date' => date('Y-m-d H:i:s')]);
            }

            // delete reply messages once parent message is deleted / obs_message table
            $this->_delete_reply_messages();
        }
    }

    private function _agent_profile_link($id)
    {
        return $this->agent_info_model->find_single_data('profile_link', ['agent_id' => $id]);
    }

    private function _client_profile_link($id)
    {
        return $this->client_info_model->find_single_data('profile_link', ['uid' => $id]);
    }

    public function fetch_email_recipients()
    {
        $job_id         = $this->input->post('job_id');
        $job_parent_id  = $this->input->post('job_parent_id');
        $module         = $this->input->post('module');
        $default        = $this->input->post('default');

        //data for obs_message_recipients table
        $condition = ($module == 'campaign_discussion' || $module == 'wall') && $default == 'TRUE';

        if($condition){
            $recipients = $this->obs_message_model->fetch_campaign_agents($job_parent_id, $this->user_id);
        } else {
            if($module == 'messages') {
                $recipients = $this->obs_message_recipients_model->message_recipients($job_id);
            } elseif($module == 'interviews') {
                if($this->usermodel->is_client($this->user_id) == TRUE){
                    $recipients = $this->obs_message_recipients_model->interviewee($job_parent_id);
                } else {
                    $recipients = $this->obs_message_model->interviewer($job_parent_id);
                }
            } else {
                $recipients = $this->obs_message_model->fetch_all_recipients($job_id, $job_parent_id, $this->user_id);
            }
        }

        $html = '';
        $count = 0;
        foreach ($recipients->result() as $recipient) {

            // fetch data from main_users table based on user_id
            if($condition) {
                $user_id = $recipient->agent_id;
            } elseif($module == 'messages' || $module == 'interviews') {
                $user_id = $recipient->recipient_id;
            } else {
                $user_id = $recipient->user_id;
            }

            $main_users = $this->usermodel->find_row(['user_id' => $user_id]);

            if($this->user_id == $user_id) continue;

            $html .= '<div class="ui checked checkbox clear">';
            $html .= '<input type="checkbox" name="recipients" id="check-'.$count.'" value="'.$user_id.'">';
            $html .= '<label for="check-'.$count.'">'.$main_users['firstname'].' '.$main_users['lastname'].'</label>';
            $html .= '</div>';

            $count++;
        }

        echo $html;
    }

    private function _messages_email($title, $message, $recipient, $encrypted_id, $type, $total_files, $style)
    {
        if ($this->usermodel->is_client($this->user_id) == TRUE) {
            $profile_image  = $this->client_info_model->find_single_data('filename_photo', ['uid' => $this->user_id]);
            $sender         = $this->usermodel->client_info($this->user_id);
            $receiver       = $this->usermodel->client_info($recipient);

        } else {
            $profile_image  = $this->agent_info_model->find_single_data('filename_photo', ['agent_id' => $this->user_id]);
            $sender         = $this->usermodel->agent_info($this->user_id);
            $receiver       = $this->usermodel->agent_info($recipient);
        }

        $photo       = get_profile_image($profile_image['filename_photo']);
        $sender_name = $sender['firstname'] . ' ' . $sender['lastname'];
        $to_email    = $receiver['email_address'];

        if($type == 2) {
            $subject = "{$sender_name} sent you a message: $title";
        } else {
            $subject = "{$sender_name} sent you a group message: $title";
        }

        $email_message  = '<table cellpadding="5" cellspacing="5">';
        $email_message .= '<tr>';
        $email_message .= '<td valign="top" width="60px">'.$photo.'</td>';
        $email_message .= '<td valign="top" ><span style="font-size:14px;color:#777">'.$sender_name.' sent you a message</span>';
        $email_message .= '<br><span style="font-size:18px;color:#444">'.$title.'</span>';
        $email_message .= '<br><span style="font-size:14px;color:#777">'.$message.'</span><br>';
        if($total_files > 0){
            $email_message .= "<a href='".base_url()."messages/view/" . $encrypted_id . "' {$style}>Login to your account to view {$total_files} attached file.</a>";
        }
        $email_message .= '</td>';
        $email_message .= '</tr>';
        $email_message .= '</table>';

        $email_message .= '<br><br>';
        $email_message .= '<div style="border-top: 1px solid #ccc; color:#999">';
        $email_message .= '<br><br>';
        $email_message .= 'You can now <span style="color:#f14a4a">reply easily</span>, simply just by replying to this message using this email!';
        $email_message .= '<br><strong>important:</strong> this feature is only available when indicated';
        $email_message .= '<br>click here to view message in outbounders.com';
        $email_message .= "<a href='".base_url()."messages/view/" . $encrypted_id . "' {$style}> click here to view in outbounders.com</a>";
        $email_message .= '</div>';

        $reply_to       = 'pm-' . $encrypted_id . '@outbounders.com';
        $email_from     = $reply_to;

        $this->obmail->send_email($to_email, $subject, $email_message, $email_from);

        return true;
    }

    public function send_email()
    {
        $job_id         = $this->input->post('job_id');
        $job_parent_id  = $this->input->post('job_parent_id');
        $module         = $this->input->post('module');
        $default        = $this->input->post('default');
        $email_option   = $this->input->post('email_option');
        $condition      = ($module == 'campaign_discussion' || $module == 'wall') && $default == 'TRUE';
        $style          = "style='color:#0000ff; font-size:12px;'";

        //data for obs_message_recipients table
        if($condition) {
            $recipients = $this->obs_message_model->fetch_campaign_agents($job_parent_id, $this->user_id);
        } elseif($module == 'messages') {
            $recipients = $this->obs_message_recipients_model->message_recipients($job_id);
        } elseif($module == 'interviews') {
            if($this->usermodel->is_client($this->user_id) == TRUE){
                $recipients = $this->obs_message_recipients_model->interviewee($job_parent_id);
            } else {
                $recipients = $this->obs_message_model->interviewer($job_parent_id);
            }
        } else {
            $recipients = $this->obs_message_model->fetch_all_recipients($job_id, $job_parent_id, $this->user_id);
        }

        $campaign_name  = $this->input->post('campaign_name');
        $message        = $this->input->post('message');
        $total_files    = $this->input->post('total_files');
        $campaign_type  = $this->client_postjob_model->find_single_data('type', ['id' => $job_parent_id]);
        $excs_id        = $this->client_postjob_model->find_single_data('excs_id', ['id' => $job_parent_id]);
        $title          = $this->client_job_posting_model->find_single_data('title', ['id' => $job_parent_id]);

        if($email_option == 1) {

            foreach ($recipients->result() as $recipient) {
                if($condition) {
                    $user_id = $recipient->agent_id;
                } elseif($module == 'interviews') {
                    $user_id = $recipient->recipient_id;
                } else {
                    $user_id = $recipient->user_id;
                }

                $main_users     = $this->usermodel->find_row(['user_id' => $user_id]);
                $email_address  = $this->usermodel->find_single_data('email_address', ['user_id' => $user_id]);
                $encrypted_id   = encrypt_primary_id($job_id);

                if($module == 'post_job') {
                    $email_title = $this->data['user_data']['firstname'] . ' ' . ' added a new message in Job Posting';
                    $message_title = "{$this->data['user_data']['firstname']} added a new message on <strong>" . $title . "</strong> Job Posting page.";

                    $message_attach = "<a href='" . base_url() . "post_job/view/" . $encrypted_id . "' {$style}>Click here to view {$total_files} attached file.</a>";

                    $footer_links = "<br>Click here to view in <a href='" . base_url() . "post_job/view/" . $encrypted_id . "'>outbounders.com</a>";

                } elseif($module == 'messages') {
                    if ($this->user_id == $user_id) continue;

                    $type   = $this->obs_message_model->find_single_data('type', ['id' => $job_id]);
                    $title  = $this->obs_message_model->find_single_data('subject', ['id' => $job_id]);

                    // sending email
                    $this->_messages_email($title, $message, $user_id, $encrypted_id, $type, $total_files, $style);
                    exit();

                } elseif ($module == 'interviews') {
                    $email_title = $this->data['user_data']['firstname'] . ' ' . ' added a new message in Interview Schedule';
                    $message_title = "{$this->data['user_data']['firstname']} added a new message on <strong>" . $title . "</strong> Interview Schedule.";

                    $message_attach = "<a href='".base_url()."interviews/display_interview?interview_id=" . $job_id . "' {$style}>Login to your account to view {$total_files} attached file.</a>";

                    $footer_links  = "<br>Click here to view in <a href='".base_url()."interviews/display_interview?interview_id=" . $job_id . "'>outbounders.com</a>";
                } else {

                    if($campaign_type == 'E' && $main_users['account_type'] == 'C') {

                        $email_title    = $this->data['user_data']['firstname'].' '.' added a new message in the Wall';
                        $message_title  = "{$this->data['user_data']['firstname']} added a new message on <strong>" . $campaign_name . "</strong> Wall page.";

                        $message_attach = "<a href='".WEB_CHAT_EXPRESS_URL."wall/view/".$this->web_chat_encrypt_id('wall', $job_id)."/?excs_id=".$this->web_chat_encrypt_id('wall', $excs_id)."' {$style}>Click here to view {$total_files} attached file.</a>";

                        $footer_links   = "<br>Click here to view in <a href='".WEB_CHAT_EXPRESS_URL."wall/view/".$this->web_chat_encrypt_id('wall', $job_id)."/?excs_id=".$this->web_chat_encrypt_id('wall', $excs_id)."'>express.outbounders.com</a>";
                        $footer_links  .= "<a href='".WEB_CHAT_EXPRESS_URL."wall/lists/?excs_id=".$this->web_chat_encrypt_id('wall', $excs_id)."' {$style}>Click here to view in express.outbounders.com </a>";
                    } else {

                        $email_title    = $this->data['user_data']['firstname'].' '.' added a new message in Campaign Discussion';
                        $message_title  = "{$this->data['user_data']['firstname']} added a new message on <strong>" . $campaign_name . "</strong> Campaign Discussion page.";

                        $message_attach = "<a href='".base_url()."campaign_discussion/view/".$encrypted_id."' {$style}>Click here to view {$total_files} attached file.</a>";

                        $footer_links   = "<br>Click here to view in <a href='".base_url()."campaign_discussion/view/".$encrypted_id."'>outbounders.com</a>";
                        $footer_links  .= "<a href='".base_url()."campaign_discussion' {$style}> Click here to view all your campaign messages</a>";
                    }
                }

                $email_message  = "Hi {$main_users['firstname']},";
                $email_message .= '<br>';
                $email_message .= '<br>';
                $email_message .= $message_title;

                $email_message .= '<br>';

                if($module == 'campaign_discussion' || $module == 'wall'){
                    $email_message .= '<br><strong>' . $this->input->post('topic_title') . '</strong>';
                }

                $email_message .= '<br>';
                $email_message .= $message;
                $email_message .= '<br>';

                if($total_files != 0) {
                    $email_message .= $message_attach;
                }

                $email_message .= '<br>';
                $email_message .= "<div style='border-top: 1px solid #ccc; color:#999'>";
                $email_message .= $footer_links;
                $email_message .= '</div>';

                @$this->obmail->send_email($email_address, $email_title, $email_message);
            }

        } else if($email_option == 2) {
            $selected = $this->input->post('recipients');
            $selected = explode(',', $selected);

            foreach($selected as $key => $value) {

                // fetch data from main_users table based on user_id
                $main_users     = $this->usermodel->find_row(['user_id' => $value]);
                $email_address  = $this->usermodel->find_single_data('email_address', ['user_id' => $value]);
                $encrypted_id   = encrypt_primary_id($job_id);

                if($module == 'post_job'){
                    $email_title    = $this->data['user_data']['firstname'].' '.' added a new message in Job Posting';
                    $message_title  = "{$this->data['user_data']['firstname']} added a new message on <strong>" . $title . "</strong> Job Posting page.";

                    $message_attach = "<p style='color:#0000ff; font-size:12px'>Login to your account to view {$total_files} attached file.</p>";

                    $footer_links = "<a href='".base_url()."post_job/view/".$encrypted_id."' style='color:#0000ff;'>click here to view in outbounders.com</a>";

                } elseif($module == 'messages'){
                    if ($this->user_id == $value) continue;

                    $type   = $this->obs_message_model->find_single_data('type', ['id' => $job_id]);
                    $title  = $this->obs_message_model->find_single_data('subject', ['id' => $job_id]);

                    // sending email
                    $this->_messages_email($title, $message, $value, $encrypted_id, $type, $total_files, $style);
                    exit();

                } elseif ($module == 'interviews') {
                    $email_title = $this->data['user_data']['firstname'] . ' ' . ' added a new message in Interview Schedule';
                    $message_title = "{$this->data['user_data']['firstname']} added a new message on <strong>" . $title . "</strong> Interview Schedule.";

                    $message_attach = "<a href='".base_url()."interviews/display_interview?interview_id=" . $job_id . "' {$style}>Login to your account to view {$total_files} attached file.</a>";

                    $footer_links  = "<br>Click here to view in <a href='".base_url()."interviews/display_interview?interview_id=" . $job_id . "'>outbounders.com</a>";
                } else {

                    if($campaign_type == 'E' && $main_users['account_type'] == 'C') {

                        $email_title    = $this->data['user_data']['firstname'].' '.' added a new message in the Wall';
                        $message_title  = "{$this->data['user_data']['firstname']} added a new message on <strong>" . $campaign_name . "</strong> Wall page.";

                        $message_attach = "<a href='".WEB_CHAT_EXPRESS_URL."wall/view/".$this->web_chat_encrypt_id('wall', $job_id)."/?excs_id=".$this->web_chat_encrypt_id('wall', $excs_id)."' {$style}>Click here to view {$total_files} attached file.</a>";

                        $footer_links   = "<br>Click here to view in <a href='".WEB_CHAT_EXPRESS_URL."wall/view/".$this->web_chat_encrypt_id('wall', $job_id)."/?excs_id=".$this->web_chat_encrypt_id('wall', $excs_id)."'>express.outbounders.com</a>";
                        $footer_links  .= "<a href='".WEB_CHAT_EXPRESS_URL."wall/lists/?excs_id=".$this->web_chat_encrypt_id('wall', $excs_id)."' {$style}>Click here to view in express.outbounders.com </a>";
                    } else {

                        $email_title    = $this->data['user_data']['firstname'].' '.' added a new message in Campaign Discussion';
                        $message_title  = "{$this->data['user_data']['firstname']} added a new message on <strong>" . $campaign_name . "</strong> Campaign Discussion page.";

                        $message_attach = "<a href='".base_url()."campaign_discussion/view/".$encrypted_id."' {$style}>Click here to view {$total_files} attached file.</a>";

                        $footer_links   = "<br>Click here to view in <a href='".base_url()."campaign_discussion/view/".$encrypted_id."'>outbounders.com</a>";
                        $footer_links  .= "<a href='".base_url()."campaign_discussion' {$style}> Click here to view all your campaign messages</a>";
                    }
                }

                $email_message  = "Hi {$main_users['firstname']},";
                $email_message .= '<br>';
                $email_message .= '<br>';
                $email_message .= $message_title;

                $email_message .= '<br>';

                if($module == 'campaign_discussion' || $module == 'wall'){
                    $email_message .= '<br><strong>' . $this->input->post('topic_title') . '</strong>';
                }

                $email_message .= '<br>';
                $email_message .= $message;
                $email_message .= '<br>';

                if($total_files != 0) {
                    $email_message .= $message_attach;
                }

                $email_message .= '<br>';
                $email_message .= "<div style='border-top: 1px solid #ccc; color:#999'>";
                $email_message .= $footer_links;
                $email_message .= '</div>';

                @$this->obmail->send_email($email_address, $email_title, $email_message);
            }

        } else {

        }
    }

    // this function is based only encrypt_primary_id from text_helper.php
    // this will be updated only if encrypt_primary_id from text_helper.php is updated also
    public function web_chat_encrypt_id($module, $id, $salt_start = SALT_START, $salt_end = SALT_END)
    {
        if($module == 'wall'){
            // the ff. values of SALTS are from express/constants.php
            // the ff. values of SALTS should always be the same from express/constants.php
            $salt_start = '3x/Pre5S0';
            $salt_end   = 'oB4dW!in';
        }

        return md5($salt_start . $id . $salt_end);
    }

    public function table_migrator($module, $table)
    {
        $this->data['module'] = $module;
        $this->data['table']  = $table;

        $this->load->view('table_migrator', $this->data);
    }

    public function test(){

        $this->template->set_layout($this->layout)->build('test', $this->data);
    }
}



?>