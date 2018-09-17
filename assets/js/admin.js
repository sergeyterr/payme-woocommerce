jQuery(document).ready(function(e) {
    jQuery('#woocommerce_payme_general,#woocommerce_payme_payment,#woocommerce_payme_installments').parent().parent().parent().css('border-bottom','1px solid');
	
	jQuery('#woocommerce_payme_general').parent().parent().parent().parent().find('select').css('height','35px');
	
	 jQuery('#woocommerce_payme_formstyle,#woocommerce_payme_labelstyle,#woocommerce_payme_inputstyle').parent().parent().parent().hide();
	 
	 jQuery('#woocommerce_payme_fullscreen').change(function(){
	 	if (jQuery(this).val() == 'custom') {
			jQuery('#customform-container').show();
		} else {
			jQuery('#customform-container').hide();
		}
	 });
	
	function setInstallmentType() 
	{
		if (jQuery('#woocommerce_payme_installments_setting').val() == 1) {
			jQuery('#woocommerce_payme_installments_preset').parent().parent().parent().show();
			jQuery('#tableinst').parent().parent().parent().hide();
		} else {
			jQuery('#woocommerce_payme_installments_preset').parent().parent().parent().hide();
			jQuery('#tableinst').parent().parent().parent().show();
		}
	}
	
	jQuery("#woocommerce_payme_installments_setting").change(function() {
		setInstallmentType();
	});

	setInstallmentType();
});