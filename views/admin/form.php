<section class="title">
	<!-- We'll use $this->method to switch between stream_schema.create & stream_schema.edit -->
	<h4><?php echo lang('stream_schema:'.$this->method); ?></h4>
</section>

<section class="item">
	<div class="content">
		<?php echo form_open_multipart($this->uri->uri_string(), 'class="crud"'); ?>

		<div class="form_inputs">

			<ul class="fields">
				<li>
				<label for="Title">Stream Backup File</label>
				<div class="input"><?php echo form_upload("file"); ?></div>
				</li>
			</ul>

		</div>

	<div class="buttons">
		<?php $this->load->view('admin/partials/buttons', array('buttons' => array('save', 'cancel') )); ?>
	</div>

	<?php echo form_close(); ?>
</div>
</section>