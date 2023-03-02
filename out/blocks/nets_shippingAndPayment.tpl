[{assign var="payment" value=$oView->getPayment()}]
[{if $oView->netsIsEmbedded() && $payment->netsIsNetsPaymentUsed()}]
	[{oxscript include="js/libs/jquery.min.js" priority=1}]
	[{oxscript include="js/libs/jquery-ui.min.js" priority=1}]

	[{$smarty.block.parent}]

	[{assign var="checkoutKey" value=$oView->netsGetCheckoutKey()}]
	[{assign var="paymentId" value=$oView->netsGetPaymentId()}]
	
	[{oxstyle include=$oViewConf->getModuleUrl("esnetseasy", "out/src/css/embedded.css")}]
	
	<div id="dibs-block" class="agb card">
		<div class="card-header">
			<h3 class="card-title">Nets Easy</h3>
		</div>
		<div class="card-body">
			<div id="dibs-complete-checkout"></div>
		</div>
	</div>

	<script type="text/javascript" src="[{ $oView->netsGetCheckoutJs() }]"></script>
	<script type="text/javascript">
		var checkoutOptions = {
			checkoutKey: "[{$checkoutKey}]", // checkout-key
			paymentId : "[{$paymentId}]",
			containerId : "dibs-complete-checkout",
			language: "[{$oView->netsGetLocaleCode()}]"
		};

		var checkout = new Dibs.Checkout(checkoutOptions);
		checkout.on('payment-completed', function(response) {                         
			$("#orderConfirmAgbBottom").submit();
		});
	</script>
	[{ oxscript include=$oView->netsGetLayoutJs() }]
[{else}]
	[{$smarty.block.parent}]
[{/if}]
