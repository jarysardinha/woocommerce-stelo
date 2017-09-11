
<fieldset id="stelo-payment-form" class="woocommerce-stelo-form">
	<ul id="stelo-payment-methods">
		<?php foreach ($methods as $method): ?>
		<?php if ( 'credit_card' == $method ) : ?>
			<li><label><input id="stelo-payment-method-credit-card" type="radio" name="stelo_payment_method" value="credit-card" <?php checked( true, ( 'credit_card' == $method ), true ); ?> /> <?php _e( 'Credit Card', 'woocommerce-stelo' ); ?></label></li>
		<?php endif; ?>

		<?php if ( 'bankslip' == $method ) : ?>
			<li><label><input id="stelo-payment-method-banking-ticket" type="radio" name="stelo_payment_method" value="bankslip" <?php checked( true, ( 'bankslip' == $method ), true ); ?> /> <?php _e( 'Bankslip', 'woocommerce-stelo' ); ?></label></li>
		<?php endif; ?>
		<?php endforeach; ?>
	</ul>
	<div class="clear"></div>
	<?php foreach ($methods as $method): ?>
	<?php if ( 'credit_card' == $method ) : ?>
		<div id="stelo-credit-card-form" class="stelo-method-form">
			<input type="hidden" id="stelo-card-idUnico" name="stelo_card_idUnico" value="<?php echo $idUnico; ?>">
			<input type="hidden" id="stelo-card-token" name="stelo_card_token" value="">
			<p id="stelo-card-holder-name-field" class="form-row form-row-wide">
				<label for="stelo-card-holder-name"><?php _e( 'Name', 'woocommerce-stelo' ); ?> <small>(<?php _e( 'as printed on the card', 'woocommerce-stelo' ); ?>)</small> <span class="required">*</span></label>
				<input id="stelo-card-holder-name" name="stelo_card_holder_name" class="input-text" type="text" autocomplete="off" style="font-size: 1.5em; padding: 8px;" />
			</p>
			<p id="stelo-card-number-field" class="form-row form-row-wide">
				<label for="stelo-card-number"><?php _e( 'Card number', 'woocommerce-stelo' ); ?> <span class="required">*</span></label>
				<input id="stelo-card-number" class="input-text wc-credit-card-form-card-number" type="tel" maxlength="20" autocomplete="off" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" style="font-size: 1.5em; padding: 8px;" />
			</p>
			<div class="clear"></div>
			<p id="stelo-card-expiry-field" class="form-row form-row-first">
				<label for="stelo-card-expiry"><?php _e( 'Exp. Date', 'woocommerce-stelo' ); ?> <span class="required">*</span></label>
				<input id="stelo-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="tel" autocomplete="off" placeholder="<?php _e( 'MM / YYYY', 'woocommerce-stelo' ); ?>" style="font-size: 1.5em; padding: 8px;" />
			</p>
			<p id="stelo-card-cvc-field" class="form-row form-row-last">
				<label for="stelo-card-cvc"><?php _e( 'CVC', 'woocommerce-stelo' ); ?> <span class="required">*</span></label>
				<input id="stelo-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="tel" autocomplete="off" placeholder="<?php _e( 'CVC', 'woocommerce-stelo' ); ?>" style="font-size: 1.5em; padding: 8px;" />
			</p>
			<div class="clear"></div>
			<p id="stelo-card-installments-field" class="form-row form-row-wide">
				<label for="stelo-card-installments"><?php _e( 'Installments', 'woocommerce-stelo' ); ?> <span class="required">*</span></label>
				<select id="stelo-card-installments" name="stelo_card_installments" style="font-size: 1.5em; padding: 4px; width: 100%;">
					<?php for($i = 1; $i <= 12; $i++): ?>
					<option value="<?php echo $i; ?>"><?php echo $i == 1 ? __( 'Pay in full', 'woocommerce-stelo' ) : sprintf( __( 'Pay in %sx payments', 'woocommerce-stelo' ), $i ); ?></option>
					<?php endfor; ?>
				</select>
			</p>
			<p id="stelo-card-holder-cpf-field" class="form-row form-row-wide">
				<label for="stelo-card-holder-cpf"><?php _e( 'Owners CPF', 'woocommerce-stelo' ); ?> <span class="required">*</span></label>
				<input id="stelo-card-holder-cpf" name="stelo_card_holder_cpf" class="input-text wecfb-cpf-field" type="tel" autocomplete="off" style="font-size: 1.5em; padding: 8px;" />
			</p>
			<div class="clear"></div>
			<p id="stelo-card-holder-birth-date-field" class="form-row form-row-wide">
				<label for="stelo-card-holder-birth-date"><?php _e( 'Date of birth', 'woocommerce-stelo' ); ?> <span class="required">*</span></label>
				<input id="stelo-card-holder-birth-date" name="stelo_card_holder_birth_date" class="input-text" type="tel" autocomplete="off" placeholder="<?php _e( 'DD / MM / YYYY', 'woocommerce-stelo' ); ?>" style="font-size: 1.5em; padding: 8px;" />
			</p>
			<div class="clear"></div>
            <p class="form-row form-row-wide">
                <img src="<?php echo $credit_card_brand; ?>" class="brand-img" title="Conte com a confiança e segurança da Stelo">
            </p>
            <div class="clear"></div>
        </div>
	<?php endif; ?>

	<?php if ( 'bankslip' == $method ) : ?>
		<div id="stelo-banking-ticket-form" class="stelo-method-form">
			<p id="stelo-card-holder-birth-date-field" class="form-row form-row-wide">
				<label for="stelo-bankslip-birth-date"><?php _e( 'Date of birth', 'woocommerce-stelo' ); ?> <span class="required">*</span></label>
				<input id="stelo-bankslip-birth-date" name="stelo_bankslip_birth_date" class="input-text" type="tel" autocomplete="off" placeholder="<?php _e( 'DD / MM / YYYY', 'woocommerce-stelo' ); ?>" style="font-size: 1.5em; padding: 8px;" />
			</p>
			<div class="clear"></div>
			<p>
				<i id="stelo-icon-ticket"></i>
				<?php _e( 'The order will be processed after we receive a confirmation from the bank.', 'woocommerce-stelo' ); ?>
			</p>
			<div class="clear"></div>
            <p class="form-row form-row-wide">
                <img src="<?php echo $boleto_brand; ?>" class="brand-img" title="<?php _e( 'Count with the trust and credibility from Stelo', 'woocommerce-stelo' ); ?>">
            </p>
            <div class="clear"></div>
		</div>
	<?php endif; ?>
	<?php endforeach; ?>
</fieldset>