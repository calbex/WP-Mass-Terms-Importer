<?php
/*
Plugin Name: Tag Importer
Plugin URI: 
Description: Bulk import post and product tags
Author: Caleb Millar
Version: 1.0
Author URI: http://caleb.ntm.bz/
*/

/**
*	Include other plugin files
*/
function tim_load_files() {
	require_once( 'term-importer.php' );
}
tim_load_files();

function tim_tag_importer(){
	$title = __('Tag Importer');
	?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2><?php echo esc_html( $title ); ?></h2>
	<?php			
		
	if (defined('WPSC_FILE_DIR')) {
		tim_tag_import_function();
	}

	}
	function tim_tag_importer_menu() {
		add_management_page('Tag Importer', 'Tag Importer', 'manage_options', 'tag_importer', 'tim_tag_importer');
	}
	
		add_action('admin_menu', 'tim_tag_importer_menu');



/**
 * This file handles the standard importing of products through a csv file upload. Access this page via WP-admin Settings>Import
 * @package WP e-Commerce
 *
 * Look in /wpsc-admin/includes/product-functions.php
 * function wpsc_sanitise_product_forms()
 * To add more fields
 */
function tim_tag_import_function() {
	global $wpdb;
?>
	<form name='cart_options' enctype='multipart/form-data' id='cart_options' method='post' action='<?php echo 'tools.php?page=tag_importer'; ?>' class='wpsc_form_track'>
		<div class="wrap">
<?php _e( '<p>You can import tags from a vertical bar delimited text file.
</p>', 'wpsc' ); ?>

<?php wp_nonce_field( 'update-options', 'wpsc-update-options' ); ?>
		<input type='hidden' name='MAX_FILE_SIZE' value='5000000' />
		<input type='file' name='csv_file' />
		<input type='submit' value='Import' class='button-primary'>
<?php
		if ( isset( $_FILES['csv_file']['name'] ) && ($_FILES['csv_file']['name'] != '') ) {
			ini_set( "auto_detect_line_endings", 1 );
			$file = $_FILES['csv_file'];
			if ( move_uploaded_file( $file['tmp_name'], WPSC_FILE_DIR . $file['name'] ) ) {
				$content = file_get_contents( WPSC_FILE_DIR . $file['name'] );
				$handle = @fopen( WPSC_FILE_DIR . $file['name'], 'r' );
				while ( ($csv_data = @fgetcsv( $handle, filesize( $handle ), "|" )) !== false ) {
					$fields = count( $csv_data );
					for ( $i = 0; $i < $fields; $i++ ) {
						if ( !is_array( $data1[$i] ) ) {
							$data1[$i] = array( );
						}
						array_push( $data1[$i], $csv_data[$i] );
					}
				}

				$_SESSION['cvs_data'] = $data1;
?>

				<p><?php _e( 'For each column, select the field it corresponds to in \'Belongs to\'. You can upload as many tags as you like.', 'wpsc' ); ?></p>
				<div class='metabox-holder' style='width:90%'>
					<input type='hidden' name='csv_action' value='import'>

<?php
				foreach ( (array)$data1 as $key => $datum ) {
?>
					<div style='width:100%;' class='postbox'>
						<h3 class='hndle'><?php printf(__('Column (%s)', 'wpsc'), ($key + 1)); ?></h3>
						<div class='inside'>
							<table>
								<tr><td style='width:80%;'>
										<input type='hidden' name='column[]' value='<?php echo $key + 1; ?>'>
								<?php
								foreach ( $datum as $column ) {
									echo $column;
									break;
								} ?>
								<br />
							</td><td>
								<select  name='value_name[]'>


									<?php
								
									// This array makes up the select boxes for the CSV fields
								
									$options = array(
										'0' => array(
											'name' => 'ID',
											'value' => 'post_id',
										),
										'1' => array(
											'name' => 'Tag Type',
											'value' => 'post_tag_type',
										),
										'2' => array(
											'name' => 'Tags',
											'value' => 'post_tags',
										),
									);
								?>
								
						<!-- /* These are the current fields that can be imported with products, to add additional fields add more <option> to this dorpdown list */ -->

						
									<?php foreach($options as $opkey => $option): ?>
										<?php 	if( $opkey == $key){
													$selected = ' selected="selected"';
												}else{
													$selected = '';
												}?>
										<option value='<?php echo $option['value']; ?>'<?php echo $selected; ?>><?php _e($option['name'], 'wpsc'); ?></option>
									<?php endforeach; ?>
								

								</select>
							</td></tr>
					</table>
				</div>
			</div>
<?php } ?>
					
			<input type='submit' value='Import' class='button-primary'>
		</div>
<?php
		} else {
			echo "<br /><br />" . __('There was an error while uploading your csv file.', 'wpsc');
		}
	}
	if ( isset( $_POST['csv_action'] ) && ('import' == $_POST['csv_action']) ) {
		global $wpdb;
		$cvs_data = $_SESSION['cvs_data'];
		$column_data = $_POST['column'];
		$value_data = $_POST['value_name'];
		
		$status = esc_attr($_POST['post_status']);
		
		$name = array( );
		foreach ( $value_data as $key => $value ) {

			$cvs_data2[$value] = $cvs_data[$key];
		}
		$num = count( $cvs_data2['post_id'] );

		for ( $i = 0; $i < $num; $i++ ) {
			$data = array(
				'post_id' => esc_attr( $cvs_data2['post_id'][$i] ),
				'tags' => esc_attr( $cvs_data2['post_tags'][$i] ),
				'tag_type' => esc_attr( $cvs_data2['post_tag_type'][$i] ),
			);
			
			if( $data['tag_type'] == 'product_tag' || $data['tag_type'] == 'wpsc_product_category' ){
				
				$post_slug = $data['post_id'];

				$args = array(
  					'name' => $post_slug,
  					'post_type' => 'wpsc-product',
  					'post_status' => 'publish',
  					'numberposts' => 1
				);
				$post = get_posts($args);
				$post_id = $post[0]->ID;
				
			}else{

				$post_id = $data['post_id'];
				
			}
			
			// Tags Import, this should be the slug of the term
			$post_tags_import = $data['tags'];
			$post_tags = explode(",",$post_tags_import);
			// Taxonomy type
			$post_tag_type = $data['tag_type'];
			
			
			wp_set_object_terms( $post_id , $post_tags , $post_tag_type );
			
		}		
		echo "<br /><br />". sprintf(__("Success, your tags have been added.", "wpsc"));
	}
?>
		</div>
	</form>
<?php
}



?>
