[{if $order->netsIsNetsPaymentUsed()}]
	<div>
		<b>Nets Payment ID</b> - [{ $oView->netsGetTransactionId() }]
	</div>
	<br>
[{/if}]
[{$smarty.block.parent}]

