<?php defined('BASEPATH') OR exit('No direct script access allowed');

// MAIL
if ( ! function_exists('send_mail'))
{
	function send_mail( $email_to=NULL, $subject=NULL, $message=NULL ) {
		$ci = get_instance();
		
		$ci->load->library('email');
		$config = [];
		$config = $ci->base_model->getValueArray('protocol, smtp_host, smtp_port, smtp_user, smtp_pass, smtp_timeout, charset, mailtype, priority', 'a_system', ['client_id', 'org_id'], [DEFAULT_CLIENT_ID, DEFAULT_ORG_ID]);
		$config['useragent'] = 'CodeIgniter';
		$config['newline'] 	 = "\r\n";
		$ci->email->initialize($config);

		$ci->email->clear();
		$ci->email->from($config['smtp_user'], '[SYSTEM APPS]');
		$ci->email->to($email_to); 
		// $ci->email->bcc('hertanto@fajarbenua.co.id');
		$ci->email->subject($subject);
		$ci->email->message($message);	

		if (! $ci->email->send()) {
			$ci->session->set_flashdata('message', $ci->email->print_debugger());
			return FALSE;
		}
		return TRUE;
	}
}