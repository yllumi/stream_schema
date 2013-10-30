<section class="title">
	<h4><?php echo lang('stream_schema:item_list'); ?></h4>
</section>

<section class="item">
	<div class="content">
		<fieldset id="filters">
			<legend>Filters</legend>
			<ul>
				<li class="">
					<label for="f_status">Stream Namespace</label>
					<?php echo form_dropdown('namespace', $namespace, 'streams', 'id="namespace"'); ?>
					
				</li>
			</ul>
		</fieldset>

	<div id="stream-table" class="streams">
		<?php echo $datatable; ?>
	</div>

	<script>
		$('#namespace').change(function(){
			var namespace = $(this).val();
			var oldnamespace = $('#stream-table').attr('class');
			if(oldnamespace != namespace){
				$('#stream-table')
				.fadeOut(200)
				.empty()
				.load(BASE_URL + 'admin/stream_schema/table_ajax/' + $(this).val()).fadeIn()
				.removeClass(oldnamespace)
				.addClass(namespace);
							
			}
			return false;
		});
	</script>
	<style>.button{display:inline-block !important;}</style>

</div>
</section>