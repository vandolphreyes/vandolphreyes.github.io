<html>
<head>
    <style>
        body, a {
            color: #333333;
            font-family: 'Lucida Sans Unicode', 'Lucida Grande', sans-serif;
        }

        a {
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .note {
            border-left: 5px solid #ff3939;
        }

        .row {
            padding: 10px;
            background: #e5e1e6;
        }

        .purple {
            border-left: 5px solid #988CC3;
        }

        .orange {
            border-left: 5px solid #F5876E;
        }

        .green {
            border-left: 5px solid #8EBD40;
        }

        .gold {
            border-left: 5px solid goldenrod;
        }

        .black {
            border-left: 5px solid black;
        }

        section {
            margin: 50px 0;
        }

        ul {
            padding: 0;
        }

        li {
            list-style-type: none;
        }

        ul li.level-2 {
            margin: 10px 30px;
        }

        .imported {
            text-decoration: line-through;
        }
    </style>
</head>

<body>
<header>
    <p class="row note">If possible, we could put the system into maintenance mode to avoid conflicts during importing.</p>
    <p class="row note">Possible problem, users could insert new data into a table which will be imported.</p>
</header>

<section>

    <?

    $messages = $this->db->query('SELECT * FROM main_messaging')->result();

    if($module == null): ?>

        <p class="row gold">MODULES</p>
        <p class="row purple imported"><b><a href="#">job_discussion module</a></b></p>
        <p class="row orange imported"><b><a href="#">campaign_discussion module</a></b></p>
        <p class="row green"><b><a href="<?= current_url() . '/messages'; ?>">messages module</a></b></p>

    <? elseif($module == 'messages' && $table == null): ?>

        <ul>
            <p class="row gold"><b><a href="<?= base_url() . 'web_chat/table_migrator/'; ?>">BACK</a></b></p>
            <li class="row green"><b>main_messaging table</b></li>
            <li class="row green level-2"><b>Total number of rows: <?= count($messages); ?></b></li>
            <li class="row green level-2"><a href="<?= current_url() . '/messages'; ?>"><b>CLICK HERE to migrate main_messaging table</b></a></li>
        </ul>

    <? elseif($module == 'messages' && $module == 'messages'):

        $parent_ids = [];
        foreach($messages as $data){
            if(!in_array($data->original_message, $parent_ids)){
                array_push($parent_ids, $data->original_message);
                $parent_id    = null;
                $ref_table_id = $data->original_message;
            } else {
                $parent_id    = $this->obs_message_model->find_single_data('id', ['ref_table_id' => $data->original_message]);
                $ref_table_id = $parent_id;
            }

            $subject = $this->main_messages_set_model->find_single_data('message_title', ['mtid' => $data->original_message]);

            if($data->message_type == 'I') {
                $message_type = 4;
            } elseif($data->message_type == null) {
                $message_type = 2;
            } elseif($data->message_type == 'R') {
                $message_type = 5;
            } elseif($data->message_type == 'O') {
                $message_type = 6;
            } elseif($data->message_type == '') {
                $message_type = 7;
            } elseif($data->message_type == 'M') {
                $message_type = 8;
            } else {
                $message_type = 9;
            }

            $data_message = [
                'ref_table'        => 'main_messaging',
                'ref_table_id'     => $ref_table_id,
                'message_type'     => $message_type,
                'user_id'          => $data->message_from,
                'parent_id'        => $parent_id,
                'subject'          => ($data->message_subject == '') ? $subject : $data->message_subject,
                'message'          => $data->message,
                'type'             => ($data->is_group == 'Yes') ? 1 : 2,
                'status'           => 1,
                'date_created'     => $data->date_created,
                'message_sequence' => $data->date_created
            ];
            $last_id = $this->obs_message_model->insert($data_message);

            // manually added for the new module, for sender
            $data_message2 = [
                'om_id'		   => $last_id,
                'recipient_id' => $data->message_from,
                'sms_sent'     => null,
                'trashed_by'   => ($data->deletedto == 'Y' || $data->deletedfrom == 'Y') ? $data->message_from : null,
                'read_date'    => $data->date_created
            ];
            $this->obs_message_recipients_model->insert($data_message2);

            if($data->is_group == 'Yes'){
                $recipients = $this->main_messaging_users_model->find_all(['original_message' => $data->original_message]);

                foreach($recipients as $recipient){
                    $data_message3 = [
                        'om_id'		   => $last_id,
                        'recipient_id' => $recipient['user_id'],
                        'sms_sent'     => ($data->sms_sent == 1) ? $data->sms_sent : null,
                        'trashed_by'   => ($data->deletedto == 'Y' || $data->deletedfrom == 'Y') ? $data->message_to : null,
                        'read_date'    => ($data->onread_to == 1) ? $data->date_created : null
                    ];
                }
            } else {
                $data_message3 = [
                    'om_id'		   => $last_id,
                    'recipient_id' => $data->message_to,
                    'sms_sent'     => ($data->sms_sent == 1) ? $data->sms_sent : null,
                    'trashed_by'   => ($data->deletedto == 'Y' || $data->deletedfrom == 'Y') ? $data->message_to : null,
                    'read_date'    => ($data->onread_to == 1) ? $data->date_created : null
                ];
            }

            if($this->obs_message_recipients_model->insert($data_message3)){
                echo $last_id . 'success' . '<br>';
            } else {
                echo $last_id . 'failed' . '<br>';
            }

            // add attached files
            $attached_files = $this->db->query('SELECT * FROM main_message_attach WHERE mid = '.$data->message_id)->result();
            if(count($attached_files) > 0) {
                foreach ($attached_files as $files) {
                    $type = explode('|', $files->real_filename);
                    $data_attach = [
                        'user_id'       => $data->message_from,
                        'ref_table'     => 'main_message_attach',
                        'ref_table_id'  => $last_id,
                        'upload_type'   => 23,
                        'path'          => null,
                        'filename'      => $type[3],
                        'orig_filename' => $type[3],
                        'file_ext'      => '.'.$type[1],
                        'file_size'     => number_format($type[2] / 1000, 2),
                        'json_data'     => '',
                        'status'        => 2,
                        'date_created'  => $data->date_created
                    ];
                    $this->uploads_model->insert($data_attach);
                }
            }
        }

    else: ?>
        <p>Else</p>
    <? endif; ?>
</section>

<footer>
    <p class="row note">Please remove this file right after migrating the table to avoid other users to accidentally run it.</p>
</footer>
</body>
</html>