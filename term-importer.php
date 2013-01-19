<?php

function tim_term_importer(){
	$title = __('Term Importer');
	?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2><?php echo esc_html( $title ); ?></h2>
	<?php			
		
	if (defined('WPSC_FILE_DIR')) {
		tim_term_import_function();
	}

}
	function tim_term_importer_menu() {
		add_management_page('Term Importer', 'Term Importer', 'manage_options', 'term_importer', 'tim_term_importer');
	}
	
		add_action('admin_menu', 'tim_term_importer_menu');



/**
 * This file handles the standard importing of products through a csv file upload. Access this page via WP-admin Settings>Import
 * @package WP e-Commerce
 *
 * Look in /wpsc-admin/includes/product-functions.php
 * function wpsc_sanitise_product_forms()
 * To add more fields
 */
function tim_term_import_function() {
	global $wpdb;
?>
	<form name='cart_options' enctype='multipart/form-data' id='cart_options' method='post' action='<?php echo 'tools.php?page=term_importer'; ?>' class='wpsc_form_track'>
		<div class="wrap">
<?php _e( '<p>You can import terms from a vertical bar delimited text file.
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
											'name' => 'Term',
											'value' => 'term',
										),
										'1' => array(
											'name' => 'Slug',
											'value' => 'slug',											
										),
										'2' => array(
											'name' => 'Parent',
											'value' => 'parent',											
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
		$num = count( $cvs_data2['term'] );
 		$k = 0;
 		$m = 1;
 		
	for ( $j = 0; $j < $num; $k++ ) {

		// This means that it has done a loop but not added any categories.
		if($m == $j){
			$missed = $num - $j;
			echo "<br /><br />Error: Did not add (" . $missed . ") categories.";
			break;
		}

		$m = $j;	

		for ( $i = 0; $i < $num; $i++ ) {
			$data = array(
				'term' => esc_attr( $cvs_data2['term'][$i] ),
				'slug' => esc_attr( $cvs_data2['slug'][$i] ),
				'parent' => esc_attr( $cvs_data2['parent'][$i] ),
			);
			
			//$parent_term = term_exists( 'fruits', 'product' ); // array is returned if taxonomy is given
			//$parent_term_id = $parent_term['term_id']; // get numeric term id
			
			$parent_term = term_exists( $data['parent'] , 'wpsc_product_category' );
			$parent_term_id = $parent_term['term_id']; // get numeric term id
			
			if( $data['parent'] == '0' ){
				$is_ready = TRUE;
			}elseif( !is_null( term_exists( $data['parent'] , 'wpsc_product_category' )) ){
				$is_ready = TRUE;
			}else{
				$is_ready = FALSE;
			}
			
			if( is_null(term_exists( $data['slug'] , 'wpsc_product_category' )) && $is_ready){
				wp_insert_term(
  					$data['term'], // the term 
  					'wpsc_product_category', // the taxonomy
  					array(
    					'slug' => $data['slug'],
    					'parent' => $parent_term_id,
  					)
				);
				$j++; //This counts through the categories, loop stops when all added
			}
		}
			
	}
	
		echo "<br /><br />". sprintf(__("Success, your categories have been added.", "wpsc"));
		echo "<br />Number of loops " . $k . ".";
	}
?>
		</div>
	</form>
<?php
}



?>
