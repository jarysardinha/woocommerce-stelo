jQuery(function($) {
    var stelo = {
        init: function() {
            this.initMasks();

            if (stelo_params.methodType == 'transparent') {
                this.initForm();
                this.initCardToken();
            }

            if (stelo_params.methodType == 'wallet') {
                this.initLightbox();
            }
        },
        initLightbox: function() {
            $('body').on('click', '#submit-payment', function(e) {
                e.preventDefault();

                $.fancybox({
                    href: stelo_params.payment_url,
                    type : 'iframe',
                    padding : 5
                });
            });
        },
        initForm: function() {
            $('body').on('click', '#stelo-payment-methods input', function(e) {
                if ($(this).val() == 'credit-card') {
                    $('#stelo-credit-card-form').show();
                    $('#stelo-banking-ticket-form').hide();

                    $(this).parent().parent().next().removeClass('active');
                }

                if ($(this).val() == 'bankslip') {
                    $('#stelo-credit-card-form').hide();
                    $('#stelo-banking-ticket-form').show();

                    $(this).parent().parent().prev().removeClass('active');
                }

                $(this).parent().parent().addClass('active');
            });

            setTimeout(function() {
                $('#stelo-payment-methods input:first').trigger('click');
            }, 2000);

            $('body').append(stelo_params.transfer_block);
        },
        initMasks: function() {
            if (stelo_params.methodType == 'transparent') {
                var SPMaskBehavior = function (val) {
                        return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
                    },
                    spOptions = {
                        onKeyPress: function(val, e, field, options) {
                            field.mask(SPMaskBehavior.apply({}, arguments), options);
                        }
                    };

                $('#stelo-card-holder-cpf').mask('000.000.000-00', {reverse: true});
                $('#stelo-card-holder-birth-date').mask('00/00/0000', {reverse: true});
                $('#stelo-bankslip-birth-date').mask('00/00/0000', {reverse: true});
            }

            if (stelo_params.methodType == 'wallet') {
                $('#stelo-wallet-birth-date').mask('00/00/0000', {reverse: true});
            }
        },
        initCardToken: function() {
            $('body').on('change', '#stelo-card-holder-name, #stelo-card-number, #stelo-card-expiry, #stelo-card-cvc', function() {
                if ($('#stelo-card-holder-name').val().length != 0 && $('#stelo-card-number').val().length != 0 && $('#stelo-card-expiry').val().length != 0 && $('#stelo-card-cvc').val().length != 0) {
                    var expiryDate = $('#stelo-card-expiry').val().split(' / ');

                    $.registerCard({
                        url: stelo_params.token_url,
                        data: {
                            'number': $('#stelo-card-number').val().replace(/[^0-9]/g, ''),
                            'embossing': $('#stelo-card-holder-name').val(),
                            'expiryDate': expiryDate[0] + '/' + expiryDate[1].substr(2, 2),
                            'cvv': $('#stelo-card-cvc').val(),
                        },
                        id: stelo_params.client_id,
                        callback: function(response) {
                            $('#stelo-card-token').val(response.token);
                        },
                        fnError: function(response) {
                            error = JSON.parse(response.responseText);

                            alert(stelo_params.registerCardError[error.errorCode]);
                        }

                    });
                }
            });
        }
    }

    stelo.init();
});