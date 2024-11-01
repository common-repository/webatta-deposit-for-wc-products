<?php
/**
 * Plugin Name: Webatta - Deposit price for WooCommerce products
 * Plugin URI: http://blog.webatta.hu/en-us/wordpress-plugins/deposit-for-woocommerce-products/
 * Description: You can add deposit price to simple, variable, downloadable and virtual WooCommerce products.
 * Version: 1.2
 * Author: Webatta
 * Author URI: http://www.webatta.hu
 * Text Domain: webatta-deposit
 * Domain Path: /languages
 */


/**
* LOAD TRANSLATIONS
*/
function webatta_deposit_plugins_loaded() {
        load_plugin_textdomain( 'webatta-deposit', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }
add_action( 'plugins_loaded', 'webatta_deposit_plugins_loaded', 0 );



/**
* GENERAL PRODUCTS
*/


/**
 * Add a custom field for the deposit price to the products general tab.
*/
function webatta_add_custom_fields() {

        global $woocommerce, $post;

        woocommerce_wp_text_input( 
            array( 
                'id'          => '_deposit_price', 
                'label'       => __( 'Deposit price(€)', 'webatta-deposit' ), 
                'placeholder' => '10.20',
                'desc_tip'    => 'true',
                'description' => __( 'Enter the custom deposit price here.', 'webatta-deposit' ) 
            )
        );

}

add_action( 'woocommerce_product_options_general_product_data', 'webatta_add_custom_fields');



/**
 * Save the value of the custom field as metadata to the product.
*/
function webatta_custom_fields_save( $post_id ){

        $woocommerce_text_field = sanitize_text_field( $_POST['_deposit_price'] );

        if( is_numeric( $woocommerce_text_field ) || empty( $woocommerce_text_field ) ){
            update_post_meta( $post_id, '_deposit_price', esc_attr( $woocommerce_text_field ) );
        }

    }

    add_action( 'woocommerce_process_product_meta', 'webatta_custom_fields_save' );


/**
* ----------END OF GENERAL PRODUCTS----------
*/


/**
* ----------VARIABLE PRODUCTS----------
*/

/*
 * Add our Custom Fields to variable products
 */
function webatta_add_custom_variation_fields( $loop, $variation_data, $variation ) {

	echo '<div class="options_group form-row form-row-full">';

 	// Text Field
	woocommerce_wp_text_input(
		array(
			'id'          => '_deposit_price[' . $variation->ID . ']',
			'label'       => __( 'Deposit price(€)', 'wa-deposit' ),
			'placeholder' => '0.5',
			'desc_tip'    => true,
			'description' => __( "Enter the custom deposit price here.", "woocommerce" ),
			'value' => get_post_meta( $variation->ID, '_deposit_price', true )
		)
 	);

	// Add extra custom fields here as necessary...

	echo '</div>';

}
add_action( 'woocommerce_variation_options_pricing', 'webatta_add_custom_variation_fields', 10, 3 ); // After Price fields

/*
 * Save our variable product fields
 */
function webatta_add_custom_variation_fields_save( $post_id ){

 	// Text Field
 	$woocommerce_text_field = sanitize_text_field($_POST['_deposit_price'][ $post_id ]);
	if( is_numeric( $woocommerce_text_field ) || empty( $woocommerce_text_field ) ){
           update_post_meta( $post_id, '_deposit_price', esc_attr( $woocommerce_text_field ) );
        }

}
add_action( 'woocommerce_save_product_variation', 'webatta_add_custom_variation_fields_save', 10, 2 );



/**
* ----------END OF VARIABLE PRODUCTS----------
*/

/**
 * Filter woocommerce_get_price, if the product has a deposit price set we return that instead of the normal product price. If no deposit price was set we return 0.
*/
function webatta_filter_woocommerce_get_price( $price, $product ){
        $product_id = $product->get_id();
        $deposit_price = get_post_meta( $product_id, '_deposit_price', true );
        $product_price = get_post_meta( $product_id, '_price', true );


if( ! empty( $deposit_price )  ) {

            return $deposit_price;
        }
else{
        return $product_price; 
}
}

    add_filter( 'woocommerce_get_price', 'webatta_filter_woocommerce_get_price', 10, 2 );
    add_filter( 'woocommerce_product_get_price', 'webatta_filter_woocommerce_get_price', 10, 2 );
    add_filter( 'woocommerce_product_variation_get_price', 'webatta_filter_woocommerce_get_price', 10, 2 );


/**
 * Filter several values so that the price is set like this €34.50 Deposit: €10.00 in product page and checkout page.
*/
function webatta_filter_woocommerce_get_price_html( $price, $product ){

        $product_id = $product->get_id();
        $deposit_price = get_post_meta( $product_id, '_deposit_price', true );
        $product_price = get_post_meta( $product_id, '_price', true );



        if( ! empty( $deposit_price )  ) {

            return wc_price($product_price) . '<br/><i>' . esc_html(__('Deposit:', 'webatta-deposit')) . wc_price($deposit_price) . '</i>';
        }

        return wc_price( $product_price ); 

}

    add_filter( 'woocommerce_get_price_html', 'webatta_filter_woocommerce_get_price_html', 10, 2 );
    add_filter( 'woocommerce_cart_product_price', 'webatta_filter_woocommerce_get_price_html', 10, 2 );
    add_filter( 'woocommerce_variable_price_html', 'webatta_filter_woocommerce_get_price_html', 10, 2 );


function webatta_filter_woocommerce_cart_product_subtotal( $product_subtotal, $product, $quantity ){

        $product_id = $product->get_id();
        $deposit_price = get_post_meta( $product_id, '_deposit_price', true );
        $product_price = get_post_meta( $product_id, '_price', true );
	
        if( ! empty( $deposit_price )  ) {
	    $balance_price = $product_price - $deposit_price;
            return wc_price( $product_price * $quantity ) . '<br/><i>' . esc_html(__('Deposit:', 'webatta-deposit'))  . wc_price( $deposit_price * $quantity ) . '</i><br/><i>' . esc_html(__('Balance:', 'webatta-deposit'))  . wc_price( $balance_price * $quantity ) . '</i>';
        }

        return wc_price( $product_price * $quantity ); 

    }

add_filter( 'woocommerce_cart_product_subtotal', 'webatta_filter_woocommerce_cart_product_subtotal', 10, 3 );


add_action( 'woocommerce_cart_totals_before_shipping', 'webatta_display_cart_balance', 20 );
add_action( 'woocommerce_review_order_before_shipping', 'webatta_display_cart_balance', 20 );
function webatta_display_cart_balance() {
    $total_price_w = 0;
    $tot = WC()->cart->total;

    // Loop through cart items and calculate total volume
    foreach( WC()->cart->get_cart() as $cart_item ){
        $product_price_w = get_post_meta( $cart_item['product_id'], '_price', true );
	$total_price_w += $product_price_w * $cart_item['quantity'];
    }
    
    $total_deposit_w = $total_price_w - $tot;
    if( $total_deposit_w > 0 ){

        // The Output
        echo ' <tr class="cart-total-volume">
            <th>' .esc_html(__( "Balance", "webatta-deposit" )). '</th>
            <td data-title="total-balance">' . wc_price( $total_deposit_w ) .'</td>
        </tr>';
    }
}


?>