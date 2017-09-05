<?php
function stelo_get_template( $filename, $data = array(), $return = false ) {
	extract( $data );

	if ( $return ) {
		ob_start();
	}

	include STELO_PATH . 'templates/' . $filename;

	if ( $return ) {
		$content = ob_get_contents();

		ob_end_clean();

		return $content;
	}
}

function stelo_register_card_error() {
	$errorMessage = array(
		101 => "O BIN do cartão não foi aceito.",
		102 => "Os dados do cartão estão inválidos.",
		401 => "Houve falha na autenticação com a Stelo, verifique suas credenciais.",
		400 => "Os campos ou cabeçalhos obrigatórios não foram fornecidos."
	);

	return $errorMessage;
}