<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Export and import PyroStream schema from one site to another
 *
 * @author 		Toni Haryanto
 * @website		http://toniharyanto.net
 * @package 	PyroCMS
 * @subpackage 	PyroStream
 */
class stream_schema_m extends MY_Model {

	private $folder;

	public function __construct()
	{
		parent::__construct();
	}

	public function get_field($field_slug, $namespace){
		return $this->db->from('data_fields')
				->where('field_slug', $field_slug)
				->where('field_namespace', $namespace)
				->get()->row();
	}
	
	public function get_stream_slug($id){
		$stream = $this->db->select('stream_slug')
							->from('data_streams')
							->where('id', $id)
							->get()->row();
		if($stream)
			return $stream->stream_slug;
			
		return false;
	}
	
	public function get_stream_id($slug){
		$stream = $this->db->select('id')
							->from('data_streams')
							->where('stream_slug', $slug)
							->get()->row();
		if($stream)
			return $stream->id;
			
		return false;
	}

	public function get_assignment($stream_slug, $field_slug, $namespace){
		return $this->db->from('data_field_assignments fa')
				->join('data_streams s', 'fa.stream_id = s.id')
				->join('data_fields f', 'fa.field_id = f.id')
				->where('stream_slug', $stream_slug)
				->where('field_slug', $field_slug)
				->where('stream_namespace', $namespace)
				->get()->row();
	}

	public function get_namespaces(){
		$data = $this->db->distinct()->select('stream_namespace')->get('data_streams')->result();
		$namespace = array();
		foreach ($data as $value) {
			$namespace[$value->stream_namespace] = $value->stream_namespace;
		}
		return $namespace;
	}
}
