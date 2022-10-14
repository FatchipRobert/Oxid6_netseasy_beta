$(function() {
	if($('#dibs-complete-checkout').length !== 0) {

		$('.alert.alert-info:first').after('<div class="row"><div class="col-md-12 ttop"></div><div class="col-md-6 tbleft"></div><div class="col-md-6 tbright"></div><div class="col-md-12 btop"></div><div class="col-md-12 bbot"></div></div>');

		$('#orderAgbTop').hide();
		$('#orderConfirmAgbBottom').hide();

		$('#orderAddress').appendTo('.ttop');
		$('#orderShipping').appendTo('.tbleft').removeClass('col-12 col-md-6');
		$('#orderPayment').appendTo('.tbright').removeClass('col-12 col-md-6');
		$('#orderEditCart').appendTo('.btop');
		$('.lineBox').addClass('row');
		$('#basketcontents_list').addClass('col-md-8');
		$('#basketSummary').removeClass('col-12 col-md-6 summary offset-md-6 orderSummary').addClass('summary orderSummary col-md-4').insertAfter('#basketcontents_list');
		$('#dibs-block').appendTo('.bbot');
	}
});
