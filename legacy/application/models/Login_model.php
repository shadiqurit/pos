<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author: Askarali Makanadar
 * Date: 05-11-2018
 */
class Login_model extends CI_Model
{
	
	function __construct()
	{
		parent::__construct();
	}

	public function verify_credentials($email,$password)
	{
		//Filtering XSS and html escape from user inputs 
		$email=$this->security->xss_clean(html_escape($email));
		$password=$this->security->xss_clean(html_escape($password));


		$this->db->select("a.email,a.store_id,a.id,a.username,a.role_id,b.role_name,a.status,a.last_name,a.password");
		$this->db->from("db_users a");
		$this->db->join("db_roles b", "b.id=a.role_id");
		$this->db->group_start();
		$this->db->where("a.email", $email);
		$this->db->or_where("a.username", $email);
		$this->db->group_end();
		$query = $this->db->get();
		$record = ($query->num_rows() === 1) ? $query->row() : null;
		$stored_password = $record ? (string) $record->password : '';
		$password_valid = $record && (
			password_verify($password, $stored_password)
			|| hash_equals($stored_password, md5($password))
		);
		if($password_valid){
			// Transparently upgrade legacy MD5 passwords after a successful login.
			if(!password_get_info($stored_password)['algo']){
				$this->db->where('id', $record->id);
				$this->db->update('db_users', [
					'password' => password_hash($password, PASSWORD_DEFAULT),
				]);
			}

			$store_rec = get_store_details($record->store_id);
			//STORE ACTIVE OR NOT
			if(!$store_rec->status){
				$this->session->set_flashdata('failed', 'Your Store Temporarily Inactive!');
				legacy_redirect('login');exit;
			}
			//USER ACTIVE OR NOT
			if(!$record->status){
				$this->session->set_flashdata('failed', 'Your account is temporarily inactive!');
				legacy_redirect('login');exit;
			}


			$logdata = array(
							'inv_username'  => $record->username,
							'user_lname'  => $record->last_name,
				        	 'inv_userid'  => $record->id,
				        	 'logged_in' => TRUE,
				        	 'role_id' => $record->role_id,
				        	 'role_name' => trim($record->role_name),
				        	 'store_id' => trim($record->store_id),
				        	 'email' => trim($record->email),
				        	);
			$this->session->set_userdata($logdata);
			$this->session->set_flashdata('success', 'Welcome '.ucfirst($record->username)." !");
			legacy_redirect(base_url().'dashboard');
		}
		else{
			$this->session->set_flashdata('failed', 'Invalid Email or Password!');
			legacy_redirect('login');
		}		
	}
	public function verify_email_send_otp($email)
	{
		
		//Filtering XSS and html escape from user inputs 
		$to=$this->security->xss_clean(html_escape($email));
				
		$query=$this->db->query("select * from db_users where email='$email' and status=1");

		if($query->num_rows() == 0){
			$this->session->set_flashdata('failed', 'This Email ID not Exist in Our Records!');
			return false;
		}

			$store_id = $query->row()->store_id;

			$q1=$this->db->query("select email,store_name from db_store where id=$store_id");
			
			$otp=rand(1000,9999);

$subject = "OTP for Password Change | OTP: ".$otp;


$message="---------------------------------------------------------
Hello User,

You are requested for Password Change,
Please enter ".$otp." as a OTP.

Note: Don't share this OTP with anyone.
Thank you
---------------------------------------------------------
";
			

			$this->load->model('email_model');

			$contants = array(
								'to' => $to,
								'subject' => $subject,
								'message' => $message,
							);
			$response = $this->email_model->send_email($contants);

			if($response){
				$this->session->set_flashdata('success', 'OTP has been sent to your email ID! (Check Inbox/Spam Box)');
				$otpdata = array('email'  => $to,'otp'  => $otp );
				$this->session->set_userdata($otpdata);
				return true;
			}
			else{
				$this->session->set_flashdata('error', $response);
				return false;
			}
		
			
	}
	public function verify_otp($otp)
	{
		//Filtering XSS and html escape from user inputs 
		$otp=$this->security->xss_clean(html_escape($otp));
		$email=$this->security->xss_clean(html_escape($email));
		if($this->session->userdata('email')==$email){ legacy_redirect(base_url().'logout','refresh');	}
				
		$query=$this->db->query("select * from db_users where username='$username' and password='".md5($password)."' and status=1");
		if($query->num_rows()==1){

			$logdata = array(
							'inv_username'  => $query->row()->username,
							'user_lname'  => $query->row()->last_name,
				        	 'inv_userid'  => $query->row()->id,
				        	 'logged_in' => TRUE,
				        	 'role_id' => $query->row()->role_id,
				        	 'role_name' => trim($query->row()->role_name),
				        	 'store_id' => trim($query->row()->store_id),
				        	);
			$this->session->set_userdata($logdata);
			return true;
		}
		else{
			return false;
		}		
	}
	public function change_password($password,$email){
			$query=$this->db->query("select * from db_users where email='$email' and status=1");
			if($query->num_rows()==1){
				/*if($query->row()->username == 'admin'){
					echo "Restricted Admin Password Change";exit();
				}*/
				$password=password_hash($password, PASSWORD_DEFAULT);
				$query1="update db_users set password='$password' where email='$email'";
				if ($this->db->simple_query($query1)){

				        return true;
				}
				else{
				        return false;
				}
			}
			else{
				return false;
				}

		}
}
