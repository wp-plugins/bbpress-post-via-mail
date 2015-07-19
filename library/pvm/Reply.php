<?php

use \EmailReplyParser\EmailReplyParser;

function log_info($v) {
    error_log("bbPress-pvm: $v");
}

function debug_dump($v) {
    if (IsDebugMode()) {
        $o = print_r($v, true);
        if (php_sapi_name() == "cli") {
            print( "$o\n");
        } else {
            //flush the buffers
            while (ob_get_level() > 0) {
            ob_end_flush();
            }
            print( "<pre>\n");
            EchoInfo($o);
            print( "</pre>\n");
       }
   }
}


class pvm_Reply {
	public $from;
	public $subject;
	public $body;
	public $attachments;
    public $nonce;
	public $post;
	public $site;
        
	public function __construct() {
	}

//	function pvm_handle_upload(&$file, $overrides = false, $time = null) {
//    	// The default error handler.
//    	if (!function_exists('wp_handle_upload_error')) {
//
//        	function wp_handle_upload_error(&$file, $message) {
//            	return array('error' => $message);
//        	}
//
//    	}
//
//    	// A correct MIME type will pass this test. Override $mimes or use the upload_mimes filter.
//    	$wp_filetype = wp_check_filetype($file['name']);
//    	debug_dump("postie_handle_upload: detected file type for " . $file['name'] . " is " . $wp_filetype['type']);
//
//    	if (!isset($file['type'])) {
//        	//debug_dump("postie_handle_upload: adding type - " . $wp_filetype['type']);
//        	$file['type'] = $wp_filetype['type'];
//    	}
//    	$file = apply_filters('wp_handle_upload_prefilter', $file);
//
//    	// You may define your own function and pass the name in $overrides['upload_error_handler']
//    	$upload_error_handler = 'wp_handle_upload_error';
//
//    	// $_POST['action'] must be set and its value must equal $overrides['action'] or this:
//    	$action = 'wp_handle_upload';
//
//    	// Courtesy of php.net, the strings that describe the error indicated in $_FILES[{form field}]['error'].
//    	$upload_error_strings = array(false,
//        	__("The uploaded file exceeds the <code>upload_max_filesize</code> directive in <code>php.ini</code>."),
//        	__("The uploaded file exceeds the <em>MAX_FILE_SIZE</em> directive that was specified in the HTML form."),
//        	__("The uploaded file was only partially uploaded."),
//        	__("No file was uploaded."),
//        	'',
//       	 	__("Missing a temporary folder."),
//        	__("Failed to write file to disk."));
//
//    	// Install user overrides. Did we mention that this voids your warranty?
//    	if (is_array($overrides)) {
//        	extract($overrides, EXTR_OVERWRITE);
//    	}
//    	// A successful upload will pass this test. It makes no sense to override this one.
//    	if ($file['error'] > 0) {
//        	return $upload_error_handler($file, $upload_error_strings[$file['error']]);
//    	}
//    	// A non-empty file will pass this test.
//    	if (!($file['size'] > 0 )) {
//        	return $upload_error_handler($file, __('File is empty. Please upload something more substantial. This error could also be caused by uploads being disabled in your php.ini.'));
//    	}
//    	// A properly uploaded file will pass this test. There should be no reason to override this one.
//    	if (!file_exists($file['tmp_name'])) {
//        	return $upload_error_handler($file, __('Specified file failed upload test.'));
//   	}
//
//    	//extract($wp_filetype);
//    	$mimetype = $wp_filetype['type'];
//    	$ext = $wp_filetype['ext'];
//
//    	if (empty($ext)) {
//        	$ext = ltrim(strrchr($file['name'], '.'), '.');
//    	}
//    	if (empty($mimetype)) {
//        	$mimetype = $file['type'];
//    	}
//
//    	debug_dump("postie_handle_upload (type/ext): '$mimetype' / '$ext'");
//
//    	if ((empty($mimetype) && empty($ext)) && !current_user_can('unfiltered_upload')) {
//        	debug_dump("postie_handle_upload: no type/ext & user restricted");
//        	return $upload_error_handler($file, __('File type does not meet security guidelines. Try another.'));
//    	}
//
//   	 // A writable uploads dir will pass this test. Again, there's no point overriding this one.
//    	if (!( ( $uploads = wp_upload_dir($time) ) && false === $uploads['error'] )) {
//        	debug_dump("postie_handle_upload: directory not writable");
//        	return $upload_error_handler($file, $uploads['error']);
//    	}
//    	// fix filename (encode non-standard characters)
//    	$file['name'] = filename_fix($file['name']);
//    	$filename = wp_unique_filename($uploads['path'], $file['name']);
//    	debug_dump("wp_unique_filename: $filename");
//
//    	// Move the file to the uploads dir
//	$new_file = $uploads['path'] . "/$filename";
//
//    	//move_uploaded_file() will not work here
//    	if (false === rename($file['tmp_name'], $new_file)) {
//        	debug_dump("upload: rename failed");
//        	debug_dump("old file: " . $file['tmp_name']);
//        	debug_dump("new file: $new_file");
//        	//debug_dump($file);
//        	//debug_dump($uploads);
//        	return $upload_error_handler($file, sprintf(__('The uploaded file could not be moved to %s.'), $uploads['path']));
//    	} else {
//        	debug_dump("upload: rename to $new_file succeeded");
//    	}
//
//    	// Set correct file permissions
//    	$stat = stat(dirname($new_file));
//    	$perms = $stat['mode'] & 0000666;
//    	if (chmod($new_file, $perms)) {
//        	debug_dump("upload: permissions changed");
//    	} else {
//        	debug_dump("upload: permissions not changed $new_file");
//    	}
//
//    	// Compute the URL
//    	$url = $uploads['url'] . "/$filename";
//
//    	debug_dump("upload: before apply_filters");
//    	$return = apply_filters('wp_handle_upload', array('file' => $new_file, 'url' => $url, 'type' => $mimetype));
//    	debug_dump("upload: after apply_filters");
//
//    	return $return;
//	}


	public function parse_body() {
		// Parse the body and remove signatures, and reformat
		$parts = array();
        $email = EmailReplyParser::read($this->body);
        $fragments = $email->getFragments();
		//$debug_export = var_export($this->attachments, true);
        //error_log("Attachments: ".$debug_export."\n\n");
		//$debug_export = var_export($email, true);
        //error_log("Email: ".$debug_export."\n\n");               
		//$debug_export = var_export($fragments, true);
       	//error_log("Fragments: ".$debug_export);
		foreach ($fragments as $fragment) {
			// We don't care about hidden parts (signatures, eg)
            // $debug_export = var_export($fragment, true);
			if ($fragment->isHidden()) {
				continue;
			}
			elseif ($fragment->isQuoted()) {
				// Remove leading quote symbols
				$quoted = preg_replace('/^> */m', '', $fragment->getContent());		
				// Reparse to ensure that we strip signatures from here too
				$subfragments = EmailReplyParser::read($quoted);
				$subparts = array();
				foreach ($subfragments as $subfrag) {
					if ($subfrag->isHidden()) continue;
					$subparts[] = $subfrag->getContent();
				}
				$parts[] = '<blockquote>' . implode("\n", $subparts) . '</blockquote>';
			}
			else $parts[] = $fragment->getContent();
		}
		$content = implode("\n", $parts);
		return $content;
	}

    public function parse_attachments() {
		$upload_dir = wp_upload_dir();
        //$debug_export = var_export($upload_dir, true);
        //error_log("Upload_dir: ".$debug_export."\n\n");
		$action = 'wp_handle_upload';
        $attachment_list = array ();
        foreach($this->attachments as $attachment) {
			$tmpFile = tempnam(get_temp_dir(), 'pvm');
    	    if ($tmpFile !== false) {
        		$downloaded_size=$attachment->DownloadToFile($tmpFile);
                //error_log("downloaded_sizze ".$downloaded_size);
                //error_log("attachment->ContentLength ".$attachment->ContentLength);
        		if ($downloaded_size != $attachment->ContentLength) { 
					error_log("Problem downloading file ".$tmpFile. "for attachment ".$attachment->Name."Downloaded".$downloaded_size." instead of ".$attachment->ContentLength);
			        continue;
                }
            }
			//if (!file_exists($tmpFile))
			//	error_log("The file was not created!!");
            //else
			//	error_log("File created OK: ".$tmpFile);
	        //$overrides = array('test_form' => false);
            //$file = array(	'name' => $attachment->Name,
            //                'type' => $attachment->ContentType,
            //            	'size' => $attachment->ContentLength,
            //            	'tmp_name' => $tmpFile,
            //              	'error' => 0
            //        	);
			//$file['name'] = filename_fix($file['name']);
        	$filename = filename_fix($attachment->Name);
            $filename = wp_unique_filename($upload_dir["path"], $filename);
            $path_parts = pathinfo($filename);
            $debug_export = var_export($path_parts,true);
            $attachment_name = $path_parts['filename'];
        	// Move the file to the uploads dir
        	$new_file = $upload_dir["path"] . "/$filename";
            //if (!file_exists($tmpFile)) error_log("Nima pliku ".$file['tmp_name']);
			//else error_log("Jest plik ".$file['tmp_name']);
        	if (false === rename($tmpFile, $new_file)) {
                error_log("Rename failed new file: ". $new_file. " old file: ".$tmpFile);
                continue ;
        	}
            // Set correct file permissions
    	    $stat = stat(dirname($new_file));
    		$perms = $stat['mode'] & 0000666;
    		if (!chmod($new_file, $perms)) {
        		error_log("upload: permissions not changed". $new_file);
    		//} else {
        	//	debug_dump("upload: permissions not changed $new_file");
    		}
    		// Compute the URL
    		$url = $upload_dir['url'] . "/$filename";
    		//debug_dump("upload: before apply_filters");
            //$debug_export = var_export(array('file' => $new_file, 'url' => $url,'type' => $attachment->ContentType), true);
            //error_log("apply_filters wp_handle_upload on: ".$debug_export);
    		$return = apply_filters('wp_handle_upload', array('file' => $new_file, 'url' => $url,'type' => $attachment->ContentType));
            $attachment_list[] = array ('post_mime_type'=>$attachment->ContentType,
                                        'guid'=>$url,
                                        'post_parent'=>0,
                                        'post_title'=>$attachment_name,
                                        'post_excerpt'=>'',
                                        'post_content'=>'',
                                        'post_author'=>1,
                                        'filename' => $new_file);
            //$debug_export = var_export($attachment_list, true);
            //error_log("Attachment list: ". $debug_export);
        }
        return $attachment_list;
    }

	public function get_user() {
		return get_user_by( 'id', $this->user );
	}

	public function is_valid() {
		$user = $this->get_user();
                //error_log ("Nonce: ".$this->nonce);
                //error_log ("pvm hash: ".pvm::get_hash($this->post, $user, $this->site)); 
		return $this->nonce === pvm::get_hash($this->post, $user, $this->site);
	}

	public function insert() {
		if ( is_multisite() ) {
			switch_to_blog( $this->site );
		}

		$result = apply_filters( 'pvm.reply.insert', null, $this );

		if ( is_multisite() ) {
			restore_current_blog();
		}

		return $result;
	}

	public static function parse_to($address) {
		$template = pvm::get_option('bb_pvm_replyto');

		// No plus address in saved, parse via splitting
		$has_match = preg_match( '/\+(\w+)-(\d+)-(\d+)-(\w+)\@.*/i', $address, $matches );
		if ( ! $has_match ) {
			throw new Exception('Reply-to not formatted correctly -'.$address);
                        throw new Exception($address);
		}
		return array( $matches[1], $matches[2], $matches[3], $matches[4] );
	}
}

