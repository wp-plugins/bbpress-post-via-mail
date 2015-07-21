<?php

class pvm_Connector_bbPress {
	protected $handler;

	public function __construct( $handler ) {
		$this->handler = $handler;
		remove_action( 'bbp_new_topic',    'bbp_notify_forum_subscribers', 11, 5 );
		remove_action( 'bbp_new_reply',    'bbp_notify_topic_subscribers', 11, 5 );
		add_action( 'bbp_new_topic', array( $this, 'notify_new_topic' ), 10, 4 );
		add_filter( 'bbp_new_reply', array( $this, 'notify_on_reply'  ),  1, 5 );

		add_action( 'pvm.reply.insert', array( $this, 'handle_insert' ), 20, 2 );
	}

	/**
	 * Get a human-readable name for the handler
	 *
	 * This is used for the handler selector and is shown to the user.
	 * @return string
	 */
	public static function get_name() {
		return 'bbPress';
	}

        public function send_mail ($users, pvm_Message $message) {
		$options = $message->get_options();

		$from = pvm::get_from_address();
		if ( $author = $message->get_author() ) {
			$from = sprintf( '%s <%s>', $author, $from );
		}
        //$debug_export = var_export($message, true);
        //error_log("Message to be sent:". $debug_export);
		$messages = array();
        $data="";
        if ( $text = $message->get_text() ) {
            $data = $text;
        }
        if ( $html = $message->get_html() ) {
            $data = $html;
        }
        if (!strlen($data))
        {
            error_log("pvm: Empty message to be sent");
            return;
        }
		foreach ($users as $user) {
			$to = $user->user_email;
                        $subj = $message->get_subject();
                        $reply_to=$message->get_reply_address($user);
			$headers = array();
			// Set the message ID if we've got one
			if ( ! empty( $options['message-id'] ) ) {
				$headers = array(
					'Name' => 'Message-ID',
					'Value' => $options['message-id'],
				);
			}

			// If this is a reply, set the headers as needed
			if ( ! empty( $options['in-reply-to'] ) ) {
				$original = $options['in-reply-to'];
				if ( is_array( $original ) ) {
					$original = isset( $options['in-reply-to'][ $user->ID ] ) ? $options['in-reply-to'][ $user->ID ] : null;
				}

				if ( ! empty( $original ) ) {
					$headers = array(
						'Name' => 'In-Reply-To',
						'Value' => $original,
					);
				}
			}

			if ( ! empty( $options['references'] ) ) {
				$references = implode( ' ', $options['references'] );
				$headers = array(
					'Name' => 'References',
					'Value' => $references,
				);
			}
  			//$debug_export = var_export($headers, true);
            $header[]='Reply-To: '.$reply_to;
            //error_log("Message -> To:".$to." Subject:".$subj." Data:".$data);
			wp_mail( $to, $subj, $data, $header );
			//$messages[ $user->ID ] = $this->send_single($data);
		}
		
	}
        /**
        * Notify user roles on new topic
        */
	public function notify_new_topic( $topic_id = 0, $forum_id = 0, $anonymous_data = 0, $topic_author = 0) {
		// JJ $user_roles = pvm::get_option( 'bb_pvm_topic_notification', array() );
         //       error_log("notify_new_topic");
                //$user_roles[0]="administrator";
		// bail out if no user roles found
		//if ( !$user_roles ) {
		//	return;
		//}
                //error_log ("bail out if no user roles found");
		//$recipients = array();
		//foreach ($user_roles as $role) {
		//	$users = get_users(array('role' => $role, 'fields' => array('ID', 'user_email', 'display_name')));
		//	$recipients = array_merge( $recipients, $users );
		//}
		//error_log ("still no users?");
		// still no users?
		//$debug_export = var_export($recipients, true);
                //error_log("Recipients:".$debug_export);
		//if ( !$recipients ) {
		//	return;
		//}
		$user_ids = bbp_get_forum_subscribers($forum_id, true);
        //$debug_export = var_export($user_ids, true);
        //error_log("Userss:".$debug_export);
        if (empty($user_ids)) return false;
		// subscribe the users automatically
        //if (pvm::get_option(' bb_pvm_topic_autosubscribe', '')) {
        //    error_log("Autosubscribe ON");
		//    foreach ($recipients as $user) {
		//    	bbp_add_user_subscription( $user->ID, $topic_id );
		//    }
		//}
        //else error_log("Autosubscribe OFF");
		// Get userdata for all users
        $user_ids = array_map(function ($id) {
             return get_userdata($id);
                }, $user_ids);


		// Sanitize the HTML into text
		$content = apply_filters( 'bb_pvm_html_to_text', bbp_get_topic_content( $topic_id ) );
        //error_log("Topic id". $topic_id);
        //error_log("Forum_id". $forum_id);
		// Build email
        $reply_author_name = bbp_get_topic_author_display_name( $topic_id );
        $subject = pvm::get_new_topic_subj();
        $text = pvm::get_new_topic_msg();
        $link = bbp_get_reply_url($topic_id);
        $text = str_replace ('{site}',get_option( 'blogname' ),$text);
        $subject = str_replace ('{site}',get_option( 'blogname' ),$subject);
        $text    = str_replace ('{forum}',bbp_get_forum_title ($forum_id),$text);
        $subject = str_replace ('{forum}',bbp_get_forum_title ($forum_id),$subject);
        $text    = str_replace ('{title}',bbp_get_topic_title( $topic_id ),$text);
        $subject = str_replace ('{title}',bbp_get_topic_title( $topic_id ),$subject);
        $text    = str_replace ('{author}',$reply_author_name,$text);
        $subject = str_replace ('{author}',$reply_author_name,$subject);
        $text    = str_replace ('{link}',$link,$text);
        $subject = str_replace ('{link}',$link,$subject);
        $text    = str_replace ('{content}',$content,$text);
        $subject = str_replace ('{content}',$content,$subject);
        $subject = apply_filters('bb_pvm_email_subject', $subject, 0, $topic_id);

		$options = array(
			'author' => $reply_author_name,
			'id'     => $topic_id,
		);

        $message = new pvm_Message();
        $message->set_subject( $subject );
        $message->set_text( $text);
        $message->set_options( $options );
        $message->set_reply_address_handler( function ( WP_User $user, pvm_Message $message ) use ( $topic_id ) {
                                                return pvm::get_reply_address( $topic_id, $user );
                                           } );
        //$post = get_post($topic_id);
        //$debug_export = var_export($post, true);
        //error_log ("Post :".$debug_export);
        $message->set_author( $reply_author_name );
        //$debug_export = var_export($message, true);
        //error_log ("Message nt:".$debug_export);
        //$this->handler->send_mail( $user_ids, $message );
        $this->send_mail($user_ids, $message);
		//$this->handler->send_mail( $recipients, $subject, $text, $options );

		do_action( 'bbp_post_notify_topic_subscribers', $topic_id, $user_ids );

	}

	/**
	 * Send a notification to subscribers
	 *
	 * @wp-filter bbp_new_reply 1
	 */
	public function notify_on_reply( $reply_id = 0, $topic_id = 0, $forum_id = 0, $anonymous_data = false, $reply_author = 0 ) {
		//error_log("notify_on_reply");
                if ($this->handler === null) {
			return false;
		}

		global $wpdb;

		if (!bbp_is_subscriptions_active()) {
			return false;
		}
                
		$reply_id = bbp_get_reply_id( $reply_id );
		$topic_id = bbp_get_topic_id( $topic_id );
		$forum_id = bbp_get_forum_id( $forum_id );
                
		if (!bbp_is_reply_published($reply_id)) {
			return false;
		}
		if (!bbp_is_topic_published($topic_id)) {
			return false;
		}

		$user_ids = bbp_get_topic_subscribers($topic_id, true);
		if (empty($user_ids)) {
			return false;
		}

		// Poster name
		$reply_author_name = apply_filters('bb_pvm_reply_author_name', bbp_get_reply_author_display_name($reply_id));

		do_action( 'bbp_pre_notify_subscribers', $reply_id, $topic_id, $user_ids );

		// Don't send notifications to the person who made the post
		$send_to_author = pvm::get_option('bb_pvm_send_to_author', false);

		if (!$send_to_author && !empty($reply_author)) {
			$user_ids = array_filter($user_ids, function ($id) use ($reply_author) {
				return ((int) $id !== (int) $reply_author);
			});
		}

		// Get userdata for all users
		$user_ids = array_map(function ($id) {
			return get_userdata($id);
		}, $user_ids);

		// Sanitize the HTML into text
		$content = apply_filters('bb_pvm_html_to_text', bbp_get_reply_content($reply_id));
		$debug_export = var_export($reply_id, true);
		// Build email 
		$subject = pvm::get_new_reply_subj();
		$text = pvm::get_new_reply_msg();
		$link = bbp_get_reply_url($reply_id);
        $text = str_replace ('{site}',get_option( 'blogname' ),$text);
        $subject = str_replace ('{site}',get_option( 'blogname' ),$subject);
		$text    = str_replace ('{forum}',bbp_get_forum_title ($forum_id),$text);
       	$subject = str_replace ('{forum}',bbp_get_forum_title ($forum_id),$subject);
		$text    = str_replace ('{title}',bbp_get_topic_title( $topic_id ),$text);
        $subject = str_replace ('{title}',bbp_get_topic_title( $topic_id ),$subject);
		$text    = str_replace ('{author}',$reply_author_name,$text);
       	$subject = str_replace ('{author}',$reply_author_name,$subject);
		$text    = str_replace ('{link}',$link,$text);
        $subject = str_replace ('{link}',$link,$subject);
		$text    = str_replace ('{content}',$content,$text);
        $subject = str_replace ('{content}',$content,$subject);
		$subject = apply_filters('bb_pvm_email_subject', $subject, $reply_id, $topic_id);

		$options = array(
			'id'     => $topic_id,
			'author' => $reply_author_name,
		);
        $message = new pvm_Message();
		$message->set_subject( $subject );
		$message->set_text( $text);
		$message->set_options( $options );
        $message->set_reply_address_handler( function ( WP_User $user, pvm_Message $message ) use ( $topic_id ) {
			return pvm::get_reply_address( $topic_id, $user );
		} );
        // JJ $message->set_author( get_the_author_meta( 'display_name', $post->post_author ) );
		$message->set_author($reply_author_name);
		//$debug_export = var_export($message, true);
        //error_log ("Message new reply:".$debug_export);

        //$this->handler->send_mail( $user_ids, $message );
        $this->send_mail($user_ids, $message);
		do_action( 'bbp_post_notify_subscribers', $reply_id, $topic_id, $user_ids );

		return true;
	}

	protected function is_allowed_type( $type ) {
		$allowed = array( 'topic','reply' );
		return in_array( $type, $allowed );
	}

	public function handle_insert( $value, pvm_Reply $reply ) {
        //DebugDump($reply);
        //error_log ("Reply value:".$debug_export);
        if ( ! empty( $value ) ) {
			return $value;
		}
        //$debug_export = var_export($reply, true);
        //error_log ("Reply :".$debug_export);
		$post = get_post( $reply->post );
        //$debug_export = var_export($post, true);
        //error_log ("Post :".$debug_export);
		if (! $post)
			return $value;
		if ( ! $this->is_allowed_type( $post->post_type ) ) {
			return $value;
		}
		$user = $reply->get_user();
        //$debug_export = var_export($user, true);
       	//error_log ("User:".$debug_export);
		if (! $reply->is_valid() ) {
			pvm::notify_invalid( $user, $reply->from, bbp_get_reply_url($reply->post),bbp_get_topic_title( $reply->post ) );
			return false;
		}
        $debug_export = var_export($attch, true);
        $attachments = $reply->parse_attachments();
        $debug_export = var_export($attachments, true);
        error_log("Attachemnets ". $debug_export);
		$new_reply = array(
			'post_parent'       => $reply->post, // topic ID
			'post_author'       => $user->ID,
			'post_content'      => $reply->parse_body(),
            'post_attachments'  => $attachments['attachments'],
			'post_title'        => $reply->subject,
		);
        if ($attachments['errors'])
            error_log("There are errors!!");
        //$debug_export = var_export($new_reply, true);
        //error_log("New reply: ". $debug_export);
		$meta = array(
			'author_ip' => '127.0.0.1', // we could parse Received, but it's a pain, and inaccurate
			'forum_id' => bbp_get_topic_forum_id($reply->post),
			'topic_id' => $reply->post
		);
        // Subscribe to topic if needed
        if (pvm::get_option(' bb_pvm_topic_autosubscribe', '')) {
            //error_log("Autosubscribe ON");
            bbp_add_user_subscription( $user->ID, $reply->post );
        }
        //else error_log("Autosubscribe OFF");
        //$debug_export = var_export($new_reply, true);
        //error_log ("Reply to be insterted:".$debug_export);
		//$debug_export = var_export($meta, true);
        //error_log ("Reply meta:".$debug_export);
		$reply_id = bbp_insert_reply($new_reply, $meta);
        //$attchs = $new_reply['post_attachments'];
        //$debug_export = var_export($attchs, true);
        //error_log("Attachments!: ". $debug_export);
        foreach($new_reply['post_attachments'] as $attch) {
            $debug_export = var_export($attch, true);
            error_log("Attachments #: ". $debug_export);
            $attch['post_parrent'] = $reply_id;
            $attch['post_author']  = $user->ID;
            $id = wp_insert_attachment($attch, $attch['filename'], $reply_id);
            error_log("after wp_insert_attachment: attachement id:". $id);
        }
		do_action( 'bbp_new_reply', $reply_id, $meta['topic_id'], $meta['forum_id'], false, $new_reply['post_author'] );

		// bbPress removes the user's subscription because bbp_update_reply() is hooked to 'bbp_new_reply' and it checks for $_POST['bbp_topic_subscription']
		bbp_add_user_subscription( $new_reply['post_author'], $meta['topic_id'] );

		return $reply_id;
	}

	public function register_settings() {
		register_setting( 'bb_pvm_options', 'bb_pvm_topics_notification', array(__CLASS__, 'validate_topics_notification') );

		add_settings_section('bb_pvm_options_bbpress', 'bbPress', '__return_null', 'bb_pvm_options');
		add_settings_field('bb_pvm_options_bbpress_topics_notification', 'New Topics Notification', array(__CLASS__, 'settings_field_topics_notification'), 'bb_pvm_options', 'bb_pvm_options_bbpress');
	}

	/**
	 * Print field for new topic notification
	 *
	 * @see self::init()
	 */

	/**
	 * Validate the new topic notification
	 *
	 * @param array $input
	 * @return array
	 */
	public function validate_topic_notification( $input ) {
		return is_array( $input ) ? $input : array();
	}
/**
	 * Get available settings for notifications
	 *
	 * @return array
	 */
	public function get_available_settings() {
                return null; 
	}

	public function get_available_settings_short() {
                return null; 

        }

	/**
	 * Get default notification settings
	 *
	 * @return array Map of type => pref value
	 */
	protected function get_default_settings() {
		return null;
	}

	/**
	 * Get notification settings for the current user
	 *
	 * @param int $user_id User to get settings for
	 * @return array Map of type => pref value
	 */
	protected function get_settings_for_user( $user_id, $site_id = null ) {
		$available = $this->get_available_settings();
		$settings = array();

		foreach ( $available as $type => $choices ) {
			$key = $this->key_for_setting( 'notifications.' . $type, $site_id );
			$value = get_user_meta( $user_id, $key );
			if ( empty( $value ) ) {
				continue;
			}

			$settings[ $type ] = $value[0];
		}

		return $settings;
	}

	protected function key_for_setting( $key, $site_id = null ) {
		return pvm_Manager::key_for_setting( 'bbpress', $key, $site_id );
	}

	protected function print_field( $field, $settings, $is_defaults_screen = false ) {
		$defaults = $this->get_default_settings();

		$site_id = get_current_blog_id();
		$default = isset( $defaults[ $field ] ) ? $defaults[ $field ] : false;
		$current = isset( $settings[ $field ] ) ? $settings[ $field ] : $default;
		$notifications = $this->get_available_settings();
        //$debug_export = var_export($notifications, true);
        //error_log("Notifications -> ".$debug_export);
		if ($notifications)
			foreach ( $notifications as $value => $title ) {
				printf('<label><textarea name="%s" class="large-text" rows="15">%s</textarea></label>',
					esc_attr( $this->key_for_setting( 'notifications.' . $field ) ),
					esc_attr( $value ));
			}
	}

	public function output_settings( $user = null ) {
		// Are we on the notification defaults screen?
		$is_defaults_screen = empty( $user );

		// Grab defaults and currently set
		$settings = $is_defaults_screen ? $this->get_default_settings() : $this->get_settings_for_user( $user->ID );

		?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Emails about new topics will be sent to subscribers of forum', 'pvm' ) ?></th>
				<td>
					<?php $this->print_field( 'message.new.topic', $settings, $is_defaults_screen ) ?>
				</td>
			</tr>
			<tr>	<th scope="row"><?php esc_html_e( "Email about new replies will be sent to topics' subscribers", 'pvm' ) ?></th>
				<td>
                                    	<?php $this->print_field( 'message.new.reply', $settings, $is_defaults_screen ) ?>
                                </td>
			</tr>
		<?php
	}

	public function save_profile_settings( $user_id, $args = array(), $sites = null ) {
		$available = $this->get_available_settings();

		if ( $sites === null ) {
			$sites = array( get_current_blog_id() );
		}

		foreach ( $available as $type => $options ) {
			foreach ( $sites as $site ) {
				$key = $this->key_for_setting( 'notifications.' . $type, $site );

				// PHP strips '.' out of POST data as a relic from the
				// register_globals days, so we need to take that into account
				$request_key = str_replace( '.', '_', $key );
				if ( ! isset( $args[ $request_key ] ) ) {
					continue;
				}
				$value = $args[ $request_key ];

				// Check the value is valid
				$options = array_keys( $options );
				if ( ! in_array( $value, $options ) ) {
					continue;
				}

				// Actually set it!
				if ( ! update_user_meta( $user_id, wp_slash( $key ), wp_slash( $value ) ) ) {
					// TODO: Log this?
					continue;
				}
			}
		}
	}

	public function network_notification_settings( $user = null, $sites ) {
		// Are we on the notification defaults screen?
		$is_defaults_screen = empty( $user );

		$available = $this->get_available_settings();
		$short_names = $this->get_available_settings_short();
		$defaults = $this->get_default_settings();

		?>
		<table class="widefat pvm-grid">
			<thead>
				<tr>
					<th></th>
					<th colspan="<?php echo esc_attr( count( $available['post'] ) ) ?>"
						class="last_of_col"><?php
						esc_html_e( 'Posts', 'pvm' ) ?></th>
					<th colspan="<?php echo esc_attr( count( $available['comment'] ) ) ?>"><?php
						esc_html_e( 'Comments', 'pvm' ) ?></th>
				</tr>
				<tr>
					<th></th>
					<?php
					foreach ( $available as $type => $opts ) {
						$last = key( array_slice( $opts, -1, 1, true ) );

						foreach ( $opts as $key => $title ) {
							printf(
								'<td class="%s"><abbr title="%s">%s</abbr>%s</td>',
								( $key === $last ? 'last_of_col' : '' ),
								esc_attr( $title ),
								esc_html( $short_names[ $type ][ $key ] ),
								( $key === $defaults[ $type ] ) ? ' <strong>*</strong>' : ''
							);
						}
					}
					?>
				</tr>
			</thead>

			<?php
			foreach ( $sites as $site ):
				$details = get_blog_details( $site );
				$settings = $this->get_settings_for_user( $user->ID, $site );

				$title = esc_html( $details->blogname ) . '<br >';
				$path = $details->path;
				if ( $path === '/' ) {
					$path = '';
				}

				$title .= '<span class="details">' . esc_html( $details->domain . $path ) . '</span>';
				?>
				<tr>
					<th scope="row"><?php echo $title ?></th>

					<?php
					foreach ( $available as $type => $opts ) {
						$default = isset( $defaults[ $type ] ) ? $defaults[ $type ] : false;
						$current = isset( $settings[ $type ] ) ? $settings[ $type ] : $default;

						$name = $this->key_for_setting( 'notifications.' . $type, $site );

						foreach ( $opts as $key => $title ) {
							printf(
								'<td><input type="radio" name="%s" value="%s" %s /></td>',
								esc_attr( $name ),
								esc_attr( $key ),
								checked( $key, $current, false )
							);
						}
					}
					?>
				</tr>
			<?php endforeach ?>
		</table>
		<?php
	}

}
