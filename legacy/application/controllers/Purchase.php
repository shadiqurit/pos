<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Dompdf\Dompdf;
use Dompdf\Options;

class Purchase extends MY_Controller {
	public function __construct(){
		parent::__construct();
		$this->load_global();
		$this->load->model('purchase_model','purchase');
	}

	public function index()
	{
		$this->permission_check('purchase_view');
		$data=$this->data;
		$data['page_title']=$this->lang->line('purchase_list');
		$this->load->view('purchase-list',$data);
	}
	
	public function add()
	{
		$this->permission_check('purchase_add');
		$data=$this->data;
		$data['page_title']=$this->lang->line('purchase');
		$this->load->view('purchase',$data);
	}

	public function purchase_save_and_update(){
		$this->form_validation->set_rules('pur_date', 'Purchase Date', 'trim|required');
		$this->form_validation->set_rules('supplier_id', 'Supplier Name', 'trim|required');
		
		if ($this->form_validation->run() == TRUE) {
	    	$result = $this->purchase->verify_save_and_update();
	    	echo $result;
		} else {
			echo "Please Fill Compulsory(* marked) Fields.";
		}
	}
	
	public function update($id){
		$this->belong_to('db_purchase',$id);
		$this->permission_check('purchase_edit');
		$data=$this->data;
		$data=array_merge($data,array('purchase_id'=>$id));
		$data['page_title']=$this->lang->line('purchase');
		$this->load->view('purchase', $data);
	}
	
	//adding new item from Modal
	public function newsupplier(){
	
		$this->form_validation->set_rules('supplier_name', 'supplier Name', 'trim|required');
		
		if ($this->form_validation->run() == TRUE) {
			$this->load->model('suppliers_model');
			$result=$this->suppliers_model->verify_and_save();
			//fetch latest item details
			$res=array();
			$query=$this->db->query("select id,supplier_name from db_suppliers order by id desc limit 1");
			$res['id']=$query->row()->id;
			$res['supplier_name']=$query->row()->supplier_name;
			$res['result']=$result;
			
			echo json_encode($res);

		} 
		else {
			echo "Please Fill Compulsory(* marked) Fields.";
		}
	}

	public function ajax_list()
	{
		$list = $this->purchase->get_datatables();
		
		$data = array();
		$no = $_POST['start'];
		foreach ($list as $purchase) {
			
			$no++;
			$row = array();
			$row[] = '<input type="checkbox" name="checkbox[]" value='.$purchase->id.' class="checkbox column_checkbox" >';
			
			$row[] = show_date($purchase->purchase_date);

			$info = (!empty($purchase->return_bit)) ? "<br><span class='label label-danger' style='cursor:pointer'><i class='fa fa-fw fa-undo'></i>Return Raised</span>" : '';

			$row[] = $purchase->purchase_code.$info;
			$row[] = $purchase->purchase_status;
			$row[] = $purchase->reference_no;
			$row[] = $purchase->supplier_name;
			
			$row[] = store_number_format($purchase->grand_total);
			$row[] = store_number_format($purchase->paid_amount);
					$str='';
					if($purchase->payment_status=='Unpaid')
			          $str= "<span class='label label-danger' style='cursor:pointer'>Unpaid </span>";
			        if($purchase->payment_status=='Partial')
			          $str="<span class='label label-warning' style='cursor:pointer'> Partial </span>";
			        if($purchase->payment_status=='Paid')
			          $str="<span class='label label-success' style='cursor:pointer'> Paid </span>";

			$row[] = $str;
			$row[] = ($purchase->created_by);
					$str2 = '<div class="btn-group" title="View Account">
										<a class="btn btn-primary btn-o dropdown-toggle" data-toggle="dropdown" href="#">
											Action <span class="caret"></span>
										</a>
										<ul role="menu" class="dropdown-menu dropdown-light pull-right">';
											if($this->permissions('purchase_view'))
											$str2.='<li>
												<a title="View Invoice" href="'.base_url().'purchase/invoice/'.$purchase->id.'" ><i class="fa fa-fw fa-eye text-blue"></i>View Purchase
												</a>
											</li>';

											if($this->permissions('purchase_edit'))
											$str2.='<li>
												<a title="Update Record ?" href="'.base_url().'purchase/update/'.$purchase->id.'">
													<i class="fa fa-fw fa-edit text-blue"></i>Edit
												</a>
											</li>';

											if($this->permissions('purchase_payment_view'))
											$str2.='
											<li>
												<a title="Pay" class="pointer" onclick="view_payments('.$purchase->id.')" >
													<i class="fa fa-fw fa-money text-blue"></i>View Payments
												</a>
											</li>';

											if($this->permissions('purchase_payment_add'))
											$str2.='<li>
												<a title="Pay" class="pointer" onclick="pay_now('.$purchase->id.')" >
													<i class="fa fa-fw  fa-hourglass-half text-blue"></i>Pay Now
												</a>
											</li>';

											if($this->permissions('purchase_add') || $this->permissions('purchase_edit'))
											$str2.='<li>
												<a title="Take Print" target="_blank" href="purchase/print_invoice/'.$purchase->id.'">
													<i class="fa fa-fw fa-print text-blue"></i>Print
												</a>
											</li>
											<li>
												<a title="Download PDF" target="_blank" href="'.base_url().'purchase/pdf/'.$purchase->id.'">
													<i class="fa fa-fw fa-file-pdf-o text-blue"></i>PDF
												</a>
											</li>';

											if($this->permissions('purchase_return_add'))
											$str2.='<li>
												<a title="Purchase Return" href="'.base_url().'purchase_return/add/'.$purchase->id.'">
													<i class="fa fa-fw fa-undo text-blue"></i>Purchase Return
												</a>
											</li>';

											if($this->permissions('purchase_delete'))
											$str2.='<li>
												<a style="cursor:pointer" title="Delete Record ?" onclick="delete_purchase(\''.$purchase->id.'\')">
													<i class="fa fa-fw fa-trash text-red"></i>Delete
												</a>
											</li>
											
										</ul>
									</div>';			

			$row[] = $str2;
			$data[] = $row;
		}

		$output = array(
						"draw" => $_POST['draw'],
						"recordsTotal" => $this->purchase->count_all(),
						"recordsFiltered" => $this->purchase->count_filtered(),
						"data" => $data,
				);
		//output to json format
		echo json_encode($output);
	}
	
	public function delete_purchase(){
		$this->permission_check_with_msg('purchase_delete');
		$id=$this->input->post('q_id');
		echo $this->purchase->delete_purchase($id);
	}
	public function multi_delete(){
		$this->permission_check_with_msg('purchase_delete');
		$ids=implode (",",$_POST['checkbox']);
		echo $this->purchase->delete_purchase($ids);
	}


	//Table ajax code
	public function search_item(){
		$q=$this->input->get('q');
		$result=$this->purchase->search_item($q);
		echo $result;
	}
	public function find_item_details(){
		$id=$this->input->post('id');
		
		$result=$this->purchase->find_item_details($id);
		echo $result;
	}

	//Purchase invoice form
	public function invoice($id)
	{
		$this->belong_to('db_purchase',$id);
		if(!$this->permissions('purchase_add') && !$this->permissions('purchase_edit')){
			$this->show_access_denied_page();
		}
		$data=$this->data;
		$data=array_merge($data,array('purchase_id'=>$id));
		$data['page_title']=$this->lang->line('purchase_invoice');
		$this->load->view('pur-invoice',$data);
	}
	
	//Print Purchase invoice 
	public function print_invoice($purchase_id)
	{
		$this->belong_to('db_purchase',$purchase_id);
		if(!$this->permissions('purchase_add') && !$this->permissions('purchase_edit')){
			$this->show_access_denied_page();
		}
		$data=$this->data;
		$data=array_merge($data,array('purchase_id'=>$purchase_id));
		$data['page_title']=$this->lang->line('purchase_invoice');
		$this->load->view('print-purchase-invoice-2',$data);
	}
	public function pdf($purchase_id)
	{
		$this->belong_to('db_purchase',$purchase_id);
		if(!$this->permissions('purchase_add') && !$this->permissions('purchase_edit')){
			$this->show_access_denied_page();
		}
		$data=$this->data;
		$data=array_merge($data,array('purchase_id'=>$purchase_id));
		$data['page_title']=$this->lang->line('purchase_invoice');
		$this->load->view('print-purchase-invoice-2',$data);

		mb_internal_encoding('UTF-8');

		// Get output html
        $html = $this->output->get_output();

		/*
		 * The PHP development server handles one request at a time. Dompdf
		 * previously tried to fetch every stylesheet and image over HTTP from
		 * that same server, which deadlocked the purchase PDF request. The
		 * invoice already contains its print CSS, so remove the application
		 * stylesheets and embed local invoice images directly in the document.
		 */
		$html = preg_replace('/<link\b[^>]*>/i', '', $html) ?? $html;
		$html = preg_replace(
			'/([\x{0980}-\x{09FF}]+)/u',
			'<span class="pdf-bengali">$1</span>',
			$html
		) ?? $html;
		$bengali_font_path = LEGACY_APPPATH . 'libraries/tcpdf/fonts/NotoSerifBengali-Regular.ttf';
		$bengali_font = is_file($bengali_font_path) ? file_get_contents($bengali_font_path) : false;
		if ($bengali_font !== false) {
			$font_css = '<style>'
				. '@font-face{font-family:"Purchase Bengali";'
				. 'src:url("data:font/truetype;base64,' . base64_encode($bengali_font) . '") format("truetype");'
				. 'font-weight:normal;font-style:normal;}'
				. '.pdf-bengali{font-family:"Purchase Bengali";font-weight:normal;}'
				. '</style>';
			$html = preg_replace('/<\/head>/i', $font_css . '</head>', $html, 1) ?? $html;
		}

		$base_url = rtrim(base_url(), '/') . '/';
		$html = preg_replace_callback(
			'~(<img\b[^>]*\bsrc=["\'])' . preg_quote($base_url, '~') . '([^"\']+)(["\'])~i',
			static function(array $matches): string {
				$relative_path = ltrim(rawurldecode($matches[2]), '/\\');
				$public_root = realpath(FCPATH);
				$image_path = realpath(FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $relative_path));

				if ($public_root === false || $image_path === false
					|| !str_starts_with(strtolower($image_path), strtolower($public_root . DIRECTORY_SEPARATOR))
					|| !is_file($image_path)) {
					return $matches[0];
				}

				$mime_types = [
					'gif' => 'image/gif',
					'jpeg' => 'image/jpeg',
					'jpg' => 'image/jpeg',
					'png' => 'image/png',
					'svg' => 'image/svg+xml',
					'webp' => 'image/webp',
				];
				$extension = strtolower(pathinfo($image_path, PATHINFO_EXTENSION));
				$contents = file_get_contents($image_path);
				if ($contents === false || !isset($mime_types[$extension])) {
					return $matches[0];
				}

				return $matches[1]
					. 'data:' . $mime_types[$extension] . ';base64,' . base64_encode($contents)
					. $matches[3];
			},
			$html
		) ?? $html;
        
        $options = new Options();
		$options->set('isRemoteEnabled', false);
		$options->set('chroot', FCPATH);
		$options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);

        // Load HTML content
        $dompdf->loadHtml($html,'UTF-8');
        
        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', 'portrait');/*landscape or portrait*/
        
        // Render the HTML as PDF
        $dompdf->render();
        
        // Return a framework download response so headers and binary data are
        // preserved by the CodeIgniter 4 legacy dispatcher.
		$file_name = "Purchase-invoice-$purchase_id-".date('M')."_".date('d')."_".date('Y').'.pdf';

		return service('response')->download($file_name, $dompdf->output(), true);
	}


	//Purchase Barcode image
	public function barcode_image($item_code)
	{
		$this->load->library('zend');
	    $this->zend->load('Zend/Barcode');
	    Zend_Barcode::render('code39', 'image', array('text' => $item_code), array());
	}


	public function return_row_with_data($rowcount,$item_id){
		echo $this->purchase->get_items_info($rowcount,$item_id);
	}
	public function return_purchase_list($purchase_id){
		echo $this->purchase->return_purchase_list($purchase_id);
	}
	public function delete_payment(){
		$this->permission_check_with_msg('purchase_payment_delete');
		$payment_id = $this->input->post('payment_id');
		echo $this->purchase->delete_payment($payment_id);
	}

	public function show_pay_now_modal(){
		$this->permission_check_with_msg('purchase_view');
		$purchase_id=$this->input->post('purchase_id');
		echo $this->purchase->show_pay_now_modal($purchase_id);
	}

	public function save_payment(){
		$this->permission_check_with_msg('purchase_add');
		echo $this->purchase->save_payment();
	}
	
	public function view_payments_modal(){
		$this->permission_check_with_msg('purchase_view');
		$purchase_id=$this->input->post('purchase_id');
		echo $this->purchase->view_payments_modal($purchase_id);
	}
	public function get_suppliers_select_list(){
		echo get_suppliers_select_list(null,$_POST['store_id']);
	}

	
}
