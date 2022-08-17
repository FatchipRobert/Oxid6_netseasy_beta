[{assign var="payment" value=$oView->getPayment()}]

<!-- [{ $payment|@debug_print_var}] -->
[{if $oView->isEmbedded() && $payment->oxpayments__oxid->value == 'nets_easy'}]

	[{foreach from = $oxcmp_lang item = _language}]
	[{if $_language->selected == 1}][{if $_language->abbr=='en'}][{assign var="lang" value="en-GB"}][{/if}][{/if}]
	[{if $_language->selected == 1}][{if $_language->abbr=='de'}][{assign var="lang" value="de-DE"}][{/if}][{/if}]
	[{if $_language->selected == 1}][{if $_language->abbr=='dk'}][{assign var="lang" value="da-DK"}][{/if}][{/if}]
	[{if $_language->selected == 1}][{if $_language->abbr=='se'}][{assign var="lang" value="sv-SE"}][{/if}][{/if}]
	[{if $_language->selected == 1}][{if $_language->abbr=='no'}][{assign var="lang" value="nb-NO"}][{/if}][{/if}]
	[{if $_language->selected == 1}][{if $_language->abbr=='fi'}][{assign var="lang" value="fi-FI"}][{/if}][{/if}]
	[{if $_language->selected == 1}][{if $_language->abbr=='pl'}][{assign var="lang" value="pl-PL"}][{/if}][{/if}]
	[{if $_language->selected == 1}][{if $_language->abbr=='nl'}][{assign var="lang" value="nl-NL"}][{/if}][{/if}]
	[{if $_language->selected == 1}][{if $_language->abbr=='fr'}][{assign var="lang" value="fr-FR"}][{/if}][{/if}]
	[{if $_language->selected == 1}][{if $_language->abbr=='es'}][{assign var="lang" value="es-ES"}][{/if}][{/if}]
	[{/foreach}]

        [{oxscript include="js/libs/jquery.min.js" priority=1}]
        [{oxscript include="js/libs/jquery-ui.min.js" priority=1}]

	[{$smarty.block.parent}]
	[{assign var="checkoutKey" value=$oView->getCheckoutKey()}]
	[{assign var="paymentId" value=$oView->getPaymentApiResponse()}]
	
	[{oxstyle include=$oViewConf->getModuleUrl("esnetseasy", "out/src/css/embedded.css")}]
	
	<div id="dibs-block" class="agb card">
		<div class="card-header">
			<h3 class="card-title">Nets Easy 
				<?php /* [{$checkoutKey}] [{$paymentId}]*/ ?>
			</h3>
		</div>
		<div class="card-body">
			<div id="dibs-complete-checkout"></div>
		</div>
	</div>

	[{assign var="checkoutJs" value=$oView->getCheckoutJs() }]
	<script type="text/javascript" src="[{ $checkoutJs }]"></script>
	<script type="text/javascript">
		var checkoutOptions = {
			checkoutKey: "[{$checkoutKey}]", // checkout-key
			paymentId : "[{$paymentId}]",
			containerId : "dibs-complete-checkout",
			language: "[{$lang}]"
		};
                console.log(checkoutOptions);
		var checkout = new Dibs.Checkout(checkoutOptions);
		checkout.on('payment-completed', function(response) {                         
			$("#orderConfirmAgbBottom").submit();
		});
	</script>
	[{ oxscript include=$oView->getLayout() }]
[{else}]
	[{$smarty.block.parent}]
[{/if}]
