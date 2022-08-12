[{if $order->oxorder__oxpaymenttype->value === 'nets_easy'}]
	<div>
		<b>Nets Payment ID</b> - [{ $oView->getPaymentId() }] 
	</div>
	<br>
[{/if}]
[{$smarty.block.parent}]

