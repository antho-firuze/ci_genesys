<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/* THIS IS CLASS FOR BASE CONTROLLER (BACKEND) */
class Getmeb extends CI_Controller
{
	/* DEFAULT TEMPLATE */
	public $theme  	= 'adminlte';
	/* FOR REQUEST METHOD */
	public $r_method;	
	/* FOR CONTROLLER METHOD */
	public $c_method;
	/* FOR THIS METHOD USING WHICH TABLE*/
	public $c_table;
	/* FOR EXCEPTION METHOD */
	public $exception_method = [];
	/* FOR GETTING PARAMS FROM REQUEST URL */
	public $params;
	/* FOR AUTOLOAD MODEL */
	public $mdl;
	/* FOR ADDITIONAL CRUD FIXED DATA */
	public $fixed_data = array();
	public $create_log = array();
	public $update_log = array();
	public $delete_log = array();
	/* FOR GETTING ERROR MESSAGE OR SUCCESS MESSAGE */
	public $messages = array();
	
	/* ========================================= */
	/* This variable for dynamic page lookup		 */
	/* ========================================= */
	public $pageid;
	/* ========================================= */
	/* This variable for CRUD & IMPORT/EXPORT    */
	/* ========================================= */
	/* FOR DEFINED IDENTITY FIELD WHICH CANNOT BE DUPLICATE */
	public $identity_keys = ['name'];
	/* FOR ISOLATED FIELDS WHICH CANNOT BE EXPORT */
	public $protected_fields = [];	// ['user_org_id','user_role_id','api_token','password']
	/* FOR DECLARE MANDATORY IMPORTED FIELDS */
	public $imported_fields = [];		// ['code','name','description']
	/* FOR VALIDATE IDENTITY FIELDS TO MASTER TABLE */
	public $validations = [];				// ['user_id' => 'a_user', 'item_id' => 'm_item']
	
	/* ========================================= */
	/* This variable for UPLOAD & DOWNLOAD files */
	/* ========================================= */
	// public $tmp_dir = APPPATH.'../var/tmp/';
	public $tmp_dir = FCPATH.'var/tmp/';
	public $allow_ext = 'jpg,jpeg,png,gif,xls,xlsx,csv,doc,docx,ppt,pptx,pdf,zip,rar';
	public $max_file_upload = '2mb';
	/* FOR RELATIVE TMP DIRECTORY */
	public $rel_tmp_dir = 'var/tmp/';
	
	function __construct() {
		parent::__construct();
		$this->r_method = $_SERVER['REQUEST_METHOD'];
		$this->c_method = $this->uri->segment(2);
		
		/* Defined for template */
		define('ASSET_URL', base_url().'/assets/');
		define('TEMPLATE_URL', base_url().TEMPLATE_FOLDER.'/backend/'.$this->theme.'/');
		define('TEMPLATE_PATH', '/backend/'.$this->theme.'/');
		
		$this->lang->load('systems/systems', (!empty($this->session->language) ? $this->session->language : 'english'));
		
		$this->fixed_data = [
			'client_id'		=> DEFAULT_CLIENT_ID,
			'org_id'			=> $this->session->org_id,
		];
		$this->create_log = [
			'created_by'	=> (!empty($this->session->user_id) ? $this->session->user_id : '0'),
			'created_at'	=> date('Y-m-d H:i:s')
		];
		$this->update_log = [
			'updated_by'	=> (!empty($this->session->user_id) ? $this->session->user_id : '0'),
			'updated_at'	=> date('Y-m-d H:i:s')
		];
		$this->delete_log = [
			'is_deleted'	=> 1,
			'deleted_by'	=> (!empty($this->session->user_id) ? $this->session->user_id : '0'),
			'deleted_at'	=> date('Y-m-d H:i:s')
		];

		$this->_clear_tmp();
		
		/* This process is a special case, because using multiple r_method (POST and OPTIONS). Request for Import Data */
		// if (isset($this->params['import']) && !empty($this->params['import'])) {
			// /* Check permission in the role */
			// $this->_check_is_allow_inrole('canexport');
		// }


		/* This process is running before checking request method */
		$this->_check_is_login();
		/* This Request for GETTING/VIEWING Data */
		if (in_array($this->r_method, ['GET'])) {
			/* Become Array */
			$this->params = $this->input->get();

			/* Parsing pageid */
			if (isset($this->params['pageid'])) {
				$this->pageid = explode(',', $this->params['pageid']);
				$this->pageid = end($this->pageid);
			}
			
			/* Request for viewlog */
			if (isset($this->params['viewlog']) && !empty($this->params['viewlog'])) {
				/* Check permission in the role */
				$this->_check_is_allow_inrole('canviewlog');
				$this->_get_viewlog();
			}
			
			/* Request for Export Data */
			if (isset($this->params['action']) && !empty($this->params['action'])) {
				switch($this->params['action']) {
					case 'exp':
						/* Check permission in the role */
						$this->_check_is_allow_inrole('canexport');
						break;
					case 'imp':
						/* Check permission in the role */
						$this->_check_is_allow_inrole('canexport');
						break;
				}
			}
			// if (isset($this->params['export']) && !empty($this->params['export'])) {
				// /* Check permission in the role */
				// $this->_check_is_allow_inrole('canexport');
			// }
		}
		
		/* This Request for INDERT & UPDATE Data */
		if (in_array($this->r_method, ['POST','PUT'])) {
			/* Must be checking permission before next process */
			$this->_check_is_allow();
			
			/* Become Object */
			$this->params = json_decode($this->input->raw_input_stream);
			$this->params = count($this->params) > 0 ? $this->params : (object)$_REQUEST;
			
		}
		
		/* This Request for DELETE Data */
		if (in_array($this->r_method, ['DELETE'])) {
			/* Must be checking permission before next process */
			$this->_check_is_allow();

			/* Become Array */
			$this->params = $this->input->get();
			if (! $this->deleteRecords($this->c_table, $this->params['id']))
				$this->xresponse(FALSE, ['message' => $this->messages()], 401);
			else
				$this->xresponse(TRUE, ['message' => $this->messages()]);
		}
		
		/* This Request for EXPORT/IMPORT, PROCESS/REPORT & FORM  */
		if (in_array($this->r_method, ['OPTIONS'])) {
			/* Must be checking permission before next process */
			$this->_check_is_allow();
			
			/* Become Object */
			$this->params = json_decode($this->input->raw_input_stream);
			$this->params = count($this->params) > 0 ? $this->params : (object)$_REQUEST;
			
			/* Request for Export Data */
			if (isset($this->params->export) && !empty($this->params->export)) {
				/* Check permission in the role */
				$this->_check_is_allow_inrole('canexport');
				$this->_pre_export_data();
			}
		}
	}
	
	/* This procedure is for cleaning a tmp file & tmp_tables */
	function _clear_tmp()
	{
		/* Note: 60(sec) x 60(min) x 2-24(hour) x 2~(day) */
		
		/* Check & Execute for every 1 hour */
		if (!empty($cookie = $this->input->cookie('_clear_tmp'))) {
			if ((time()-$cookie) < 60*60) 
				return;
		}
				
		setcookie('_clear_tmp', time());
		if ($handle = @opendir($this->tmp_dir)) {
			while (false !== ($file = @readdir($handle))) {
				if (! preg_match('/^(\.htaccess|index\.(html|htm|php)|web\.config)$/i', $file)) {
					if ((time()-filectime($this->tmp_dir.$file)) > 60*60) {  
						@unlink($this->tmp_dir.$file);
					}
				}
			}
		}
		
		/* Cleaning tmp_tables */
		$qry = $this->db->get_where('a_tmp_tables', ['time <' => time()-60*60]);
		if ($qry->num_rows() > 0){
			$this->load->dbforge();
			foreach($qry->result() as $k => $v){
				$this->dbforge->drop_table($v->name,TRUE);
			}
			$this->db->where('time <', time()-60*60, FALSE);
			$this->db->delete('a_tmp_tables');
		}
	}
	
	function _check_menu($data=[])
	{
		/* CHECK METHOD */
		if (empty($data['method'])) {
			$this->set_message('ERROR: Menu [method] is could not be empty !');
			return FALSE;
		}
		
		/* CHECK PATH FILE */
		if (!$this->_check_path($data['path'].$data['method'])) {
			$this->set_message('ERROR: Menu [path] is could not be found or file not exist !');
			return FALSE;
		}
		
		if (key_exists('edit', $this->params) && !empty($this->params['edit'])) {
			if (!$this->_check_path($data['path'].$data['method'].'_edit')) {
				$this->set_message('ERROR: Page or File ['.$data['path'].$data['method'].'_edit'.'] is could not be found or file not exist !');
				return FALSE;
			}
		}
		
		/* CHECK CLASS/CONTROLLER */
		if (!$this->_check_class($data['class'])) {
			$this->set_message('ERROR: Menu [class] is could not be found or file not exist !');
			return FALSE;
		}
		
		return TRUE;
	}
	
	function _check_path($path)
	{
		return file_exists(APPPATH.'../'.TEMPLATE_FOLDER.'/backend/'.$this->theme.'/'.$path.'.tpl') ? TRUE : FALSE;
	}
	
	function _check_class($class)
	{
		return file_exists(APPPATH.'modules/'.$class.'/controllers/'.$class.'.php') ? TRUE : FALSE;
	}
	
	function _check_is_login()
	{
		/* This process is for bypass methods which do not need to login */
		if (count($this->exception_method) > 0){
			if (in_array($this->c_method, $this->exception_method)){
				return TRUE;
			}
		}
		
		/* Check the session data for user_id */
		if (!$this->session->userdata('user_id')) {
			/* set reference url to session */
			setURL_Index();
			/* forward to login page */
			// $this->x_login();
			redirect(LOGIN_LNK);
			exit();
		}
		return TRUE;
	}
	
	function _check_is_allow()
	{
		/* This process is for bypass methods which do not need to login */
		if (count($this->exception_method) > 0){
			if (in_array($this->c_method, $this->exception_method))
				return;
		}
		
		/* Check menu existance on the table a_menu */
		if ($this->pageid)
			$menu = $this->base_model->getValueArray('*', 'a_menu', ['client_id','id'], [DEFAULT_CLIENT_ID, $this->pageid]);
		else
			$menu = $this->base_model->getValueArray('*', 'a_menu', ['client_id','method'], [DEFAULT_CLIENT_ID, $this->c_method]);
		
		if (!$menu)
			$this->backend_view('pages/404', ['message' => 'Menu not found !']);
		
		/* Set this menu using this table */
		$this->c_table = $menu['table'];

		/* Check menu active & permission on the table a_role_menu */
		$allow = $this->base_model->getValue('permit_form, permit_process, permit_window', 'a_role_menu', ['role_id', 'menu_id', 'is_active', 'is_deleted'], [$this->session->role_id, $menu['id'], '1', '0']);
		if (!$allow)
			$this->backend_view('pages/unauthorized', ['message' => sprintf('Permission [%s] <b>not found</b> or <b>not active</b> in [a_role_menu] !', $menu['name'])]);
		
		/* Permission for view */
		if ($this->r_method == 'GET' && $allow)
			return $menu;
		
		if ($menu['type'] == 'F') {
			switch($allow->permit_form){
			case '1':
				/* Execute */
				if (!in_array($this->r_method, ['OPTIONS']))
					$this->xresponse(FALSE, ['message' => $this->lang->line('permission_failed_crud')], 401);
					// $this->backend_view('pages/unauthorized', ['message' => '']);
				break;
			default:
				$this->xresponse(FALSE, ['message' => $this->lang->line('permission_failed_crud'), 'note' => sprintf('Permission [%s] is not set !', $menu['name'])], 401);
				// $this->backend_view('pages/unauthorized', ['message' => sprintf('Permission [%s] is not set !', $menu->name)]);
				break;
			}
		}
		if ($menu['type'] == 'P') {
			switch($allow->permit_process){
			case '1':
				/* Export */
				if (!in_array($this->r_method, ['OPTIONS']))
					$this->xresponse(FALSE, ['message' => $this->lang->line('permission_failed_crud')], 401);
					// $this->backend_view('pages/unauthorized', ['message' => '']);
				break;
			default:
				$this->xresponse(FALSE, ['message' => $this->lang->line('permission_failed_crud'), 'note' => sprintf('Permission [%s] is not set !', $menu['name'])], 401);
				// $this->backend_view('pages/unauthorized', ['message' => sprintf('Permission [%s] is not set !', $menu['name'])]);
				break;
			}
		}
		if ($menu['type'] == 'W') {
			switch($allow->permit_window){
			case '1':
				/* Only Create */
				if (!in_array($this->r_method, ['POST']))
					$this->xresponse(FALSE, ['message' => $this->lang->line('permission_failed_crud')], 401);
					// $this->backend_view('pages/unauthorized', ['message' => '']);
				break;
			case '2':
				/* Only Edit */
				if (!in_array($this->r_method, ['PUT']))
					$this->xresponse(FALSE, ['message' => $this->lang->line('permission_failed_crud')], 401);
					// $this->backend_view('pages/unauthorized', ['message' => '']);
				break;
			case '3':
				/* Only Delete */
				if (!in_array($this->r_method, ['DELETE']))
					$this->xresponse(FALSE, ['message' => $this->lang->line('permission_failed_crud')], 401);
					// $this->backend_view('pages/unauthorized', ['message' => '']);
				break;
			case '4':
				/* Can Create & Edit */
				if (!in_array($this->r_method, ['POST','PUT']))
					$this->xresponse(FALSE, ['message' => $this->lang->line('permission_failed_crud')], 401);
					// $this->backend_view('pages/unauthorized', ['message' => '']);
				break;
			case '5':
				/* Can Create & Delete */
				if (!in_array($this->r_method, ['POST','DELETE']))
					$this->xresponse(FALSE, ['message' => $this->lang->line('permission_failed_crud')], 401);
					// $this->backend_view('pages/unauthorized', ['message' => '']);
				break;
			case '6':
				/* Can Edit & Delete */
				if (!in_array($this->r_method, ['PUT','DELETE']))
					$this->xresponse(FALSE, ['message' => $this->lang->line('permission_failed_crud')], 401);
					// $this->backend_view('pages/unauthorized', ['message' => '']);
				break;
			case '7':
				/* Can All */
				if (!in_array($this->r_method, ['POST','PUT','DELETE']))
					$this->xresponse(FALSE, ['message' => $this->lang->line('permission_failed_crud')], 401);
					// $this->backend_view('pages/unauthorized', ['message' => '']);
				break;
			default:
				$this->xresponse(FALSE, ['message' => $this->lang->line('permission_failed_crud'), 'note' => sprintf('Permission [%s] is not set !', $menu['name'])], 401);
				// $this->backend_view('pages/unauthorized', ['message' => sprintf('Permission [%s] is not set !', $menu['name'])]);
				break;
			}
		}
		return $menu;
	}
	
	function _check_is_allow_inrole($permit)
	{
		$role = $this->base_model->getValue('*', 'a_role', 'id', $this->session->role_id);
		switch($permit){
			case 'canviewlog':
				if (!$role->is_canviewlog)
					$this->backend_view('pages/unauthorized', ['message'=>'You are not authorized !']);
					// $this->xresponse(FALSE, ['message' => $this->lang->line('permission_failed_crud')]);
				break;
			case 'canexport':
				if (!$role->is_canexport)
					$this->backend_view('pages/unauthorized', ['message'=>'You are not authorized !']);
					// $this->xresponse(FALSE, ['message' => $this->lang->line('permission_failed_crud')]);
				break;
			case 'canapproveowndoc':
				if (!$role->is_canapproveowndoc)
					$this->backend_view('pages/unauthorized', ['message'=>'You are not authorized !']);
					// $this->xresponse(FALSE, ['message' => $this->lang->line('permission_failed_crud')]);
				break;
			case 'canreport':
				if (!$role->is_canreport)
					$this->backend_view('pages/unauthorized', ['message'=>'You are not authorized !']);
					// $this->xresponse(FALSE, ['message' => $this->lang->line('permission_failed_crud')]);
				break;
		}
	}
	
	function _get_viewlog()
	{
		$result = [];
		$result['table'] = $this->c_method;
		$result['id'] = $this->params['id'];
		if ($info = $this->base_model->getValue('created_by, created_at, updated_by, updated_at, deleted_by, deleted_at', $this->c_method, 'id', $this->params['id'])){
			if ($info->created_by){
				if ($user = $this->base_model->getValue('id, name', 'a_user', 'id', $info->created_by)) {
					$result['created_by'] 		 = $user->id;
					$result['created_at'] 		 = $info->created_at;
					$result['created_by_name'] = $user->name;
				}
			}
			if ($info->updated_by){
				if ($user = $this->base_model->getValue('id, name', 'a_user', 'id', $info->updated_by)) {
					$result['updated_by'] 		 = $user->id;
					$result['updated_at'] 		 = $info->updated_at;
					$result['updated_by_name'] = $user->name;
				}
			}
			if ($info->deleted_by){
				if ($user = $this->base_model->getValue('id, name', 'a_user', 'id', $info->deleted_by)) {
					$result['deleted_by'] 		 = $user->id;
					$result['deleted_at'] 		 = $info->deleted_at;
					$result['deleted_by_name'] = $user->name;
				}
			}
		}
		$this->xresponse(TRUE, ['data' => $result]);
	}
	
	function _get_filtered($client = TRUE, $org = TRUE)
	{
		if (isset($this->params['id']) && !empty($this->params['id'])) 
			$this->params['where']['t1.id'] = $this->params['id'];
		
		if (isset($this->params['q']) && !empty($this->params['q']))
			$this->params['like'] = DBX::like_or('t1.name, t1.description', $this->params['q']);
		
		if ($client)
			$this->params['where']['t1.client_id'] = $this->session->client_id;

		if ($org)
			$this->params['where']['t1.org_id'] = $this->session->org_id;
	}
	
	function remove_empty($array) {
		return array_filter($array, function($value){
			return !empty($value) || $value === 0;
		});
	}

	function _pre_update_records($return = FALSE)
	{
		$datas = [];
		$fields = $this->db->list_fields($this->c_table);
		foreach($fields as $f){
			if (key_exists($f, $this->params)){
				/* Check if any exists allow null fields */
				$datas[$f] = ($this->params->{$f} == '') ? NULL : $this->params->{$f}; 
				
				/* Check if any exists boolean fields */
				/* if (in_array($f, $this->boolfields)){
					$datas[$f] = empty($this->params->{$f}) ? '0' : '1'; 
				}  */
				/* Check if any exists allow null fields */
				/* elseif (in_array($f, $this->nullfields)){
					$datas[$f] = ($this->params->{$f}=='') ? NULL : $this->params->{$f}; 
				} else {
					$datas[$f] = $this->params->{$f};
				} */
			}
		}
		
		if ($return) 
			return $datas;
			
		$this->_go_update_records($datas);
	}
	
	function _go_update_records($datas)
	{
		if ($this->r_method == 'POST')
			$result = $this->insertRecord($this->c_table, $datas, TRUE, TRUE);
		else
			$result = $this->updateRecord($this->c_table, $datas, ['id'=>$this->params->id], TRUE);				
		
		if (! $result)
			$this->xresponse(FALSE, ['message' => $this->messages()], 401);

		if ($this->r_method == 'POST')
			$this->xresponse(TRUE, ['id' => $result, 'message' => $this->messages()]);
		else
			$this->xresponse(TRUE, ['message' => $this->messages()]);
	}
	
	function _upload_file()
	{
		/* get the params & files (special for upload file) */
		$files = $_FILES;
		
		$this->max_file_upload = isset($this->session->max_file_upload) ? $this->session->max_file_upload : $this->max_file_upload;
		
		@ini_set( 'upload_max_size' , $this->max_file_upload );
		@ini_set( 'post_max_size', $this->max_file_upload );
		@ini_set( 'max_execution_time', '300' );
		
		if ($this->r_method == 'POST') {
			if (isset($files['file']['name']) && $files['file']['name']) {
				/* Load the library */
				require_once APPPATH."/third_party/Plupload/PluploadHandler.php"; 
				$ph = new PluploadHandler(array(
					'target_dir' => $this->tmp_dir,
					'allow_extensions' => $this->allow_ext
				));
				$ph->sendNoCacheHeaders();
				$ph->sendCORSHeaders();
				/* And Do Upload */
				if (!$result = $ph->handleUpload()) {
					$this->set_message($ph->getErrorMessage());
					return FALSE;
				}
				/* Result Output in array : array('name', 'path', 'chunk', 'size') */
				return $result;
			}
		}
	}
	
	function _pre_export_data($return = FALSE)
	{
		$filetype = $this->params['filetype'];
		$filename = $this->c_method.'_'.date('YmdHi').'.'.$filetype;
		$is_compress = $this->params['is_compress'];
		/* Parsing pageid, if on sub module */
		$this->pageid = explode(',', $this->params['pageid']);
		$this->pageid = end($this->pageid);
		
		/* Get the Table */
		$menu = $this->base_model->getValue('*', 'a_menu', ['client_id','id'], [DEFAULT_CLIENT_ID, $this->pageid]);
		if (!$menu)
			$this->xresponse(FALSE, ['message' => $this->lang->line('export_failed'), 'note' => '[pageid='.$this->pageid.'] is not exists on [a_menu]'], 401);

		if (!$this->db->table_exists($menu->table))
			$this->xresponse(FALSE, ['message' => $this->lang->line('export_failed'), 'note' => '[pageid='.$this->pageid.'][table='.$menu->table.'] does not exists'], 401);

		$protected_fields = ['id','client_id','org_id','is_deleted','created_by','updated_by','deleted_by','created_at','updated_at','deleted_at'];
		$fields = $this->db->list_fields($menu->table);
		$fields = array_diff($fields, array_merge($protected_fields, $this->protected_fields));
		$select = implode(',', $fields);
		
		$this->params['export'] = 1;
		$this->params['select'] = $select;
		if (! $result = $this->{$this->mdl}->{'get_'.$this->c_method}($this->params)){
			$result['data'] = [];
			$result['message'] = $this->base_model->errors();
			$this->xresponse(FALSE, $result);
		}

		if ($return)
			return $result;
		
		/* Export the datas */
		if (! $result = $this->_export_data($result, $filename, $filetype, TRUE))
			$this->xresponse(FALSE, ['message' => 'export_data_failed']);
		
		/* Compress the file */
		if ($is_compress) 
			if(! $result = $this->_compress_file($result['filepath']))
				$this->xresponse(FALSE, ['message' => 'compress_file_failed']);
			
		$this->xresponse(TRUE, $result);
	}
	
	function _export_data($qry, $filename, $filetype, $return = FALSE)
	{
		ini_set('memory_limit', '-1');
		$this->load->library('z_libs/Excel');
		$objPHPExcel = new PHPExcel();
		$objPHPExcel->getProperties()->setTitle("export")->setDescription("none");
 
		$objPHPExcel->setActiveSheetIndex(0);
		
		// Set the Title in the first row
		$current = 'A';
		$col = 0;
		foreach ($qry->list_fields() as $field) {
			$columns[] = ($col == 0) ? $current : ++$current;
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, 1, $field);
			$col++;
		}

		// Set the Data in the next row
		$row = 2;
		foreach($qry->result() as $data) {
			$col = 0;
			foreach ($qry->list_fields() as $field) {
				$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $row, $data->$field);
				$col++;
			}
			$row++;
		}
		
		// Set the Column to Fit AutoSize
		foreach($columns as $column) {
			$objPHPExcel->getActiveSheet()->getColumnDimension($column)->setAutoSize(true);
		}
		
		if ($filetype == 'xls') {
			if ($return){
				$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
				$objWriter->save($this->tmp_dir.$filename);
				return ['filename' => $filename, 'filepath' => $this->tmp_dir.$filename, 'file_url' => BASE_URL.$this->rel_tmp_dir.$filename];
			}

			$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
			$objWriter->save('php://output');
		}
		if ($filetype == 'csv'){
			PHPExcel_Shared_String::setDecimalSeparator('.');
			PHPExcel_Shared_String::setThousandsSeparator(',');

			if ($return){
				$objWriter = new PHPExcel_Writer_CSV($objPHPExcel);
				$objWriter->save($this->tmp_dir.$filename);
				return ['filename' => $filename, 'filepath' => $this->tmp_dir.$filename, 'file_url' => BASE_URL.$this->rel_tmp_dir.$filename];
			}
			
			$objWriter = new PHPExcel_Writer_CSV($objPHPExcel);
			$objWriter->save('php://output');
			
		}
		if ($filetype == 'pdf'){
			$rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
			$rendererLibraryPath = FCPATH.'../vendor/mpdf/mpdf/src/';
			if (!PHPExcel_Settings::setPdfRenderer($rendererName,	$rendererLibraryPath)) {
					die(
							'Please set the $rendererName and $rendererLibraryPath values' .
							PHP_EOL .
							' as appropriate for your directory structure'
					);
			}
			if ($return){
				$objWriter = new PHPExcel_Writer_PDF($objPHPExcel);
				$objWriter->save($this->tmp_dir.$filename);
				return ['filename' => $filename, 'filepath' => $this->tmp_dir.$filename, 'file_url' => BASE_URL.$this->rel_tmp_dir.$filename];
			}
			$objWriter = new PHPExcel_Writer_PDF($objPHPExcel);
			$objWriter->save('php://output');
		}
		if ($filetype == 'html'){
			if ($return){
				$objWriter = new PHPExcel_Writer_HTML($objPHPExcel);
				$objWriter->save($this->tmp_dir.$filename);
				return ['filename' => $filename, 'filepath' => $this->tmp_dir.$filename, 'file_url' => BASE_URL.$this->rel_tmp_dir.$filename];
			}
			
			$objWriter = new PHPExcel_Writer_HTML($objPHPExcel);
			$objWriter->save('php://output');
		}
		exit;
	}
	
	function _compress_file($file)
	{
		$zip = new ZipArchive();
		$pathinfo = pathinfo($file);
		$dir = $pathinfo['dirname'];
		$fil = $pathinfo['filename'];
		$fbn = $pathinfo['basename'];
		$ext = strtolower($pathinfo['extension']);
		$filezip = $fil.'.zip';
		$fil_tmp = $dir.'/'.$filezip;
		if ($zip->open($fil_tmp, ZipArchive::CREATE)!==TRUE) {
			exit("cannot open <$fil_tmp>\n");
		}
		$zip->addFile($file,$fbn);
		$zip->close();
		/* remove master file */
		@unlink($file);
		return ['filename' => $filezip, 'filepath' => $this->rel_tmp_dir.$filezip, 'file_url' => BASE_URL.$this->rel_tmp_dir.$filezip];
	}
	
	function _get_menu_child($parent_id, $menu = array(), $active_only = TRUE)
	{
		$active_only = $active_only ? "and is_active = '1'" : "";
		$str = "select * from a_menu where is_submodule = '0' $active_only and is_deleted = '0' and parent_id = $parent_id order by is_parent desc, line_no";
		$qry = $this->db->query($str);
		foreach($qry->result() as $k => $v){
			$menu[] = $v;
			$menu = $this->_get_menu_child($v->id, $menu);
		}
		return $menu;
	}
	
	function _get_menu($active_only = TRUE)
	{
		$menu = [];
		/* get menu level 0 : not include dashboard (id <> 1)*/
		$active_only = $active_only ? "and is_active = '1'" : "";
		$str = "select * from (
			select * from a_menu where is_parent = '1' and is_submodule = '0' $active_only and is_deleted = '0' and (parent_id = 0 or parent_id is null)
			union
			select * from a_menu where is_parent = '0' and is_submodule = '0' $active_only and is_deleted = '0' and (parent_id = 0 or parent_id is null) and id <> 1
		) as lvl0 order by is_parent desc, line_no";
		$qry = $this->db->query($str);
		foreach($qry->result() as $k => $v){
			$menu[] = $v;
			$menu = $this->_get_menu_child($v->id, $menu);
		}
		return $menu;
	}
	
	function _reorder_menu()
	{
		$line = 1; $lineh = 1; $parent_id = -1;
		foreach($this->_get_menu(FALSE) as $k => $v){
			if ($v->is_parent == 1){
				if ($parent_id != $v->parent_id){
					$line = 1;
					$lineh = 1;
				}
				$this->db->update('a_menu', ['line_no' => $lineh], ['id' => $v->id]);
				$lineh++;
				$parent_id = $v->parent_id;
				continue;
			}
			$this->db->update('a_menu', ['line_no' => $line], ['id' => $v->id]);
			$line++;
		}
	}

	function _import_data()
	{
		if (isset($this->params->step) && $this->params->step == '1') {
			/* Received the file to be import */
			if (!$result = $this->_upload_file()){
				/* Upload file failed ! */
				return FALSE;
			}
			
			/* Check file type */
			$this->load->library('z_libs/Excel');
			/**  Identify the type of $inputFileName  **/
			$inputFileType = PHPExcel_IOFactory::identify($result["path"]);
			/**  Create a new Reader of the type that has been identified  **/
			$objReader = PHPExcel_IOFactory::createReader($inputFileType);
			/**  Load $inputFileName to a PHPExcel Object  **/
			$objPHPExcel = $objReader->load($result["path"]);
			/**  Convert object to array and populate to variable  **/
			$sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
			
			if (!$tmp_table = $this->session->tmp_table) {
				/* Create random filename for tmp_table */
				$this->load->helper('string');
				$tmp_table = "z_".random_string('alnum', 5);
				$this->session->set_userdata(['tmp_table' => $tmp_table]);
			} 
			
			/* Drop table if exists  */
			$this->load->dbforge();
			$this->dbforge->drop_table($tmp_table,TRUE);
			
			/* Process for parsing data_sheet (csv & xls) file */
			foreach($sheetData as $key => $values){
				if ($key == 1){
					/* Row #1, for header/title */
					$fields['tmp_id'] = ['type' => 'INT', 'constraint' => 9, 'auto_increment' => TRUE];
					foreach($values as $k => $v){
						$fn = $values[$k] ? $v : $k;
						$title[$k] = $fn;
						$fields[$fn] = ['type' => 'VARCHAR', 'constraint' => '100', 'null' => TRUE];
					}
					$fields['status'] = ['type' => 'text', 'null' => TRUE];
					$this->dbforge->add_field($fields);
					if (! $result = $this->dbforge->create_table($tmp_table)){
						$this->set_message('no_header_fields');
						return FALSE;
					}
					// debug($fields);
				} else {
					/* Next, Row #2 until end is value */
					foreach($values as $k => $v){
						$val[$title[$k]] = !empty($v) && $v && $v != '' ? $v : NULL;
					}
					$this->db->insert($tmp_table, $val);
				}
			}
			/* Adding table name & creation date to a_tmp_tables */
			$this->db->delete('a_tmp_tables', ['name' => $tmp_table]);
			$this->db->insert('a_tmp_tables', ['name' => $tmp_table, 'created_at' => date('Y-m-d H:i:s'), 'time' => time()]);
			/* Getting fields from tmp_table */
			$tmp_fields = $this->db->list_fields($tmp_table);
			$tmp_fields = array_diff($tmp_fields, ['tmp_id', 'status']);
			return ['tmp_fields' => array_values($tmp_fields), 'table_fields' => $this->imported_fields];
		}
		
		if (isset($this->params->step) && $this->params->step == '2') {
			/* fields syncronization with target table */
			$tmp_fields = $this->db->list_fields($this->session->tmp_table);
			$params_flip = array_flip($this->params->fields);
			foreach($tmp_fields as $k => $v){
				if (isset($params_flip[$v]))
					$tmp_fields[$k] = $v . ' as ' . $params_flip[$v];
			}

			/* Select fields with new alias */
			$this->db->select($tmp_fields);
			$qry = $this->db->get($this->session->tmp_table);
			if($qry->num_rows() > 0){
				foreach($qry->result_array() as $key => $values){
					$tmp_id = ['tmp_id' => $values['tmp_id']];
					unset($values['tmp_id'], $values['status'], $values['id']);
					
					/* ============================ Insert cluster ============================ */
					if ($this->params->importtype == 'insert') {
						
						/* Validation rule */
						if ($this->validations){
							$is_valid = true;
							foreach($this->validations as $k => $v) {
								if ($this->db->where('id', $values[$k])->get($v)->num_rows() < 1) {
									$this->db->update($this->session->tmp_table, ['status' => sprintf("[%s = %s] doesn't exists on table [%s]", $k, $values[$k], $v)], $tmp_id);
									$is_valid = false;
								}
								$this->db->flush_cache();
							}
							if (!$is_valid) 
								continue;
						}
						
						
						/* Start the Insert Process */
						if (!$result = $this->insertRecord($this->c_method, $values, TRUE, TRUE)) {
							$this->db->update($this->session->tmp_table, ['status' => $this->messages(FALSE)], $tmp_id);
						}
					} 
					
					/* ============================ Update cluster ============================ */
					if ($this->params->importtype == 'update') {
						
						/* Build identity_keys, this is a mandatory for update query */
						if ($this->identity_keys){
							$val = [];
							foreach($this->identity_keys as $k => $v){
								if (isset($values[$v])){
									$val[$v] = $values[$v];
								}
							}
						} else {
							$this->set_message('Failed: Method ['.$this->c_method.'] the identity_keys was not set !');
							return FALSE;
						}
							
						/* Check existing record in target table */
						$fk = $this->db->get_where($this->c_method, array_merge($val, ['is_active' => '1', 'is_deleted' => '0']), 1);
						if ($fk->num_rows() < 1){

							$this->db->update($this->session->tmp_table, ['status' => 'This line is not exist !'], $tmp_id);
						} else {

							/* Start the Update Process */
							if (!$result = $this->updateRecord($this->c_method, $values, array_merge($val, ['is_active' => '1', 'is_deleted' => '0']), TRUE)) {
								$this->db->update($this->session->tmp_table, ['status' => $this->messages(FALSE)], $tmp_id);
							}
						}
					}
				}
					
				/* Export the result to CSV and throw to client */
				$filename = 'result_'.$this->c_method.'_'.date('YmdHi').'.'.$this->params->filetype;
				$fields = $this->db->list_fields($this->session->tmp_table);
				$fields = array_diff($fields, ['tmp_id']);
				$this->db->select($fields);
				$qry = $this->db->get($this->session->tmp_table);
				if (! $result = $this->_export_data($qry, $filename, $this->params->filetype, TRUE)) {
					$this->set_message('export_data_failed');
					return FALSE;
				}
				
				return $result;
			}
		}
	}
	
	function set_message($message, $func=NULL, $args=NULL)
	{
		$msg = $this->lang->line($message) ? $this->lang->line($message) : '##' . $message . '##';
		
		if (!empty($args)){
			$args = is_array($args) ? 
				str_replace('+', ' ', http_build_query($args,'',', ')) : 
				$args;
			$args = sprintf('Context : <br> function %s(), [%s]', $func, $args);
			$msg = sprintf('%s<br><br>%s', $msg, $args);
		}
		$this->messages[] = $msg;
		return $message;
	}

	function messages($use_p = TRUE)
	{
		$_output = '';
		foreach ($this->messages as $message)
		{
			if ($use_p)
				$_output .= '<p>' . $message . '</p>';
			else
				$_output .= $message.' ';
		}
		$this->messages = [];
		return $_output;
	}

	function insertRecord($table, $data, $fixed_data = FALSE, $create_log = FALSE)
	{
		$data = is_object($data) ? (array) $data : $data;
		$data = $fixed_data ? array_merge($data, $this->fixed_data) : $data;
		$data = $create_log ? array_merge($data, $this->create_log) : $data;

		if (key_exists('id', $data)) 
			unset($data['id']);

		if ($this->identity_keys){
			$val = [];
			foreach($this->identity_keys as $k => $v){
				if (isset($data[$v])){
					$val[$v] = $data[$v];
				}
			}

			if (count($val) > 0) {
				if (! $fk = $this->db->get_where($table, array_merge($val, ['is_deleted' => '0']), 1)) {
					$this->set_message($this->db->error()['message']);
					return FALSE;
				}
				// debug($this->db->last_query());
				if ($fk->num_rows() > 0){
					// $this->set_message('error_identity_keys', __FUNCTION__, $val);
					$this->set_message('error_identity_keys');
					return false;
				}
			}
		}

		if (!$return = $this->db->insert($table, $data)) {
// debug($return);
// debug($this->db->error()['message']);
			$this->set_message($this->db->error()['message']);
			return false;
		} else {
			$id = $this->db->insert_id();
// debug($this->db->last_query());
// debug($return);
			$this->set_message('success_saving');
			return $id;
		}
	}
	
	function updateRecord($table, $data, $cond, $update_log = FALSE)
	{
		$data = is_object($data) ? (array) $data : $data;
		$data = $update_log ? array_merge($data, $this->update_log) : $data;
		
		$cond = is_object($cond) ? (array) $cond : $cond;

		if (isset($data['id'])) 
			unset($data['id']);
		
		if (!$return = $this->db->update($table, $data, $cond)) {
			$this->set_message($this->db->error()['message']);
			return false;
		} else {
			$this->set_message('success_update');
			return true;
		}
		
		/* $this->db->update($table, $data, $cond);
		$return = $this->db->affected_rows() == 1;
		if ($return)
			// $this->set_message('update_data_successful');
			$this->set_message('success_update');
		else
			$this->set_message('update_data_unsuccessful');
		
		return true; */
	}
	
	function deleteRecords($table, $ids, $real = FALSE)
	{
		$ids = array_filter(array_map('trim',explode(',',$ids)));
		$return = 0;
		foreach($ids as $v)
		{
			if ($real) {
				if ($this->db->delete($table, ['user_id'=>$v]))
				{
					$return += 1;
				}
			} else {
				if ($this->db->update($table, $this->delete_log, ['id'=>$v]))
				{
					$return += 1;
				}
			}
		}
		if ($return)
			$this->set_message('success_delete');
		else
			$this->set_message($this->db->error()['message']); 
			
		return $return;
	}
	
	function xresponse($status=TRUE, $response=array(), $statusHeader=200)
	{
		$BM =& load_class('Benchmark', 'core');
		
		$statusHeader = empty($statusHeader) ? 200 : $statusHeader;
		if (! is_numeric($statusHeader))
			show_error('Status codes must be numeric', 500);
		
		$elapsed = $BM->elapsed_time('total_execution_time_start', 'total_execution_time_end');

		$output['status'] = $status;
		$output['execution_time'] = $elapsed;
		$output['environment'] = ENVIRONMENT;
		
		header("HTTP/1.0 $statusHeader");
		header('Content-Type: application/json');
		echo json_encode(array_merge($output, $response));
		exit();
	}
	
	function _getMenuByRoleId($role_id)
	{
		if ($role_id) {
			/* $query = "select 
						am1.id as menu_id1, am1.name as name1, am1.is_parent as is_parent1, am1.icon as icon1, am1.type as type1,
						am2.id as menu_id2, am2.name as name2, am2.is_parent as is_parent2, am2.icon as icon2, am2.type as type2,
						am3.id as menu_id3, am3.name as name3, am3.is_parent as is_parent3, am3.icon as icon3, am3.type as type3
			from (
				select * from a_menu am where am.is_submodule = '0' and am.is_active = '1' and am.is_deleted = '0' and (am.parent_id = '0' or am.parent_id is null) and 
				exists (
					select * from (
						select am.id, am.name, am.is_parent, am.line_no, am.icon, am.type, am.parent_id, arm.role_id
						from a_menu am
						left join a_role_menu arm on am.id = arm.menu_id and arm.is_active = '1' and arm.is_deleted = '0' and arm.role_id = $role_id
						where am.is_parent = '0' and am.is_submodule = '0' and am.is_active = '1' and am.is_deleted = '0' and arm.role_id is not null
					) basemenu where role_id is not null and parent_id = am.id
				)
			) am1
			left join (
				select am.id, am.name, am.is_parent, am.line_no, am.icon, am.type, am.parent_id, arm.role_id
				from a_menu am
				left join a_role_menu arm on am.id = arm.menu_id and arm.is_active = '1' and arm.is_deleted = '0' and arm.role_id = $role_id
				where am.is_parent = '0' and am.is_submodule = '0' and am.is_active = '1' and am.is_deleted = '0' and arm.role_id is not null
			) am2 on am1.id = am2.parent_id and am2.role_id is not null
			left join (
				select am.id, am.name, am.is_parent, am.line_no, am.icon, am.type, am.parent_id, arm.role_id
				from a_menu am
				left join a_role_menu arm on am.id = arm.menu_id and arm.is_active = '1' and arm.is_deleted = '0' and arm.role_id = $role_id
				where am.is_parent = '0' and am.is_submodule = '0' and am.is_active = '1' and am.is_deleted = '0' and arm.role_id is not null
			) am3 on am2.id = am3.parent_id and am3.role_id is not null
			order by am1.line_no, am2.line_no, am3.line_no"; */
			$query = "select 
						am1.id as menu_id1, am1.name as name1, am1.is_parent as is_parent1, am1.icon as icon1, am1.type as type1,
						am2.id as menu_id2, am2.name as name2, am2.is_parent as is_parent2, am2.icon as icon2, am2.type as type2,
						am3.id as menu_id3, am3.name as name3, am3.is_parent as is_parent3, am3.icon as icon3, am3.type as type3
			from (
				select * from a_menu am where am.is_submodule = '0' and am.is_active = '1' and am.is_deleted = '0' and is_parent = '1' and (am.parent_id = '0' or am.parent_id is null) and 
				exists (
					select * from (
						select am.id, am.name, am.is_parent, am.line_no, am.icon, am.type, am.parent_id, arm.role_id
						from a_menu am
						left join a_role_menu arm on am.id = arm.menu_id and arm.is_active = '1' and arm.is_deleted = '0' and arm.role_id = $role_id
						where am.is_parent = '0' and am.is_submodule = '0' and am.is_active = '1' and am.is_deleted = '0' and arm.role_id is not null
					) basemenu where role_id is not null and parent_id = am.id
				)
			) am1
			left join (
				select am.id, am.name, am.is_parent, am.line_no, am.icon, am.type, am.parent_id, arm.role_id
				from a_menu am
				left join a_role_menu arm on am.id = arm.menu_id and arm.is_active = '1' and arm.is_deleted = '0' and arm.role_id = $role_id
				where am.is_parent = '0' and am.is_submodule = '0' and am.is_active = '1' and am.is_deleted = '0' and arm.role_id is not null
				union 
				select am.id, am.name, am.is_parent, am.line_no, am.icon, am.type, am.parent_id, $role_id as role_id
				from a_menu am
				where am.is_parent = '1' and am.parent_id <> '0' and am.is_submodule = '0' and am.is_active = '1' and am.is_deleted = '0'
			) am2 on am1.id = am2.parent_id and am2.role_id is not null
			left join (
				select am.id, am.name, am.is_parent, am.line_no, am.icon, am.type, am.parent_id, arm.role_id
				from a_menu am
				left join a_role_menu arm on am.id = arm.menu_id and arm.is_active = '1' and arm.is_deleted = '0' and arm.role_id = $role_id
				where am.is_parent = '0' and am.is_submodule = '0' and am.is_active = '1' and am.is_deleted = '0' and arm.role_id is not null
			) am3 on am2.id = am3.parent_id and am3.role_id is not null
			order by am1.line_no, am2.line_no, am3.line_no";
			
			$row = $this->db->query($query);
			return ($row->num_rows() > 0) ? $row->result() : FALSE;
		}
		return FALSE;
	}
	
	/**
	 * li
	 *
	 * Function for left menu on backend <li></li>
	 *
	 * @param	string	$cur_page   Current page
	 * @param	string	$page_chk   Page check
	 * @param	string	$url   Url
	 * @param	string	$menu_name   Menu label
	 * @param	string	$icon   bootstrap glyphicon class
	 * @param	string	$submenu   Submenu (TRUE or FALSE)
	 * @return  string
	 */
	private function li($cur_page, $page_chk, $url, $menu_name, $icon)
	{
		$active = ($cur_page == $page_chk) ? ' class="active"' : '';
		$glyp_icon = ($icon) ? '<i class="'.$icon.'"></i> ' : '<i class="fa fa-circle"></i>';
		
		$html = '<li'.$active.'><a href="'.base_url().''.$url.'">'.$glyp_icon.'<span>'.$menu_name.'</span></a></li>';
		return $html;
	}
	
	private function li_parent($cur_page, $page_chk, $url, $menu_name, $icon)
	{
		$active = ($cur_page == $page_chk) ? ' class="treeview active"' : ' class="treeview"';
		$glyp_icon = ($icon) ? '<i class="'.$icon.'"></i> ' : '<i class="glyphicon glyphicon-menu-hamburger"></i>';
		
		$html= '<li'.$active.'><a href="'.base_url().''.$url.'">'.$glyp_icon.'<span>'.$menu_name.'</span><i class="fa fa-angle-left pull-right"></i></a>';
		$html.= '<ul class="treeview-menu">';
		return $html;
	}
	
	function getParentMenu($menu_id)
	{
		$query = "select lvl0.id as lvl0_id, lvl1.id as lvl1_id, lvl2.id as lvl2_id
		from a_menu lvl0
		left join (
		 select * from a_menu 
		) lvl1 on lvl1.id = lvl0.parent_id
		left join (
		 select * from a_menu 
		) lvl2 on lvl2.id = lvl1.parent_id
		where lvl0.id = $menu_id";
		// debug($query);
		$row = $this->db->query($query);
		return ($row->num_rows() > 0) ? $row->result() : FALSE;
	}
	
	function getMenuStructure_old($cur_page)
	{
		$html = ''; $li1_closed = false; $li2_closed = false; $menu_id1 = 0; $menu_id2 = 0; $menu_id3 = 0; $parent_id = 0;
		$html.= $this->li($cur_page, 1, 'systems/x_page?pageid=1', 'Dashboard', 'fa fa-dashboard');
		$rowParentMenu = ($result = $this->getParentMenu($cur_page)) ? $result[0] : (object)['lvl1_id'=>0, 'lvl2_id'=>0];
		$rowMenus = $this->_getMenuByRoleId($this->session->role_id);
		if ($rowMenus) {
			foreach ($rowMenus as $menu){
				if (($menu_id1 != $menu->menu_id1) && $li1_closed){
					$html.= '</ul></li>';
					$li1_closed = false;
				}
				if (($menu_id2 != $menu->menu_id2) && $li2_closed){
					$html.= '</ul></li>';
					$li2_closed = false;
				}
				if (!empty($menu->menu_id2) || !empty($menu->menu_id3)){
					if ($menu_id1 != $menu->menu_id1){
						$parent_id = $rowParentMenu->lvl2_id ? $rowParentMenu->lvl2_id : $rowParentMenu->lvl1_id;
						$html.= $this->li_parent($parent_id, $menu->menu_id1, 'systems/x_page?pageid='.$menu->menu_id1, $menu->name1, $menu->icon1);
						$li1_closed = true;
						$menu_id1 = $menu->menu_id1;
					}
					if (($menu_id2 != $menu->menu_id2) && !empty($menu->menu_id3)){
						$parent_id = $rowParentMenu->lvl1_id;
						$html.= $this->li_parent($parent_id, $menu->menu_id2, 'systems/x_page?pageid='.$menu->menu_id2, $menu->name2, $menu->icon2);
						$li2_closed = true;
						$menu_id2 = $menu->menu_id2;
						
					} elseif (($menu_id2 != $menu->menu_id2) && empty($menu->menu_id3)){
						$html.= $this->li($cur_page, $menu->menu_id2, 'systems/x_page?pageid='.$menu->menu_id2, $menu->name2, $menu->icon2);
						$menu_id2 = $menu->menu_id2;
					}
					if (!empty($menu->menu_id3)){
						$html.= $this->li($cur_page, $menu->menu_id3, 'systems/x_page?pageid='.$menu->menu_id3, $menu->name3, $menu->icon3);
					}
				} elseif (!empty($menu->menu_id1)){
					$html.= $this->li($cur_page, $menu->menu_id1, 'systems/x_page?pageid='.$menu->menu_id1, $menu->name1, $menu->icon1);
				}
			}
			if ($li1_closed)
				$html.= '</ul></li>';
		}
		
		$html.= '<br><li><a href="#" id="go-lock-screen" onclick="lock_the_screen();"><i class="fa fa-circle-o text-yellow"></i> <span>' . $this->lang->line('nav_lckscr') . '</span></a></li>';
		$html.= '<li><a href="'.LOGOUT_LNK.'" id="go-sign-out"><i class="fa fa-sign-out text-red"></i> <span>' . $this->lang->line('nav_logout') . '</span></a></li>';
		return $html;
	}
	
	function getMenuStructure($cur_page)
	{
		$cur_page = $cur_page ? 'and id = '.$cur_page : '';
		$str = "WITH RECURSIVE menu_tree (id, parent_id, level, menu_active) 
			AS ( 
				SELECT 
					id, parent_id, 0 as level, cast(id as text)
				FROM a_menu
				WHERE (parent_id is NULL or parent_id = 0) and is_deleted = '0' and is_active = '1' and is_submodule = '0' and type != 'P'
				UNION ALL
				SELECT 
					mn.id, mt.id, mt.level + 1, mt.menu_active || ',' || mn.id
				FROM a_menu mn, menu_tree mt 
				WHERE mn.parent_id = mt.id and is_deleted = '0' and is_active = '1' and is_submodule = '0' and type != 'P'
			) 
			SELECT * FROM menu_tree WHERE id != 1 $cur_page ORDER BY level, parent_id;";
		$qry = $this->db->query($str);
		$menu_active = $qry->row() ? explode(',', $qry->row()->menu_active) : [];
		// debugf(explode(',', $menu_active));		
		/* $str = "WITH RECURSIVE menu_tree (id, parent_id, level, childno, line_no, is_parent, name, name_tree, icon) 
			AS ( 
				SELECT 
					id, parent_id, 0 as level, (select count(distinct am.id) from a_menu as am where am.parent_id = a_menu.id) as childno, 1 as line_no, is_parent, name, '' || name, icon
				FROM a_menu
				WHERE (parent_id is NULL or parent_id = 0) and is_deleted = '0' and is_active = '1' and is_submodule = '0' and type != 'P'
				UNION ALL
				SELECT 
					mn.id, mt.id, mt.level + 1, (select count(distinct am.id) from a_menu as am where am.parent_id = mn.id) as childno, mn.line_no, mn.is_parent, mn.name, mt.name_tree || '->' || mn.name,mn.icon
				FROM a_menu mn, menu_tree mt 
				WHERE mn.parent_id = mt.id and is_deleted = '0' and is_active = '1' and is_submodule = '0' and type != 'P'
			) 
			SELECT * FROM menu_tree WHERE id != 1	ORDER BY level, parent_id, line_no;"; */
		$role_id = $this->session->role_id;
		$str = "WITH RECURSIVE menu_tree (id, parent_id, childno, line_no, is_parent, name, icon) 
			AS ( 
				SELECT
					id, parent_id, (select count(distinct am.id) from a_menu as am where am.parent_id = a_menu.id) as childno, line_no, is_parent, name, icon
				FROM a_menu
				WHERE is_deleted = '0' and is_active = '1' and is_submodule = '0' and type != 'P' and exists(select menu_id from a_role_menu where role_id = $role_id and is_deleted = '0' and is_active = '1' and menu_id = a_menu.id)
				UNION ALL
				SELECT
					mn.id, mn.parent_id, (select count(distinct am.id) from a_menu as am where am.parent_id = mn.id) as childno, mn.line_no, mn.is_parent, mn.name, mn.icon
				FROM a_menu mn, (select distinct parent_id from menu_tree) mt 
				WHERE mn.id = mt.parent_id --and (mt.parent_id is NULL or mt.parent_id = 0) 
			) 
			SELECT distinct * FROM menu_tree 
			WHERE id != 1
			ORDER BY parent_id, line_no;";
		$qry = $this->db->query($str);
		$html = '';
		$html.= $this->li($cur_page, 1, 'systems/x_page?pageid=1', 'Dashboard', 'fa fa-dashboard');
		// debug($qry->result_array());
		$html.= $this->_getmenu_recursively($qry->result_array(), null, $menu_active);
		$html.= '<br><li><a href="#" id="go-lock-screen" onclick="lock_the_screen();"><i class="fa fa-circle-o text-yellow"></i> <span>' . $this->lang->line('nav_lckscr') . '</span></a></li>';
		$html.= '<li><a href="'.LOGOUT_LNK.'" id="go-sign-out"><i class="fa fa-sign-out text-red"></i> <span>' . $this->lang->line('nav_logout') . '</span></a></li>';
		return $html;
	}
	
	function _getmenu_recursively($categories, $parent = null, $menu_active = array())
	{
    $ret = '';
    foreach($categories as $index => $category)
    {
			if($category['parent_id'] == $parent)
			{
				$url = base_url().'systems/x_page?pageid='.$category['id'];
				$active = in_array($category['id'], $menu_active) ? 'active' : '';
				if ($category['is_parent'] == '1'){
					$glyp_icon = ($category['icon']) ? '<i class="'.$category['icon'].'"></i> ' : '<i class="glyphicon glyphicon-menu-hamburger"></i>';
					$ret .= '<li class="treeview '.$active.'"><a href="'.$url.'">'.$glyp_icon.'<span>'.$category['name'].'</span><i class="fa fa-angle-left pull-right"></i></a>';
					$ret .= '<ul class="treeview-menu">'.$this->_getmenu_recursively($categories, $category['id'], $menu_active).'</ul>';
					$ret .= '</li>';
				} else {
					$glyp_icon = ($category['icon']) ? '<i class="'.$category['icon'].'"></i> ' : '<i class="fa fa-circle"></i>';
					$ret .= '<li class="treeview '.$active.'"><a href="'.$url.'">'.$glyp_icon.'<span>'.$category['name'].'</span></a>';
					$ret .= $this->_getmenu_recursively($categories, $category['id'], $menu_active);
					$ret .= '</li>';
				}
			}
    }
    return $ret;
	}
	
	function single_view($content, $data=[])
	{
		$elapsed = $this->benchmark->elapsed_time('total_execution_time_start', 'total_execution_time_end');
		
		$select = 'head_title, page_title, logo_text_mn, logo_text_lg';
		$system = ($result = $this->base_model->getValueArray($select, 'a_system', ['client_id', 'org_id'], [DEFAULT_CLIENT_ID, DEFAULT_ORG_ID])) ? $result : [];
		
		$default['elapsed_time']= $elapsed;
		$default['start_time'] 	= microtime(true);
		$this->fenomx->view(TEMPLATE_PATH.$content, array_merge($default, $data, $system));
		exit;
	}
	
	function backend_view($content, $data=[])
	{
		$elapsed = $this->benchmark->elapsed_time('total_execution_time_start', 'total_execution_time_end');
		
		$default['content'] 	= TEMPLATE_PATH.$content.'.tpl';
		$default['menus'] 		= $this->getMenuStructure($this->pageid ? $this->pageid : 0);
		
		$default['elapsed_time']= $elapsed;
		$default['start_time'] 	= microtime(true);
		$this->fenomx->view(TEMPLATE_PATH.'index', array_merge($default, $data));
		exit;
	}
	
}