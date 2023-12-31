<?php
/**
 * Plugin Name:          Attributes Performance Test
 * Description:          Helper to generate products with local attributes and global attributes
 */


const NUMBER_OF_TERMS_PER_ATTR = 5;

function register_admin_menu() {
	$hook = add_management_page( 'Attributes Generator', 'Attributes Generator', 'install_plugins', 'attributesgenerator', 'render_admin_page' );
	add_action( "load-$hook", 'process_page_submit' );
}

function render_admin_page() {  ?>
	<h1>Attributes Performance Test</h1>
	<form method="post">
	<?php wp_nonce_field( 'generate', 'attributesgenerator_nonce' ); ?>
		<label for="number_of_products">Number to generate</label>
		<input type="number" name="number_of_products" />
		<label for="start_index">Start index</label>
		<input type="number" name="start_index" />
		<?php submit_button( 'Generate only global attributes', 'primary', 'generate_global_attributes' ); ?>
		<?php submit_button( 'Generate global attributes and products', 'primary', 'generate_global_attributes_and_products' ); ?>
		<?php submit_button( 'Generate products with local attributes', 'primary', 'generate_products_with_local_attributes' ); ?>
		<?php submit_button( 'Delete all global attributes', 'primary', 'delete_all_global_attributes' ); ?>
		<label for="product_id">Product ID</label>
		<input type="number" name="product_id" />
		<?php submit_button( 'Attach global attributes to product', 'primary', 'attach_to_product' ); ?>
	</form>
	<?php
}

function generate_global_attributes( $number_of_products, $start_index ) {
	for ( $i = $start_index; $i < $start_index + $number_of_products; $i++ ) {
		$attribute_name = "Generated attribute $i";
		wc_create_attribute( array( 'name' => $attribute_name ) );
		$slug          = wc_sanitize_taxonomy_name( $attribute_name );
		$taxonomy_name = wc_attribute_taxonomy_name( $slug );

		register_taxonomy(
			$taxonomy_name,
			apply_filters( 'woocommerce_taxonomy_objects_' . $taxonomy_name, array( 'product' ) ),
			apply_filters(
				'woocommerce_taxonomy_args_' . $taxonomy_name,
				array(
					'labels'       => array(
						'name' => $attribute_name,
					),
					'hierarchical' => true,
					'show_ui'      => false,
					'query_var'    => true,
					'rewrite'      => false,
				)
			)
		);
		for ( $j = 0; $j < NUMBER_OF_TERMS_PER_ATTR; $j++ ) {
			wp_insert_term( "$attribute_name term $j", $taxonomy_name );
		}
	}
}

function generate_global_attributes_and_products( $number_of_products, $start_index ) {
	for ( $i = $start_index; $i < $start_index + $number_of_products; $i++ ) {
		$attribute_name = "Generated attribute $i";
		$id             = wc_create_attribute( array( 'name' => $attribute_name ) );
		$slug           = wc_sanitize_taxonomy_name( $attribute_name );
		$taxonomy_name  = wc_attribute_taxonomy_name( $slug );

		register_taxonomy(
			$taxonomy_name,
			apply_filters( 'woocommerce_taxonomy_objects_' . $taxonomy_name, array( 'product' ) ),
			apply_filters(
				'woocommerce_taxonomy_args_' . $taxonomy_name,
				array(
					'labels'       => array(
						'name' => $attribute_name,
					),
					'hierarchical' => true,
					'show_ui'      => false,
					'query_var'    => true,
					'rewrite'      => false,
				)
			)
		);
		$terms = array();
		for ( $j = 0; $j < NUMBER_OF_TERMS_PER_ATTR; $j++ ) {
			$term_obj = wp_insert_term( "$attribute_name term $j", $taxonomy_name );
			$terms[]  = $term_obj['term_id'];
		}

		$attribute = new \WC_Product_Attribute();
		$attribute->set_id( $id );
		$attribute->set_name( $taxonomy_name );
		$attribute->set_options( $terms );
		$attribute->set_visible( true );
		$attribute->set_variation( true );
		$product = new \WC_Product_Simple();
		$product->set_name( "Product with global attribute $i" );
		$product->set_attributes(
			array( $attribute )
		);
		$product->save();
	}
}

function generate_products_with_local_attributes( $number_of_products, $start_index ) {
	for ( $i = $start_index; $i < $start_index + $number_of_products; $i++ ) {
		$product = new \WC_Product_Simple();
		$product->set_name( "Product with local attributes $i" );

		$attributes = array();
		for ( $j = 0; $j < NUMBER_OF_TERMS_PER_ATTR; $j++ ) {
			$attribute = new \WC_Product_Attribute();
			$attribute->set_name( "attribute $j" );
			$attribute->set_options( array( 'option 1', 'option 2', 'option 3', 'option 4', 'option 5' ) );
			$attribute->set_position( 1 );
			$attribute->set_visible( true );
			$attribute->set_variation( true );
			$attributes[] = $attribute;
		}
		$product->set_attributes( $attributes );

		$product->save();
	}
}

function delete_all_attributes() {
	global $wpdb;
	$results = $wpdb->get_results( 'SELECT attribute_id FROM wp_woocommerce_attribute_taxonomies', ARRAY_N );
	foreach ( $results as $result ) {
		wc_delete_attribute( $result[0] );
	}
}

function attach_to_product( $number, $start_index, $product_id ) {
	global $wpdb;

	$product = wc_get_product( $product_id );

	$attributes = array();

	for ( $i = $start_index; $i < $start_index + $number; $i++ ) {
		$name          = "Generated attribute $i";
		$results       = $wpdb->get_results( "SELECT attribute_id FROM wp_woocommerce_attribute_taxonomies WHERE attribute_label = '$name'" );
		$slug          = wc_sanitize_taxonomy_name( $name );
		$taxonomy_name = wc_attribute_taxonomy_name( $slug );

		$attribute_data = wc_get_attribute( $results[0]->attribute_id );
		$attribute_obj  = new \WC_Product_Attribute();
		$attribute_obj->set_name( $taxonomy_name );
		$attribute_obj->set_id( $attribute_data->id );

		$terms = get_terms( $taxonomy_name, 'orderby=name&hide_empty=0' );

		$attribute_obj->set_options(
			array_map(
				function( $term ) {
					return $term->term_id;
				},
				$terms
			)
		);
		$attribute_obj->set_position( 1 );
		$attribute_obj->set_visible( true );
		$attribute_obj->set_variation( false );

		$attributes[] = $attribute_obj;
	}

	$product->set_attributes( $attributes );
	$product->save();
}

function process_page_submit() {
	if ( ! empty( $_POST['generate_global_attributes_and_products'] ) ) {
		generate_global_attributes_and_products( intval( $_POST['number_of_products'] ), intval( $_POST['start_index'] ) );
	} elseif ( ! empty( $_POST['generate_products_with_local_attributes'] ) ) {
		generate_products_with_local_attributes( intval( $_POST['number_of_products'] ), intval( $_POST['start_index'] ) );
	} elseif ( ! empty( $_POST['delete_all_global_attributes'] ) ) {
		as_enqueue_async_action( 'attributes_generator_delete_all_attributes' );
	} elseif ( ! empty( $_POST['generate_global_attributes'] ) ) {
		generate_global_attributes( intval( $_POST['number_of_products'] ), intval( $_POST['start_index'] ) );
	} elseif ( ! empty( $_POST['attach_to_product'] ) ) {
		attach_to_product( intval( $_POST['number_of_products'] ), intval( $_POST['start_index'] ), intval( $_POST['product_id'] ) );
	}

}

add_action( 'admin_menu', 'register_admin_menu' );
add_action( 'attributes_generator_generate_global_attributes_and_products', 'generate_global_attributes_and_products' );
add_action( 'attributes_generator_delete_all_attributes', 'delete_all_attributes' );
