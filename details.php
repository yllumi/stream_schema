<?php defined('BASEPATH') or exit('No direct script access allowed');

class Module_Stream_Schema extends Module {

	/**
	 * Stream Schema module
	 *
	 * @author 	Toni Haryanto
	 * @website	http://toniharyanto.net
	 */

	public $version = '1.0.0';

	public function info()
	{
		return array(
			'name' => array(
				'en' => 'Stream Schema'
				),
			'description' => array(
				'en' => 'Export and import stream schema from one site to another'
				),
			'frontend' => false,
			'backend' => true,
			'menu' => 'structure', // You can also place modules in their top level menu. For example try: 'menu' => 'Stream Schema',
			'sections' => array(
				'items' => array(
					'name' 	=> 'stream_schema:items', // These are translated from your language file
					'uri' 	=> 'admin/stream_schema',
					'shortcuts' => array(
						'create' => array(
							'name' 	=> 'stream_schema:import',
							'uri' 	=> 'admin/stream_schema/import',
							'class' => 'add'
							)
						)
					)
				)
			);
	}

	public function install()
	{
		$this->dbforge->drop_table('stream_schema');

		$stream_schema = array(
			'id' => array(
				'type' => 'INT',
				'constraint' => '11',
				'auto_increment' => TRUE
				),
			'order' => array(
				'type' => 'INT',
				'constraint' => '11',
				'null' => true
				),
			'Title' => array(
				'type' => 'VARCHAR',
				'constraint' => '200',
				)
			);

		$this->dbforge->add_field($stream_schema);
		$this->dbforge->add_key('id', TRUE);

		if($this->dbforge->create_table('stream_schema') AND
		   //$this->db->insert('settings', $stream_schema_setting) AND
			is_dir($this->upload_path.'stream_schema') OR @mkdir($this->upload_path.'stream_schema',0777,TRUE))
		{
			return TRUE;
		}
	}

	public function uninstall()
	{
		$this->dbforge->drop_table('stream_schema');
		return TRUE;
	}


	public function upgrade($old_version)
	{
		// Your Upgrade Logic
		return TRUE;
	}

	public function help()
	{
		// Return a string containing help info
		// You could include a file and return it here.
		$doc = "<h3>Stream Schema Module Help</h3>
		<p>The Stream Schema module let you export and import stream table schema from one PyroCMS installation to another, so you don't have to make the same stream you have made by PyroStream repeatedly.</p>
		<p>You also can backup stream data into .xls file format. Especially for PyroCMS developers you can take advantage of PyroStream power to make your database schema easily for your self made module.</p>
		<p>To export stream schema, simply click into \"Schema\" button in any stream row. It will be downloaded as txt file so you can import into another PyroCMS installation by clicking \"Import Stream\" button and upload the txt file.</p>
		<p>You can also export stream data to xls file format by clicking \".xls\" button in any stream row.</p>
		<p>If you are PyroCMS developer, you can use code generator in this module for installing stream table you want to put as a requirement for your own module. Make sure you have read all the descriptions and comments in that code. To use this feature simply click the \"Code\" button in any stream row and it will showing the code. Then you can copy and paste it to your details.php file of your module.</p>";

		return $doc;
	}
}
/* End of file details.php */
