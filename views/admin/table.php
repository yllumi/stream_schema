	<?php if (!empty($stream_schema)): ?>
		<table border="0" class="table-list" cellspacing="0">
			<thead>
				<tr>
					<!-- <th><?php echo form_checkbox(array('name' => 'action_to_all', 'class' => 'check-all'));?></th> -->
					<th><?php echo lang('stream_schema:name'); ?></th>
					<th><?php echo lang('stream_schema:slug'); ?></th>
					<th><?php echo lang('stream_schema:namespace'); ?></th>
					<th><?php echo lang('stream_schema:prefix'); ?></th>
					<th style="width:200px"><?php echo lang('stream_schema:about'); ?></th>
					<th></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="7">
						<div class="inner"><?php $this->load->view('admin/partials/pagination'); ?></div>
					</td>
				</tr>
			</tfoot>
			<tbody>
				<?php foreach( $stream_schema as $item ): ?>
				<tr id="item_<?php echo $item->id; ?>">
					<!-- <td><?php echo form_checkbox('action_to[]', $item->id); ?></td> -->
					<td><?php echo $item->stream_name; ?></td>
					<td><?php echo $item->stream_slug; ?></td>
					<td><?php echo $item->stream_namespace; ?></td>
					<td><?php echo $item->stream_prefix; ?></td>
					<td><?php echo $item->about; ?></td>
					<td class="actions">
						<?php echo anchor('admin/stream_schema/backup/schema/'.$item->id, lang('stream_schema:backupschema'), array('class'=>'button', 'title'=>lang('stream_schema:schema_desc'))); ?>
						<!-- <?php echo anchor('admin/stream_schema/backup/data/'.$item->id, lang('stream_schema:backupdata'), array('class'=>'button', 'title'=>lang('stream_schema:data_desc'))); ?> -->
						<?php echo anchor('admin/stream_schema/code/'.$item->id, lang('stream_schema:code'), array('class'=>'modal button', 'title'=>lang('stream_schema:code_desc'))); ?>
						<?php echo anchor('admin/stream_schema/xls/'.$item->id, lang('stream_schema:xls'), array('class'=>'button', 'title'=>lang('stream_schema:xls_desc'))); ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else: ?>
		<div class="no_data"><?php echo lang('stream_schema:no_items'); ?></div>
	<?php endif;?>