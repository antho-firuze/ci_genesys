<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Base_Model extends CI_Model
{

	protected $errors;
	protected $error_start_delimiter;
	protected $error_end_delimiter;
	
	public function __construct()
	{
		parent::__construct();
		
		$this->error_start_delimiter   = '<p>';
		$this->error_end_delimiter     = '</p>';
	}
	
	/**
	 * set_error
	 *
	 * Set an error message
	 *
	 * @return void
	 * @author Ben Edmunds
	 **/
	public function set_error($error)
	{
		$this->errors[] = $error;

		return $error;
	}

	/**
	 * errors
	 *
	 * Get the error message
	 *
	 * @return void
	 * @author Ben Edmunds
	 **/
	public function errors()
	{
		$_output = '';
		foreach ($this->errors as $error)
		{
			$errorLang = $this->lang->line($error) ? $this->lang->line($error) : $error;
			$_output .= $this->error_start_delimiter . $errorLang . $this->error_end_delimiter;
		}
		
		$this->clear_errors();
		
		return $_output;
	}

	/**
	 * errors as array
	 *
	 * Get the error messages as an array
	 *
	 * @return array
	 * @author Raul Baldner Junior
	 **/
	public function errors_array($langify = TRUE)
	{
		if ($langify)
		{
			$_output = array();
			foreach ($this->errors as $error)
			{
				$errorLang = $this->lang->line($error) ? $this->lang->line($error) : '##' . $error . '##';
				$_output[] = $this->error_start_delimiter . $errorLang . $this->error_end_delimiter;
			}
			
			$this->clear_errors();
		
			return $_output;
		}
		else
		{
			return $this->errors;
		}
	}

	/**
	 * clear_errors
	 *
	 * Clear Errors
	 *
	 * @return void
	 * @author Ben Edmunds
	 **/
	public function clear_errors()
	{
		$this->errors = array();

		return TRUE;
	}

	/* 
	* Example : http://localhost/ci/app1/sales/e_swg_class?list=1&filter=&order=&limit=&offset= 
	*
	* 
	* @return object
	* @author antho.firuze@gmail.com
	*/
	function mget_rec($params = NULL, $counter = FALSE, $summary = [])
	{
		$params = (array) $params;
		
		$this->db->select($params['select']);
		
		$this->db->from($params['table']);
		if ( key_exists('join', $params)) DBX::join($this, $params['join']);
		// if ( key_exists('where', $params)) $this->db->where($params['where']);
		if ( key_exists('where', $params)) {
			if (is_array($params['where'])){
				foreach($params['where'] as $k => $v){
					if (is_int($k))
						$this->db->where($v);
					else
						$this->db->where($k, $v);
				}
			} else {
				$this->db->where($params['where']);
			}
		}
		
		/* sample: $this->params->where_in['t1.doc_type'] = ['5', '6']; */
		if ( key_exists('where_in', $params)) {
			foreach($params['where_in'] as $k => $v){
				$this->db->where_in($k, $v);
			}
		}
		/* 
		sample: 
		$this->params->where_custom = "exists (select distinct(order_id) from cf_order_line f1 where is_active = '1' and is_deleted = '0' 
			and not exists (select 1 from cf_inout_line where is_active = '1' and is_deleted = '0' and is_completed = '1' and order_line_id = f1.id) and f1.order_id = t1.id)";
		 */
		if ( key_exists('where_custom', $params)) {
			if (is_array($params['where_custom'])){
				foreach($params['where_custom'] as $where_custom){
					$this->db->where($where_custom, NULL, FALSE);
				}
			} else {
				$this->db->where($params['where_custom']);
			}
		}
		if ( key_exists('like', $params)) $this->db->where($params['like']);
		// if ( key_exists('sort', $params)) $this->db->order_by($params['sort'], $params['order']);
		if (key_exists('sort', $params) && !empty($params['sort'])){
			$array = explode(",", $params['sort']);
			if (!empty($array)) {
				foreach ($array as $value) {
					$this->db->order_by($value);
				}
			}
		}
		if (key_exists('order', $this->params) && isset($this->params->order)) {
			foreach($this->params->order as $k => $v){
				$this->db->order_by($this->params->columns[$v['column']]['data'], $v['dir']);
			}
			// $sort = '';
			// foreach($this->params->order as $k => $v){
				// $sort .= ($sort ? ', ' : '').$this->params->columns[$v['column']]['data'].' '.$v['dir'];
			// }
			// debug($sort);
		}
		if ( key_exists('group', $params)) {
			$this->db->group_by($params['group']);
		}
		
		/* sample: &filter=field1=value1,field2=value2... */
		if (key_exists('filter', $this->params) && !empty($this->params->filter)){
			$array = explode(",", $this->params->filter);
			if (!empty($array)) {
				foreach ($array as $value) {
					// list($k, $v) = explode("=", $value);
					// $this->db->where($k, empty($v)?0:$v);
					$this->db->where($value, NULL, FALSE);
				}
			}
		}

		/* SQL Filter
		 * sample: &sfilter=is_import='1' and name like '%anonym%' */
		if (key_exists('sfilter', $this->params) && !empty($this->params->sfilter)){
			$this->db->where($this->params->sfilter, NULL, FALSE);
		}

		/* Special feature for showing deleted records */
		if (key_exists('xdel', $this->params) && ($this->params->xdel == '1') && ($this->session->user_id == 11)){
			$this->db->where('t1.is_deleted', '1');
		} else {
			if (!key_exists('xdel', $params))
				$this->db->where('t1.is_deleted', '0');
		}
		
		/* For export data */
		if (isset($this->params->export) && !empty($this->params->export)) {
			if (! $query = $this->db->get() ){
				xresponse(FALSE, ['data' => [], 'message' => '[get_rec] '.$this->db->error()['message']]);
			} 
			$this->export_data($query, $params);
		}
		
		/* For counting record number */
		if ($counter && empty($summary)){
			if (! $query = $this->db->get() ){
				// $this->db->error(); // Has keys 'code' and 'message'
				$this->set_error($this->db->error()['message']);
				return FALSE;
			} 
			return ($query->num_rows() > 0) ? $query->num_rows() : 0;
		}
		
		/* For summarize field value. 
		 * $summary param required (array) */
		if ($counter && $summary){
			// method #1 | process in database (using query) | weakness: if a field not exist from the table (throw error), because the field is from formulation
			// function added($v){	return "coalesce(sum($v), 0) as $v";	}
			// $a1 = array_map('added', $summary);
			// $str = implode(",", $a1);
			// $this->db->select($str);
			// if (! $query = $this->db->get() ){
				// return [FALSE, $this->db->error()['message']];
			// } 
			// return $query->result();
			
			// method #2 | process in script (using php)
			// $this->db->select($summary);
			if (! $query = $this->db->get() ){
				return [FALSE, $this->db->error()['message']];
			} 
			$result = $query->result();
			// debug($this->db->last_query());
			foreach($summary as $k => $v){
				$a[$v] = array_sum(array_column($result, $v)); 
			}
			return $a;
		}
		
		/* sample: &ob=field1,field2,field3... */
		/* sample: &ob=field1 desc,field2 desc,field3... */
		if (key_exists('ob', $this->params) && !empty($this->params->ob)){
			$array = explode(",", $this->params->ob);
			if (!empty($array)) {
				foreach ($array as $value) {
					$this->db->order_by($value);
				}
			}
		}
		
		/* sample: &limit=1&offset=0 */
		if (key_exists('limit', $this->params) && !empty($this->params->limit)){
			$offset = 0;
			if (key_exists('offset', $this->params) && !empty($this->params->offset)){
				$offset = $this->params->offset;
			}
			$this->db->limit($this->params->limit, $offset);
		}
		
		// LIMITATION FOR JQUERY DATATABLES COMPONENT
		if ( key_exists('start', $params) && key_exists('length', $params) )
			$this->db->limit($params['length'], $params['start']);
		
		// LIMITATION FOR JQUERY JEASYUI COMPONENT
		if ( key_exists('page', $params) && key_exists('rows', $params))
		{
			$params['page'] = empty($params['page']) ? 1 : $params['page'];
			$offset = ($params['page']-1)*$params['rows'];
			$this->db->limit($params['rows'], $offset);
		}

		if (! $query = $this->db->get() ){
			// debug($this->db->last_query());
			// $this->db->error(); // Has keys 'code' and 'message'
			$this->set_error($this->db->error()['message']);
			$this->set_error($this->db->last_query());
			if (ini_get('display_errors'))
				debug($this->errors());
			return FALSE;
		} 
		// debug($this->db->last_query());
		if (key_exists('export', $params) && ($params['export'])) {

			return $query;
		}
		
		$result = $query->result();
		
		if (key_exists('list', $params) && ($params['list'])) {
			
			return $result;
		} 
		
		$response['total'] = $this->mget_rec($params, TRUE, []);
		$response['rows']  = $result;
		// if ($summary){
		if (key_exists('footer', $this->params) && ($this->params->footer)) {
			// debug(explode(',', $this->params->footer));
			$response['summary'] = $this->mget_rec($params, TRUE, explode(',', $this->params->footer));
			// debug($result);
			// foreach($summary as $k => $v){
				// $a[$v] = array_sum(array_column($result, $v)); 
			// }
			// $response['summary'] = $a;
		}
		return $response;
	}
	
	/* 
	*	$qry 	= $this->db->get()						;From get() result
	*	$params = array/object
	*	======================
	* $params->excl_cols			array				;Ex: ['id','password']
	*
	*/
	function export_data($qry, $params=[])
	{
		if (is_array($params))
			$params = (object) $params;

		ini_set('memory_limit', '-1');
		$this->load->library('z_libs/Excel');
		$objPHPExcel = new PHPExcel();
		$objPHPExcel->getProperties()->setTitle("export")->setDescription("none");
 
		$objPHPExcel->setActiveSheetIndex(0);

		// Set the Title in the first row
		$current = 'A';
		$col = 0;
		$fields = [];
		if ($params->excl_cols) {
			foreach ($qry->list_fields() as $field) {
				if (!in_array($field, $params->excl_cols)){
					$columns[] = ($col == 0) ? $current : ++$current;
					$fields[] = $field;
					$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, 1, $field);
					$col++;
				}
			}
		} else {
			foreach ($qry->list_fields() as $field) {
				$columns[] = ($col == 0) ? $current : ++$current;
				$fields[] = $field;
				$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, 1, $field);
				$col++;
			}
		}
		// debug($fields);
		// Set the Data in the next row
		$row = 2;
		foreach($qry->result() as $data) {
			$col = 0;
			// foreach ($qry->list_fields() as $field) {
			foreach ($fields as $field) {
				$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $row, $data->{$field});
				$col++;
			}
			$row++;
		}
		
		// Set the Column to Fit AutoSize
		foreach($columns as $column) {
			$objPHPExcel->getActiveSheet()->getColumnDimension($column)->setAutoSize(true);
		}
		
		$this->export_data_final($objPHPExcel, $params);
	}
	
	/* 
	*	$rows 	= array						;From get() result
	*	$params = array/object
	*	======================
	* $params->excl_cols			array				;Ex: ['id','password']
	*
	*/
	function export_data_array($rows, $params=[])
	{
		if (is_array($params))
			$params = (object) $params;

		ini_set('memory_limit', '-1');
		$this->load->library('z_libs/Excel');
		$objPHPExcel = new PHPExcel();
		$objPHPExcel->getProperties()->setTitle("export")->setDescription("none");
 
		$objPHPExcel->setActiveSheetIndex(0);
		
		// Set the Title in the first row
		$current = 'A';
		$col = 0;
		$fields = [];
		if ($params->excl_cols) {
			foreach ($rows[0] as $field => $val) {
				if (!in_array($field, $params->excl_cols)){
					$columns[] = ($col == 0) ? $current : ++$current;
					$fields[] = $field;
					$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, 1, $field);
					$col++;
				}
			}
		} else {
			foreach ($rows[0] as $field => $val) {
				$columns[] = ($col == 0) ? $current : ++$current;
				$fields[] = $field;
				$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, 1, $field);
				$col++;
			}
		}
		
		// Set the Data in the next row
		$row = 2;
		foreach($rows as $data) {
			$col = 0;
			foreach ($fields as $field) {
				$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $row, $data->{$field});
				$col++;
			}
			$row++;
		}
		
		// Set the Column to Fit AutoSize
		foreach($columns as $column) {
			$objPHPExcel->getActiveSheet()->getColumnDimension($column)->setAutoSize(true);
		}
		
		$this->export_data_final($objPHPExcel, $params);
	}
	
	/* 
	*	$objPHPExcel = object PHPExcel()					;From PHPExcel() object
	*	$params = array/object
	*	======================
	* $params->filetype					string				;Ex: 'xls' or 'xlsx' or 'csv' or 'pdf' or 'html'
	* $params->tmp_dir_absolute	string				;Ex: 'd:/var/tmp/'
	* $params->tmp_dir_relative	string				;Ex: 'var/tmp/'
	* $params->is_compress			boolean				:Ex: TRUE/FALSE
	* $params->base_url					string				;Ex: BASE_URL
	* $params->filename					string				;Ex: 'filename.xls' 
	*
	*/
	function export_data_final($objPHPExcel, $params=[])
	{
		if (is_array($params))
			$params = (object) $params;

		if (in_array($params->filetype, ['xls', 'xlsx'])) {
			$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
			$objWriter->save($params->tmp_dir_absolute.$params->filename);
		}
		if ($params->filetype == 'csv'){
			PHPExcel_Shared_String::setDecimalSeparator('.');
			PHPExcel_Shared_String::setThousandsSeparator(',');

			$objWriter = new PHPExcel_Writer_CSV($objPHPExcel);
			$objWriter->save($params->tmp_dir_absolute.$params->filename);
		}
		if ($params->filetype == 'pdf'){
			$rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
			$rendererLibraryPath = FCPATH.'../vendor/mpdf/mpdf/src/';
			if (!PHPExcel_Settings::setPdfRenderer($rendererName,	$rendererLibraryPath)) {
				xresponse(FALSE, [
					'data' => [], 
					'message' => '[final] Please set the $rendererName and $rendererLibraryPath values' .
												PHP_EOL .
												' as appropriate for your directory structure'
				]);
			}
			$objWriter = new PHPExcel_Writer_PDF($objPHPExcel);
			$objWriter->save($params->tmp_dir_absolute.$params->filename);
		}
		if ($params->filetype == 'html'){
			$objWriter = new PHPExcel_Writer_HTML($objPHPExcel);
			$objWriter->save($params->tmp_dir_absolute.$params->filename);
		}
		
		$result = [
			'filename' => $params->filename, 
			'filepath' => $params->tmp_dir_absolute.$params->filename, 
			'file_url' => $params->base_url.$params->tmp_dir_relative.$params->filename
		];
		
		if ($params->is_compress) 
			$this->compress_file($result['filepath'], $params);
			
		xresponse(TRUE, ['data' => $result]);
	}
	
	/* 
	*	$files 	= array/string									;Absolute file path
	*	$params = array/object
	*	======================
	* $params->separate_zip	= true/false			;Separate or not
	* $params->remove_source	= true/false		;Remove source file
	* $params->base_url			= BASE_URL
	* $params->tmp_dir_relative	= 'var/tmp/'
	*
	*/
	function compress_file($files, $params=[])
	{
		if (is_array($params))
			$params = (object) $params;

		// Default value
		$params->separate_zip		= isset($params->separate_zip) ? $params->separate_zip : TRUE;
		$params->remove_source	= isset($params->remove_source) ? $params->remove_source : TRUE;
		
		if (is_array($files)) {
			$i = 1;
			foreach($files as $file){
				
				$pathinfo = pathinfo($file);
				$dir = $pathinfo['dirname'];
				$fil = $pathinfo['filename'];
				$fbn = $pathinfo['basename'];
				$ext = strtolower($pathinfo['extension']);
				$filezip = $fil.'.zip';
				$fil_tmp = $dir.'/'.$filezip;
				
				if ($params->separate_zip) {
					$zip = new ZipArchive();
					if ($zip->open($fil_tmp, ZipArchive::CREATE) !== TRUE) {
						xresponse(FALSE, ['data' => [], 'message' => "Cannot open <$fil_tmp>\n"], 401);
					}
					$zip->addFile($file, $fbn);
					$zip->close();
					
				} else {
					if ($i == 1) {
						$zip = new ZipArchive();
						if ($zip->open($fil_tmp, ZipArchive::CREATE) !== TRUE) {
							xresponse(FALSE, ['data' => [], 'message' => "Cannot open <$fil_tmp>\n"], 401);
						}
					}
					
					$zip->addFile($file, $fbn);
				}
				
				if ($params->remove_source)
					@unlink($file);	

				$i++;
			}
			
			if (! $params->separate_zip)
				$zip->close();
			
		} else {
			$pathinfo = pathinfo($files);
			$dir = $pathinfo['dirname'];
			$fil = $pathinfo['filename'];
			$fbn = $pathinfo['basename'];
			$ext = strtolower($pathinfo['extension']);
			$filezip = $fil.'.zip';
			$fil_tmp = $dir.'/'.$filezip;
			
			$zip = new ZipArchive();
			if ($zip->open($fil_tmp, ZipArchive::CREATE) !== TRUE) {
				xresponse(FALSE, ['data' => [], 'message' => "Cannot open <$fil_tmp>\n"], 401);
			}
			$zip->addFile($files, $fbn);
			$zip->close();

			if ($params->remove_source)
				@unlink($files);
		}

		$result = [
			'filename' => $filezip, 
			'filepath' => $params->tmp_dir_relative.$filezip, 
			'file_url' => $params->base_url.$params->tmp_dir_relative.$filezip
		];
		xresponse(TRUE, ['data' => $result]);
	}
	
	function isDataExist($table, $where=array())
	{
		if (! $where) 
			return FALSE;
		
		$where = array_merge(['is_deleted' => '0', 'is_active' => '1'], $where); 
		$qry = $this->db->get_where($table, $where);
		// debug($this->db->last_query());
		return $qry->num_rows() > 0 
			? $qry->row() 
			: FALSE;
	}
	
	/* function mget_rec_count($params = NULL)
	{
		$this->db->select($params['select']);
		$this->db->from($params['table']);
		if ( key_exists('join', $params)) DBX::join($this, $params['join']);
		if ( key_exists('where', $params)) $this->db->where($params['where']);
		if ( key_exists('where_custom', $params)) $this->db->where($params['where_custom']);
		if ( key_exists('like', $params)) $this->db->where($params['like']);

		if (key_exists('filter', $this->params) && !empty($this->params->filter)){
			$array = explode(",", $this->params->filter);
			if (!empty($array)) {
				foreach ($array as $value) {
					// list($k, $v) = explode("=", $value);
					// $this->db->where($k, empty($v)?0:$v);
					$this->db->where($value);
				}
			}
		}
		
		if (! $query = $this->db->get() ){
			// $this->db->error(); // Has keys 'code' and 'message'
			$this->set_error($this->db->error()['message']);
			return FALSE;
		} 
		return ($query->num_rows() > 0) ? $query->num_rows() : 0;
	} */
	
	/**
	 * getValue
	 *
	 * Function get value from table with object
	 *
	 * @param	string	$sel_field   DB field select
	 * @param	string	$table    DB table
	 * @param	string	$where_field   where field or where condition Ex. "field = '1' AND field2 = '2'"
	 * @param	string	$where_val   value of wherer (If $where_field has condition. Please null)
	 * @param	string	$orderby   Order by field or NULL 
	 * @param	string	$sort   asc or desc or NULL 
	 * @param	string	$groupby   Group by field or NULL 
	 * @return	Object or FALSE
	 */
	public function getValue($sel_field = '*', $table, $where_field, $where_val, $limit = 0, $orderby = '', $sort = '', $groupby = '') {
		$this->db->select($sel_field);
		if($where_field || $where_val){
				if (is_array($where_field) && is_array($where_val)) {
						for ($i = 0; $i < count($where_field); $i++) {
								$this->db->where($where_field[$i], $where_val[$i]);
						}
				} else {
						$this->db->where($where_field, $where_val);
				}
		}
		if ($groupby) {
				$this->db->group_by($groupby);
		}
		if ($orderby && $sort) {
				$this->db->order_by($orderby, $sort);
		}
		if ($limit) {
				$this->db->limit($limit, 0);
		}
		$query = $this->db->get($table);
		if (!empty($query)) {
				if ($query->num_rows() !== 0) {
						if ($query->num_rows() === 1) {
								$row = $query->row();
						} else {
								$row = $query->result();
						}
						return $row;
				} else {
						return FALSE;
				}
		} else {
				return FALSE;
		}
	}

	/**
	 * getValueArray
	 *
	 * Function get value from table with array
	 *
	 * @param	string	$sel_field   DB field select
	 * @param	string	$table    DB table
	 * @param	string	$where_field   where field or where condition Ex. "field = '1' AND field2 = '2'"
	 * @param	string	$where_val   value of wherer (If $where_field has condition. Please null)
	 * @param	string	$orderby   Order by field or NULL 
	 * @param	string	$sort   asc or desc or NULL 
	 * @param	string	$groupby   Group by field or NULL 
	 * @return	Array or FALSE
	 */
	public function getValueArray($sel_field = '*', $table, $where_field, $where_val, $limit = 0, $orderby = '', $sort = '', $groupby = '') {
		$this->db->select($sel_field);
		if($where_field || $where_val){
				if (is_array($where_field) && is_array($where_val)) {
						for ($i = 0; $i < count($where_field); $i++) {
							$this->db->where($where_field[$i], $where_val[$i]);
						}
				} else {
						$this->db->where($where_field, $where_val);
				}
		}
		if ($groupby) {
				$this->db->group_by($groupby);
		}
		if ($orderby && $sort) {
				$this->db->order_by($orderby, $sort);
		}
		if ($limit) {
				$this->db->limit($limit, 0);
		}
		$query = $this->db->get($table);
		if (!empty($query)) {
				if ($query->num_rows() !== 0) {
						if ($query->num_rows() === 1) {
								$row = $query->row_array();
						} else {
								$row = $query->result_array();
						}
						return $row;
				} else {
						return FALSE;
				}
		} else {
				return FALSE;
		}
	}

	function get_rec_tree( $params=NULL ) { 
		if ( is_array($params) )
		{
			if ( empty($params['id']) ) {
				// REC RESULT
				$this->db->select('*, name as text');
				$this->db->from($params['table']);
				$this->db->where('parent_id', 0);
				$this->db->where('deleted', 0);
				$this->db->order_by('sort_no', 'asc');
				$result = (array)$this->mget_rec($params);

				$results = array();
				foreach ( $result as $r ) {
					$r->state = ($this->has_child_tree( $params['table'], $r->id )) ? 'closed' : 'open';
					array_push($results, $r);
				}
			} else {
				// REC RESULT
				$this->db->select('*, name as text');
				$this->db->from($params['table']);
				$this->db->where('parent_id', $params['id']);
				$this->db->where('deleted', 0);
				$this->db->order_by('sort_no', 'asc');
				$result = $this->mget_rec($params);

				$results = array();
				foreach ( $result as $r ) {
					$r->state = ($this->has_child_tree( $params['table'], $r->id )) ? 'closed' : 'open';
					array_push($results, $r);
				}
			}
		}
		
		return $results;
	}
	
	function get_rec_tree_grid( $params=NULL ) { 
		if ( is_array($params) )
		{
			if ( empty($params['id']) ) {
				// REC COUNT
				$this->db->select('COUNT(*) AS rec_count');
				$this->db->from($params['table']);
				$this->db->where('parent_id', 0);
				$num_row = $this->mget_rec_count($params);

				// REC RESULT
				$this->db->select('*');
				$this->db->from($params['table']);
				$this->db->where('parent_id', 0);
				$result = $this->mget_rec($params);

				$results = array();
				foreach ( $result as $r ) {
					$r->state = ($this->has_child_tree( $params['table'], $r->id )) ? 'closed' : 'open';
					array_push($results, $r);
				}
				
				$response = new stdClass();
				$response->total = $num_row;
				$response->rows  = $results;
				
			} else {
			
				// REC RESULT
				$this->db->select('*');
				$this->db->from($params['table']);
				$this->db->where('parent_id', $params['id']);
				$result = $this->mget_rec($params);

				$results = array();
				foreach ( $result as $r ) {
					$r->state = ($this->has_child_tree( $params['table'], $r->id )) ? 'closed' : 'open';
					array_push($results, $r);
				}
				$response = $results;
			}
		}
		
		return $response;
	}
	
	function has_child_tree( $table, $id ) {
		$this->db->select('COUNT(*) AS rec_count');
		$this->db->from($table);
		$this->db->where('parent_id', $id);
		$this->db->where('deleted', 0);
		return ($this->db->get()->row()->rec_count > 0) ? TRUE : FALSE;
	}
	
	function re_sorting_tree($params=NULL){
		$rows = $this->db->order_by('sort_no', 'asc')->get_where( $params['table'], $params['where'] )->result();
		$i = 1;
		foreach ($rows as $row){
			$this->db->update( $params['table'], array('sort_no'=>$i), array('id'=>$row->id) );
			$i++;
		}
	}
	
	function update_relation_n_n( $table=NULL, $primary_field=NULL, $primary_value=NULL, $foreign_field=NULL, $foreign_values=NULL ) {

		$this->db->delete( $table, array($primary_field=>$primary_value));
		if ( !empty($foreign_values) ) {
			foreach ($foreign_values as $value) {	
				$this->db->insert( $table, array($primary_field=>$primary_value, $foreign_field=>$value));
			}
			return TRUE;
		}
		return FALSE;
	}
	
	function push_notification_email() {
		$qry = $this->db->get_where( 'notification_email', array('status'=>'created') );
		if ( $qry->num_rows() < 1)
			return FALSE;
	
		foreach ($qry->result() as $row) {
			$result = send_mail($row->email, $row->subject, $row->message);
			if ( $result ) 
				$this->db->update( 'notification_email', array('status'=>'sent', 'sent'=>date('Y-m-d H:i:s')), array('id'=>$row->id) );
			else
				$this->db->update( 'notification_email', array('status'=>'failed', 'sent'=>date('Y-m-d H:i:s')), array('id'=>$row->id) );
		}
		return TRUE;
	}
	
	function is_duplicate_code( $table=NULL, $code=NULL ) {
		return empty($this->db->get_where($table, array('code'=>$code, 'deleted'=>0), 1)->row()->id) ? FALSE : TRUE;
	}
	
	function is_duplicate_username( $table=NULL, $username=NULL ) {
		return empty($this->db->get_where($table, array('username'=>$username), 1)->row()->id) ? FALSE : TRUE;
	}
	
	function is_customer_exists( $company_id=NULL, $customer_id=NULL ) {
		$qry = $this->db->get_where( 'customer', array('id'=>intval($customer_id), 'company_id'=>$company_id) );
		return ($qry->num_rows() < 1) ? FALSE : TRUE;
		if ( $qry->num_rows() < 1 ) 
			return FALSE;
		else
			return TRUE;
	}
	
	function is_data_exists_on( $table=NULL, $fields=NULL, $search_value=NULL ) {
		$f = array();
		foreach ( $fields as $field ) {
			$f[$field] = $search_value;
		}
		return empty($this->db->get_where($table, $f, 1)->row()->id) ? FALSE : TRUE;
	}
	
	function updateTotalAmount($table, $id)
	{
		$filter['id'] = $id;
		$qry = $this->db->get_where( $table, $filter );
		foreach ($qry->result() as $row) 
		{
			$this->db->select_sum('amount', 'total_amount');
			$this->db->where($table.'_id', $row->id);
			// $this->db->where('void', 0);
			$summary = $this->db->get($table.'_dt')->row();

			$data1['total_amount'] = $summary->total_amount;
			$this->db->update( $table, $data1, $filter );
		}
		return;
	}
	
}