$(function() {
	if($('#dibs-complete-checkout').length !== 0) {

		$('.alert.alert-info:first').after('<div class="row"><div class="col-md-6 ttmain"></div><div class="col-md-6"><div class="row"><div class="col-md-12 sstop"></div><div class="col-md-12 ssbtm"></div></div></div><div class="col-md-12 bbot"></div></div>');
				
		$('#dibs-block').appendTo('.ttmain');
		$('#dibs-block').css({ 'height' : 'calc(100% - 30px)' });

		$('.sstop').css({ 'padding' : '0' });
		$('#orderPayment').appendTo('.sstop').removeClass('col-md-6').addClass('col-md-12');
		$('#orderShipping').appendTo('.sstop').removeClass('col-md-6').addClass('col-md-12');

		$('#orderAddress').appendTo('.ssbtm');
		$('#orderAddress').find('div').removeClass('col-md-6').addClass('col-md-12');
		$('#orderAddress .card, #orderAddress .card-header, #orderAddress .card-body').removeClass('col-md-12');
		
		$('#orderAgbTop').hide();
		$('#orderConfirmAgbBottom').hide();
		$('#orderEditCart').appendTo('.bbot');

		$('.lineBox').addClass('row');
		$('#basketcontents_list').addClass('col-md-8');
		$('#basketSummary').removeClass('col-12 col-md-6 summary offset-md-6 orderSummary').addClass('summary orderSummary col-md-4').insertAfter('#basketcontents_list');
	}
});
