<?php
/**
 * Plugin Name:          Attributes Performance Test
 * Description:          TODO
 */


const NUMBER_OF_ATTRIBUTES = 1;
const NUMBER_OF_TERMS      = 5;

function register_admin_menu() {
	$hook = add_management_page( 'Attributes Generator', 'Attributes Generator', 'install_plugins', 'attributesgenerator', 'render_admin_page' );
	add_action( "load-$hook", 'process_page_submit' );
}

function render_admin_page() {
	$number_of_attributes = NUMBER_OF_ATTRIBUTES;
	?>
  <h1>Attributes Performance Test</h1>
  <form method="post">
	<?php wp_nonce_field( 'generate', 'attributesgenerator_nonce' ); ?>
	<?php submit_button( "Generate $number_of_attributes global attributes", 'primary', 'generate_global_attributes' ); ?>
	<?php submit_button( "Generate $number_of_attributes products with local attributes", 'primary', 'generate_products_with_local_attributes' ); ?>
	<?php submit_button( "Generate $number_of_attributes products with existing global attributes", 'primary', 'generate_products_with_existing_global_attributes' ); ?>
	<?php submit_button( 'Delete all global attributes', 'primary', 'delete_all_global_attributes' ); ?>
	</form>
	<?php
}

function generate_global_attributes_and_products() {
	for ( $i = 0; $i < NUMBER_OF_ATTRIBUTES; $i++ ) {
		$attribute_name = "Generated attribute $i";
		$id             = wc_create_attribute( array( 'name' => $attribute_name ) );

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
		$terms = array();
		for ( $j = 0; $j < NUMBER_OF_TERMS; $j++ ) {
			$term_obj = wp_insert_term( "$attribute_name term $j", $taxonomy_name );
			$terms[] = $term_obj['term_id'];
		}

		$attribute = new \WC_Product_Attribute();
		$attribute->set_id( $id );
		$attribute->set_name( $attribute_name );
		$attribute->set_options( $terms );
		$product = new \WC_Product_Simple();
		$product->set_name( 'Test Creating Product' );
		$product->set_attributes(
			array( $attribute )
		);
		$product->save();

		// TODO find out why the attribute is not showing up
	}
}

function generate_products_with_local_attributes() {
	for ( $i = 0; $i < NUMBER_OF_ATTRIBUTES; $i++ ) {
		$product = new \WC_Product_Simple();
		$product->set_name( "Test Creating Product $i" );
		$attribute1 = new \WC_Product_Attribute();
		$attribute1->set_name( 'attribute 1' );
		$attribute1->set_options( array( 'option 1', 'option 2', 'option 3' ) );
		$attribute1->set_position( 1 );
		$attribute1->set_visible( true );
		$attribute1->set_variation( true );
		$attribute2 = new \WC_Product_Attribute();
		$attribute2->set_name( 'attribute 2' );
		$attribute2->set_options( array( 'option 1', 'option 2', 'option 3' ) );
		$attribute2->set_position( 1 );
		$attribute2->set_visible( true );
		$attribute2->set_variation( true );
		$attribute3 = new \WC_Product_Attribute();
		$attribute3->set_name( 'attribute 3' );
		$attribute3->set_options( array( 'option 1', 'option 2', 'option 3' ) );
		$attribute3->set_position( 1 );
		$attribute3->set_visible( true );
		$attribute3->set_variation( true );

		$product->set_attributes(
			array(
				$attribute1,
				$attribute2,
				$attribute3,
			)
		);
		$product->save();
	}
}

function generate_products_with_existing_global_attributes() {
	$attribute = wc_get_attribute( 7 );

	$attribute_object = new WC_Product_Attribute();

	$attribute_object->set_id( $attribute->id );
	$attribute_object->set_name( $attribute->name );
	$attribute_object->set_position( 0 );
	// $attribute_object->set_options();

	for ( $i = 0; $i < NUMBER_OF_ATTRIBUTES; $i++ ) {
		$product = new \WC_Product_Simple();
		$product->set_name( "Test Creating Product With Global Attributes $i" );
		$product->set_attributes(
			array( $attribute_object )
		);
		$product->save();
	}
}

function process_page_submit() {
	global $wpdb;
	if ( ! empty( $_POST['generate_global_attributes'] ) ) {
		generate_global_attributes_and_products();
	} elseif ( ! empty( $_POST['generate_products_with_local_attributes'] ) ) {
		generate_products_with_local_attributes();
	} elseif ( ! empty( $_POST['generate_products_with_existing_global_attributes'] ) ) {
		generate_products_with_existing_global_attributes();
	} elseif ( ! empty( $_POST['delete_all_global_attributes'] ) ) {
		$results = $wpdb->get_results( 'SELECT attribute_id FROM wp_woocommerce_attribute_taxonomies', ARRAY_N );
		foreach ( $results as $result ) {
			wc_delete_attribute( $result[0] );
		}
	}

}

add_action( 'admin_menu', 'register_admin_menu' );
