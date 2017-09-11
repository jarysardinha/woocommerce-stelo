<?php
function stelo_get_template( $filename, $data = array(), $return = false ) {
	$filename = apply_filters( 'woocommerce_stelo_template_file', STELO_PATH . 'templates/' . $filename, $filename, $data, $return );
	$data = apply_filters( 'woocommerce_stelo_template_data', $data, $filename );

	extract( $data );

	if ( $return ) {
		ob_start();
	}

	include $filename;

	if ( $return ) {
		$content = ob_get_contents();

		ob_end_clean();

		return $content;
	}
}

function stelo_register_card_error() {
	$errorMessage = array(
		101 => __( 'The card\'s BIN was not accepted.', 'woocommerce-stelo' ),
		102 => __( 'The card\'s data are invalid.', 'woocommerce-stelo' ),
		401 => __( 'The was a failure on the authentication with Stelo, verify your credentials', 'woocommerce-stelo' ),
		400 => __( 'The necessary fields or headers were not provided.', 'woocommerce-stelo' )
	);

	return $errorMessage;
}