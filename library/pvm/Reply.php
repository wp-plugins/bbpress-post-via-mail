<?php

use \EmailReplyParser\EmailReplyParser;

class pvm_Reply {
	public $from;
	public $subject;
	public $body;
	public $nonce;
	public $post;
	public $site;

	public function __construct() {
	}

	public function parse_body() {
		// Parse the body and remove signatures, and reformat
		$parts = array();
		//$fragments = EmailReplyParser::read($this->body);
                $email = EmailReplyParser::read($this->body);
                $fragments = $email->getFragments();
                //$debug_export = var_export($fragments, true);
       	       	//error_log("Fragments: ".$debug_export);
		foreach ($fragments as $fragment) {
			// We don't care about hidden parts (signatures, eg)
                        // $debug_export = var_export($fragment, true);
                        // throw new Exception ("Frag0:".$debug_export);
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
					if ($subfrag->isHidden()) {
						continue;
					}

					$subparts[] = $subfrag->getContent();
				}

				$parts[] = '<blockquote>' . implode("\n", $subparts) . '</blockquote>';
			}
			else {
				$parts[] = $fragment->getContent();
			}
		}
		$content = implode("\n", $parts);
		return $content;
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
