<?php
if ( is_array( $value ) ) {
	$current_ids = array_map( 'intval', $value );
} else {
	$current_ids = array();
}
?>

<div class="fm-zone-posts-wrapper">

	<div class="zone-search-wrapper">
		<label for="<?php echo esc_attr( $this->get_element_id() ); ?>_recent"><?php esc_html_e( 'Add Recent Content', 'fm-zoninator' ); ?></label><br>
		<select name="search-posts" class="zone-post-latest" id="<?php echo esc_attr( $this->get_element_id() ); ?>_recent">
			<option value=""><?php esc_html_e( 'Choose a post', 'fm-zoninator' ); ?></option>
			<?php foreach ( $this->get_recent_posts( $current_ids ) as $post ) : ?>
				<option value="<?php echo esc_attr( json_encode( $post ) ) ?>"><?php echo esc_html( $post['title'] ) ?></option>
			<?php endforeach ?>
		</select>
	</div>

	<div class="zone-search-wrapper">
		<label for="<?php echo esc_attr( $this->get_element_id() ); ?>_search"><?php esc_html_e( 'Search for content', 'fm-zoninator' );?></label>
		<input type="text" class="zone-post-search" id="<?php echo esc_attr( $this->get_element_id() ); ?>_search" name="search" />
		<p class="description"><?php esc_html_e( 'Enter a term or phrase in the text box above to search for and add content to this zone.', 'fm-zoninator' ); ?></p>
	</div>

	<script type="text/template" class="fmz-post-template">
		<div id="zone-post-<%= id %>" class="zone-post" data-post-id="<%= id %>">
			<table>
				<tr>
					<td class="zone-post-col zone-post-position"><%= i %></td>
					<td class="zone-post-col zone-post-thumbnail">
						<%= thumb %>
					</td>
					<td class="zone-post-col zone-post-info">
						<%= title %> <span class="zone-post-status"><%= status %></span>
						<div class="row-actions">
							<a href="<?php echo esc_url( admin_url( 'post.php?action=edit&post=' ) ) ?><%= id %>" class="edit" target="_blank" title="<?php esc_attr_e( 'Opens in new window', 'fm-zoninator' ); ?>"><?php esc_html_e( 'Edit', 'fm-zoninator' ); ?></a>
							| <a href="#" class="delete" title="<?php esc_attr_e( 'Remove this item from the zone', 'fm-zoninator' ); ?>"><?php esc_html_e( 'Remove', 'fm-zoninator' ); ?></a>
							| <a href="<%= link %>" class="view" target="_blank" title="<?php esc_attr_e( 'Opens in new window', 'fm-zoninator' ); ?>"><?php esc_html_e( 'View', 'fm-zoninator' ); ?></a>
						</div>
					</td>
					<td class="zone-post-col zone-post-post_type"><%= type %></td>
				</tr>
			</table>
			<input type="hidden" name="<?php echo esc_attr( $this->get_form_name() ); ?>[]" value="<%= id %>" />
		</div>
	</script>
	<script type="text/javascript">
		var fmz_initial_posts = <?php $this->the_current_posts_json( $current_ids ) ?>;
	</script>

	<div class="zone-posts-list"></div>

</div>