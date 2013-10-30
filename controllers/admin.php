<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Export and import PyroStream schema from one site to another
 *
 * @author 		Toni Haryanto
 * @website		http://toniharyanto.net
 * @package 	PyroCMS
 * @subpackage 	PyroStream
 */
class Admin extends Admin_Controller
{
	protected $section = 'items';

	public function __construct()
	{
		parent::__construct();

		// Load all the required classes
		$this->load->driver('Streams');
		$this->load->model('stream_schema_m');
		$this->load->library('form_validation');
		$this->lang->load('stream_schema');

		// Set the validation rules
		$this->item_validation_rules = array(
			array(
					'field' => 'file',
					'label' => 'Stream File',
					'rules' => 'trim',
				),
		);
	}

	/**
	 * List all items
	 */
	public function index()
	{
		$stream_schema = $this->streams->streams->get_streams('streams');
		$datatable = $this->load->view('admin/table', array('stream_schema'=>$stream_schema), true);
		
		$namespace = $this->stream_schema_m->get_namespaces();

		$this->template
			->title($this->module_details['name'])
			->set('datatable', $datatable)
			->set('namespace', $namespace)
			->build('admin/index');
	}

	public function table_ajax($namespace = 'streams'){
		$stream_schema = $this->streams->streams->get_streams($namespace);
		echo $this->load->view('admin/table', array('stream_schema'=>$stream_schema), true);
	}

	public function import()
	{
		$stream_schema = new StdClass();
		
		$this->form_validation->set_rules($this->item_validation_rules);

		// check if the form validation passed
		if($this->form_validation->run())
		{
			$config['upload_path'] = './'.UPLOAD_PATH.$this->module_details['slug'];
			$config['allowed_types'] = 'txt';
			$this->load->library('upload', $config);

			if ( ! $this->upload->do_upload('file')){
				$this->session->set_flashdata('error', $this->upload->display_errors());
				redirect('admin/stream_schema/import');
			} else {
				$file =  $this->upload->data();
				$data = json_decode(file_get_contents($file['full_path']));

				if(!$data OR !isset($data->fields)){
					$this->session->set_flashdata('error', lang('stream_schema:invalid_backup'));
					redirect(getenv('HTTP_REFERER'));
				}
				
				$this->load->driver('Streams');

				//check if this stream need relationship field
				// then add fields
				$fields = array();
				foreach($data->fields as $field){

					// if this field is not exist in stream field before
					if(!$this->stream_schema_m->get_field($field->field_slug,$field->field_namespace)){
						$tempdata = unserialize($field->field_data);
						if(isset($tempdata->choose_stream)){
							if($rel_id = $this->stream_schema_m->get_stream_id($tempdata->choose_stream)){
								$tempdata->choose_stream = $rel_id;
							} else {
								// if stream relationship not found terminate
								$this->session->set_flashdata('error', sprintf(lang('stream_schema:relationship_not_found'), $tempdata->choose_stream));
								redirect('admin/stream_schema/import');
								break;
							}
						}
						
						$fields[] = array(
							'name'          => $field->field_name,
							'slug'          => $field->field_slug,
							'namespace'     => $field->field_namespace,
							'type'          => $field->field_type,
							'extra'         => $tempdata
						);
						unset($tempdata);
					}
				}
				// dump($fields);
				// dump($data['fields']);
				$this->streams->fields->add_fields($fields);
				
				// add stream
				$this->streams->streams
				->add_stream($data->stream->stream_name,
					$data->stream->stream_slug,
					$data->stream->stream_namespace,
					$data->stream->stream_prefix,
					$data->stream->about,
					array(
						'title_column' => $data->stream->title_column,
						'is_hidden' => $data->stream->is_hidden,
						'sorting' => $data->stream->sorting,
						'menu_path' => $data->stream->menu_path,
						'view_options' => $data->stream->view_options
						)
					);

				// add assignments
				foreach($data->assignments as $assign){
					if(! $this->stream_schema_m->get_assignment($assign->stream_slug,
						$assign->field_slug,$assign->namespace))
							$this->streams->fields
								->assign_field($assign->namespace, 
											   $assign->stream_slug,
											   $assign->field_slug,
											   array(
												'required' => ($assign->is_required == 'no')? false : true,
												'unique' => ($assign->is_unique == 'no')? false : true,
												'instructions' => $assign->instructions
												)
								);
				}
				
				$this->session->set_flashdata('success', lang('stream_schema:import_success'));
				redirect('admin/stream_schema');
			}
		}
		
		$stream_schema->data = new StdClass();
		foreach ($this->item_validation_rules AS $rule)
		{
			$stream_schema->data->{$rule['field']} = $this->input->post($rule['field']);
		}
		
		// Build the view using sample/views/admin/form.php
		$this->template->title($this->module_details['name'], lang('stream_schema:import'))
						->build('admin/form', $stream_schema->data);
	}

	public function backup($mode = 'schema', $id = false)
	{
		if(is_numeric($id))
		{
			// get stream data
			$stream = $this->streams->streams->get_stream($id);

			// if($mode == 'schema') {
				// get assignment data
				$assignment = $this->streams->streams->get_assignments($stream->stream_slug, $stream->stream_namespace);

				// separate data between field_assignment and field
				$fields = array();
				$assignments = array();
				foreach($assignment as $order => $row){

					$tempdata = $row->field_data;
					if(isset($tempdata['choose_stream']))
						if($choose_stream = $this->stream_schema_m->get_stream_slug($tempdata['choose_stream']))
							$tempdata['choose_stream'] = $choose_stream;

					$fields[] = array(
						'field_name' => $row->field_name,
						'field_slug' => $row->field_slug,
						'field_namespace' => $row->field_namespace,
						'field_type' => $row->field_type,
						'field_data' => $tempdata,
						'view_options' => $row->field_view_options,
						'is_locked' => $row->is_locked
						);
					$assignments[] = array(
						'sort_order' => $order+1,
						'namespace' => $row->field_namespace,
						'stream_slug' => $row->stream_slug, // we use stream_slug instead of stream_id to prevent misslinking
						'field_slug' => $row->field_slug, // we use field_slug instead of field_id to prevent misslinking
						'is_required' => $row->is_required,
						'is_unique' => $row->is_unique,
						'instructions' => $row->instructions
					);
					unset($tempdata);
				}

				$output = array(
					'stream' => $stream,
					'fields' => $fields,
					'assignments' => $assignments
					);
				
				$this->load->helper('download');
				force_download('Stream - '.$stream->stream_name.'.txt', json_encode($output));

			// } else {

			// 	$params = array(
			// 		'stream'    => $stream->stream_slug,
			// 		'namespace' => $stream->stream_namespace
			// 		);

			// 	$entries = $this->streams->entries->get_entries($params);

			// 	if($entries['total'] > 0){
			// 		$this->load->helper('download');
			// 		force_download('Stream Data - '.$stream->stream_name.'.txt', json_encode($entries['entries']));
			// 	} else {
			// 		$this->session->set_flashdata('error', sprintf(lang('stream_schema:no_data'), $stream->stream_name));
			// 		redirect(getenv('HTTP_REFERER'));
			// 	}
			// }
		}
	}

	public function xls($id = false)
	{
		if(is_numeric($id))
		{
			// get stream data
			$stream = $this->streams->streams->get_stream($id);
			$params = array(
					'stream'    => $stream->stream_slug,
					'namespace' => $stream->stream_namespace
					);

			$entries = $this->streams->entries->get_entries($params);
			// dump($stream);
			// dump($entries);

			if($entries['total'] < 1){	
				$this->session->set_flashdata('error', sprintf(lang('stream_schema:no_data'), $stream->stream_name));
				redirect(getenv('HTTP_REFERER'));
			}

			$alp = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');

			/** Error reporting */
			error_reporting(E_ALL);
			ini_set('display_errors', TRUE);
			ini_set('display_startup_errors', TRUE);
			date_default_timezone_set('Europe/London');

			if (PHP_SAPI == 'cli')
				die('This example should only be run from a Web Browser');

			require_once SHARED_ADDONPATH.'modules/stream_schema/PHPExcel/Classes/PHPExcel.php';

			// Create new PHPExcel object
			$objPHPExcel = new PHPExcel();

			// Set document properties
			$objPHPExcel->getProperties()->setCreator($entries['entries'][0]['created_by']['display_name'])
			->setLastModifiedBy($entries['entries'][0]['created_by']['display_name'])
			->setTitle("Stream Data ".$stream->stream_name)
			->setSubject("Stream Data ".$stream->stream_name)
			->setDescription($stream->about)
			->setKeywords("office 2007 openxml php stream ".$stream->stream_slug)
			->setCategory("stream export data");


			// add title
			$objPHPExcel->setActiveSheetIndex(0)
					->setCellValue('A1', $stream->stream_name);

			// Add head table
			$col = 0;
			foreach ($entries['entries'][0] as $key => $value) {
				$objPHPExcel->setActiveSheetIndex(0)
					->setCellValue($alp[$col].'3', $key);
				$col++;
			}

			// add data table
			$line = 4;
			$temp = '';
			foreach ($entries['entries'] as $row) {
				$col = 0;
				foreach ($row as &$value) {
					if(is_array($value))
						if(isset($value[0]) AND is_array($value[0])){
							$arr = array();
							foreach ($value as $key => $subvalue)
								$arr[] = implode(", ", $subvalue);
							$value = implode(", ", $arr);
						} else
							$value = implode(", ", $value);

					$objPHPExcel->setActiveSheetIndex(0)
						->setCellValue($alp[$col].$line, $value);
					$col++;
				}
				$line++;
			}

			unset($temp);
			unset($arr);

			// Rename worksheet
			$objPHPExcel->getActiveSheet()->setTitle($stream->stream_slug);


			// Set active sheet index to the first sheet, so Excel opens this as the first sheet
			$objPHPExcel->setActiveSheetIndex(0);

			// Redirect output to a client’s web browser (Excel5)
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="Stream-'.$stream->stream_name.'.xls"');
			header('Cache-Control: max-age=0');
			// If you're serving to IE 9, then the following may be needed
			header('Cache-Control: max-age=1');

			// If you're serving to IE over SSL, then the following may be needed
			header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
			header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
			header ('Pragma: public'); // HTTP/1.0

			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$objWriter->save('php://output');
			exit;
		}
	}

	public function code($id = false)
	{
		if(is_numeric($id))
		{
			// get stream data
			$stream = $this->streams->streams->get_stream($id);
			
			// get assignment data
			$assignment = $this->streams->streams->get_assignments($stream->stream_slug, $stream->stream_namespace);

			$code = "Copy and paste this code once on top of install() function\n<code>\$this->load->driver('Streams');</code><br /><br />";
			$code .= "Then copy and paste this code into your module install() function.<br /><br />";

			if(!in_array($stream->stream_namespace, array('blogs','pages','users'))){
				$code .= "<code>// you might want to change the namespace \n// to prevent collision with existing streams or fields\n";
				$code .= "\$namespace = '{$stream->stream_namespace}';\n\n</code>";

				$code .= "<code>// Create stream\n";
				$code .= "\$extra = array('title_column' => '{$stream->title_column}', 'view_options' => ".str_replace(array("[","]",":"), array("array(",")","=>"), json_encode($stream->view_options)).", 'sorting' => '{$stream->sorting}', 'menu_path' => '{$stream->menu_path}', 'is_hidden' => '{$stream->is_hidden}');\n";

				// code for create stream
				$code .= "if( !\$this->streams->streams->add_stream('{$stream->stream_name}', '{$stream->stream_slug}', \$namespace, '{$stream->stream_prefix}', '{$stream->about}', \$extra) ) return FALSE; \n\n";

				// code for get stream
				$code .= "// Get stream data\n\${$stream->stream_slug} = \$this->streams->streams->get_stream('{$stream->stream_slug}', \$namespace);\n\n</code>";
			}

			// code for prepare fields
			$code .= "<code>// Add fields\n\$fields   = array();\n\$template = array('namespace' => \$namespace, 'assign' => '{$stream->stream_slug}');\n\n";
			foreach ($assignment as $value) {
				$code .= "\$fields[] = array('name'=>'{$value->field_name}', 'slug'=>'{$value->field_slug}', 'type'=>'{$value->field_type}', 'required' => ".($value->is_required == 'yes' ? "true":"false").", 'unique' => ".($value->is_unique == 'yes' ? "true":"false").", 'instructions' => '{$value->instructions}', 'extra'=>".str_replace(array("{","}",":",","), array("array(",")","=>",", "), json_encode(unserialize($value->field_data))).");\n";
			}

			// code for combine fields and submit them
			$code .= "\n// Combine\nforeach (\$fields AS &\$field) { \$field = array_merge(\$template, \$field); }\n\n// Add fields to stream\n\$this->streams->fields->add_fields(\$fields);";
			$code .= "</code>";

			// code for uninstall function
			$code .= "<br /><br />Copy and paste this code into your module uninstall() function.<br /><br />";
			$code .= "<code>// you might want to change the namespace \n// to prevent collision with existing streams or fields\n";
			$code .= "\$namespace = '{$stream->stream_namespace}';\n\n</code>";
			$code .= "<code>\$this->streams->streams->delete_stream('{$stream->stream_slug}', \$namespace);\n\n";
			foreach ($assignment as $value) {
				$code .= "\$this->streams->fields->delete_field('{$value->field_slug}', \$namespace);\n";
			}
			$code .="</code>";

			echo $code;
		}

		return false;
	}
	
}
