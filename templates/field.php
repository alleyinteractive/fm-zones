<?php
if ( is_array( $value ) ) {
	$current_ids = array_map( 'intval', $value );
} else {
	$current_ids = array();
}
?>

<div class="fm-zone-posts-wrapper<?php $this->maybe_connect() ?>" data-current="<?php echo esc_attr( $this->get_current_posts_json( $current_ids ) ) ?>" data-limit="<?php echo absint( $this->post_limit ) ?>" data-placeholders="<?php echo intval( $this->placeholders ) ?>">
	<input type="hidden" class="fm-element zone-name" name="<?php echo esc_attr( $this->get_form_name() ); ?>" value="" />

	<div class="zone-search-wrapper">
		<label for="<?php echo esc_attr( $this->get_element_id() ); ?>_recent"><?php
			echo esc_html( sprintf(
				__( 'Add recent %s', 'fm-zones' ),
				apply_filters( 'fm-zones-content-label', __( 'content', 'fm-zones' ) )
			) ); ?></label><br>
		<select class="zone-post-latest" id="<?php echo esc_attr( $this->get_element_id() ); ?>_recent">
			<option value=""><?php esc_html_e( 'Choose a post', 'fm-zones' ); ?></option>
			<?php foreach ( $this->get_recent_posts( $current_ids ) as $post ) : ?>
				<option value="<?php echo esc_attr( json_encode( $post ) ) ?>" data-post-id="<?php echo intval( $post['id'] ) ?>"><?php echo esc_html( $post['title'] ) ?></option>
			<?php endforeach ?>
		</select>
	</div>

	<div class="zone-search-wrapper">
		<label for="<?php echo esc_attr( $this->get_element_id() ); ?>_search"><?php
			echo esc_html( sprintf(
				__( 'Search for %s', 'fm-zones' ),
				apply_filters( 'fm-zones-content-label', __( 'content', 'fm-zones' ) )
			) ); ?></label>
		<input type="text" class="zone-post-search" id="<?php echo esc_attr( $this->get_element_id() ); ?>_search" <?php echo $this->get_element_autocomplete_attributes(); ?> />
		<p class="description"><?php
			echo esc_html( sprintf(
				__( 'Enter a term or phrase in the text box above to search for and add %s to this zone.', 'fm-zones' ),
				apply_filters( 'fm-zones-content-label', __( 'content', 'fm-zones' ) )
			) ); ?></p>
	</div>

	<script type="text/template" class="fmz-post-template">
		<div id="zone-post-<%= id %>" class="zone-post" data-post-id="<%- id %>">
			<table>
				<tr>
					<td class="zone-post-col zone-post-position"><%- i %></td>
					<td class="zone-post-col zone-post-thumbnail">
						<% if ( thumb ) { %>
							<img src="<%- thumb %>" />
						<% } %>
					</td>
					<td class="zone-post-col zone-post-info">
						<%- title %> <span class="zone-post-status"><%- post_status %></span>
						<div class="row-actions">
							<a href="<?php echo esc_url( admin_url( 'post.php?action=edit&post=' ) ) ?><%- id %>" class="edit" target="_blank" title="<?php esc_attr_e( 'Opens in new window', 'fm-zones' ); ?>"><?php esc_html_e( 'Edit', 'fm-zones' ); ?></a>
							| <a href="#" class="delete" title="<?php esc_attr_e( 'Remove this item from the zone', 'fm-zones' ); ?>"><?php esc_html_e( 'Remove', 'fm-zones' ); ?></a>
							| <a href="<%- link %>" class="view" target="_blank" title="<?php esc_attr_e( 'Opens in new window', 'fm-zones' ); ?>"><?php esc_html_e( 'View', 'fm-zones' ); ?></a>
						</div>
					</td>
					<td class="zone-post-col zone-post-post_type"><%- post_type %></td>
				</tr>
			</table>
			<input type="hidden" class="fm-element" name="<?php echo esc_attr( $this->get_form_name( '[]' ) ); ?>" value="<%- id %>" />
		</div>
	</script>

	<div class="zone-posts-list">
	</div>

	<?php foreach ( $current_ids as $id ) : ?>
		<input type="hidden" class="fm-element fmz-remove-if-js" name="<?php echo esc_attr( $this->get_form_name( '[]' ) ); ?>" value="<?php echo absint( $id ) ?>" />
	<?php endforeach ?>
</div>
