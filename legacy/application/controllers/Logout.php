<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Logout extends MY_Controller {
	public function __construct(){
		parent::__construct();
		$this->load_info();
	}
	public function index()
	{
		$this->session->userdata('language');

		$cookie= array(
           'name'   => 'language',
           'value'  => $this->session->userdata('language'),
           'expire' => '3600',
       	);
        $this->input->set_cookie($cookie);


		$data = $this->data;
		// CodeIgniter 4 manages expired file sessions itself.
		// Clear the current application session and return to the login page.
		$this->session->sess_destroy();
		//LOGOUT
		legacy_redirect(base_url('login'));
	}
}
