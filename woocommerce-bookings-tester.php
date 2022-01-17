<?php
/**
 * Plugin Name: WooCommerce Bookings Tester
 * Description: Boost WooCommerce Booking Speed
 * Version: 1.0.0
 * Author: Olek
 * Text Domain: woocommerce-bookings-tester
 * Tested up to: 5.8
 * WC tested up to: 5.9
 * WC requires at least: 2.6
 *
 * @package WooCommerce/Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce Booking Generator
 *
 * @since 1.0.0
 */
function woocommerce_bookings_tester() {
	if ( isset( $_REQUEST['testmode'] ) && 'true' === $_REQUEST['testmode'] ) {
		$id = isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '';

		create_orders_programmatically( $id );
	}
}

add_action( 'init', 'woocommerce_bookings_tester', 999 );

/**
 * Create Orders
 *
 * @since 1.0.0
 */
function create_orders_programmatically( $product_id ) {

	// Set numbers of orders to create.
	$number_of_orders = 10;
	$prev_booking     = false;
	$start_date       = new DateTime( '2022-01-22' );
	$end_date         = new DateTime( '2022-01-23' );

	for ( $i = 0; $i < $number_of_orders; $i++ ) {

		// Set order address.
		$address = array(
			'first_name' => 'Joe ' . rand( 1, $number_of_orders ),
			'last_name'  => 'Njenga',
			'company'    => 'njengah.com',
			'email'      => 'joe@example.com',
			'phone'      => '894-672-780',
			'address_1'  => '123 Main st.',
			'address_2'  => '100',
			'city'       => 'Nairobi',
			'state'      => 'Nairobi',
			'postcode'   => '00100',
			'country'    => 'KE',
		);

		// Now we create the order.
		$order = wc_create_order();

		// Add products randomly selected above to the order.
		$order->add_product( wc_get_product( $product_id ), 1 ); // This is an existing SIMPLE product.

		$order->set_address( $address, 'billing' );

		$order->calculate_totals();
		$order->update_status( 'Completed', 'Imported order', true );

		$order_id = trim( str_replace( '#', '', $order->get_order_number() ) );

		$new_booking_data = array(
			'start_date'  => $start_date,
			'end_date'    => $end_date,
			'resource_id' => '',
		);

		// Define this variables outside your function.
		$status = 'confirmed';
		$exact  = false;

		// Create post object.
		$my_post = array(
			'post_title'   => wp_strip_all_tags( 'Booking - ' . $i ),
			'post_name'    => wp_strip_all_tags( 'Booking - ' . $i ),
			'post_content' => '',
			'post_status'  => 'publish',
			'post_author'  => 1,
			'post_type'    => 'wc_booking',
			'post_status'  => 'paid',
			'post_parent'  => $order_id,
		);

		// Insert the post into the database.
		$id = wp_insert_post( $my_post );

		$product = wc_get_product( $product_id );

		if ( $product->has_person_qty_multiplier() && ! empty( $new_booking_data['persons'] ) ) {
			if ( is_array( $new_booking_data['persons'] ) ) {
				$qty = array_sum( $new_booking_data['persons'] );
			} else {
				$qty                         = $new_booking_data['persons'];
				$new_booking_data['persons'] = array( $qty );
			}
		}

		// If not set, use next available
		if ( ! $start_date ) {
			$min_date   = $product->get_min_date();
			$start_date = strtotime( "+{$min_date['value']} {$min_date['unit']}", current_time( 'timestamp' ) );
		}

		// If not set, use next available + block duration
		if ( ! $end_date ) {
			$end_date = strtotime( '+' . $product->get_duration() . ' ' . $product->get_duration_unit(), $start_date );
		}

		$searching = true;

		update_post_meta( $id, '_booking_all_day', 1 );
		update_post_meta( $id, '_booking_cost', 76 );
		update_post_meta( $id, '_booking_customer_id', 1 );
		update_post_meta( $id, '_booking_order_item_id', $order_id );
		update_post_meta( $id, '_booking_parent_id', 1365 );
		update_post_meta( $id, '_booking_product_id', $product_id );
		update_post_meta( $id, '_booking_resource_id', 0 );
		update_post_meta( $id, '_booking_start', $start_date->format( 'YmdHis' ) );
		update_post_meta( $id, '_booking_end', $end_date->format( 'YmdHis' ) );
		update_post_meta( $id, '_wc_bookings_gcalendar_event_id', 0 );
		update_post_meta( $id, '_local_timezone', '' );
		update_post_meta( $id, '_edit_lock', '1642383429:1' );

		if ( 0 == $i % 10 ) {
			$start_date->modify( '+2 day' );
			$end_date->modify( '+2 day' );
		}
	}
}
