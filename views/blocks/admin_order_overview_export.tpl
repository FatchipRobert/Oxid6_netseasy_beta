[{ if $edit }]
	[{ assign var="status" value=$oView->isEasy($oxid) }]
	[{ assign var="deBug" value=$oView->debugMode() }]
	[{ if $status.paymentErr }] 
		<div class="nets-container">
			<link rel="stylesheet" href="[{$oViewConf->getModuleUrl('esnetseasy', 'out/admin/src/css/order.css')}]" type="text/css"/>
			<div class="nets-logo"> [{ assign var="langText" value=$status.langStatus }] 
				<div class="status">
					[{ oxmultilang ident="SHOP_MODULE_nets_status" }] : <span class="fail">[{ oxmultilang ident="SHOP_MODULE_nets_paystatus_failed" }]</span>
				</div>
				<img src="[{ $oViewConf->getModuleUrl('esnetseasy') }]/out/src/img/nets_easy.png">
			</div>
			<div class="nets-header"><b>[{ oxmultilang ident="SHOP_MODULE_nets_payment_failed_msg" }]</b></div>
		</div>
	[{/if}]

	[{ if $status.payStatus }] 
		[{ $smarty.block.parent }]

		<link rel="stylesheet" href="[{$oViewConf->getModuleUrl('esnetseasy', 'out/admin/src/css/order.css')}]" type="text/css"/>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
		<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>

		<div class="nets-container">
			[{ $oViewConf->getHiddenSid() }]
			<div class="nets-logo"> [{ assign var="langText" value=$status.langStatus }] 
				<div class="status">
					[{ oxmultilang ident="SHOP_MODULE_nets_status" }] : <span>[{ oxmultilang ident="SHOP_MODULE_nets_paystatus_$langText" }]</span>
				</div>
				<img src="[{ $oViewConf->getModuleUrl('esnetseasy') }]/out/src/img/nets_easy.png">
			</div>
			<div class="nets-header"><b>Payment ID :</b> [{ $oView->getPaymentId($oxid) }]</div>
			<div class="nets-block">
				[{ assign var='responseItems' value=$oView->checkPartialItems($oxid) }]

				[{ if $responseItems.chargedItemsOnly }] 
					<table class="item-listing charged" cellspacing="0" cellpadding="0" border="0" width="100%">		
						[{ foreach key=ar item=item from=$responseItems.chargedItemsOnly }]
							<tr class="listing">
								<input type="hidden" class="quant" value="[{ $item.quantity }]"/>
								<input type="hidden" class="price" value="[{ $item.price }]"/>
								<input type="hidden" class="currency" value="[{ $edit->oxorder__oxcurrency->value }]"/>
								<td>[{ oxmultilang ident="SHOP_MODULE_nets_paystatus_charged" }] : </td>
								<td class="listing qty">[{ $item.quantity }] * </td>
								<td class="listing">[{ $ar }]</td>
								<td class="listing">[{ $item.name }]</td>
								<td class="listing result right">[{ $item.price }] [{ $edit->oxorder__oxcurrency->value }]</td>
							</tr>
						[{/foreach}]
					</table>
				[{/if}]

				<table cellspacing="0" cellpadding="0" border="0" width="100%">
					[{ foreach key=nm item=listitem from=$responseItems.reservedItems }]
						<tr class="lining" key="[{ $listitem.reference }]">
							<td class="listing" width="110px">
								<div class="qty-box charge">
									<div class="quantity">
										<input type="hidden" class="quant" value="[{ $listitem.quantity }]"/>
										<input type="hidden" class="reference" value="[{ $listitem.reference|replace:' ':'_' }]"/>
										<input type="hidden" class="price" value="[{ $listitem.price }]"/>
										<input type="hidden" class="currency" value="[{ $edit->oxorder__oxcurrency->value }]"/>
										<input type="button" value="-" class="minus"/>
										<input 
										  type="text" 
										  class="single qty value" 
										  name="single" 
										  value="[{ $listitem.quantity }]" 
										  step="1" 
										  min="1" 
										  max="[{ $listitem.quantity }]"
										/>
										<input type="button" value="+" class="plus"/>
									</div>
								</div>
							</td>
							<td class="listing">[{ $listitem.reference }]</td> 
							<td class="listing">[{ $listitem.name }]</td>
							<td class="listing right pr">
								<span id="price_[{ $listitem.reference|replace:' ':'_' }]" class="priceblk">
									[{ $listitem.price }] [{ $edit->oxorder__oxcurrency->value }]
								</span>
							</td>
							<td class="listing" width="40px">
								<form 
								  name="partialCharge" 
								  method="post" 
								  action="[{ $oViewConf->getSelfLink() }]cl=nets_order_overview&fnc=getOrderCharged"
								>
									<input type="hidden" name="oxorderid" value="[{ $oxid }]"/>
									<input type="hidden" name="reference" value="[{ $listitem.reference }]"/> 
									<button 
									  type="submit" 
									  id="item_[{ $listitem.reference|replace:' ':'_' }]" 
									  class="nets-btn capture" 
									  name="charge" value="[{ $listitem.quantity }]"
									/>
										<img src="[{ $oViewConf->getModuleUrl('esnetseasy', 'out/admin/src/img/charge.png') }]" alt=""/>
									</button>
								</form>
							</td> 
						</tr>
					[{/foreach}]
				</table>

				<table cellspacing="0" cellpadding="0" border="0" width="100%">
					[{ foreach key=key item=prodval from=$responseItems.chargedItems }]
						<tr class="lining" key="[{ $key }]">
							<td class="listing" width="110px">
								<div class="qty-box refund">
									<div class="quantity">
										<input type="hidden" class="quant" value="[{ $prodval.quantity }]"/>
										<input type="hidden" class="reference" value="[{ $key|replace:' ':'_' }]"/>
										<input type="hidden" class="price" value="[{ $prodval.price }]"/>
										<input type="hidden" class="currency" value="[{ $edit->oxorder__oxcurrency->value }]"/>
										<input type="button" value="-" class="minus"/>
										<input 
										  type="text" 
										  class="single qty value" 
										  name="single" 
										  value="[{ $prodval.quantity }]" 
										  step="1" 
										  min="1" 
										  max="[{ $prodval.quantity }]"
										/>
										<input type="button" value="+" class="plus"/>
									</div>
								</div>
							</td>
							<td class="listing"> [{ $key }]</td>
							<td class="listing"> [{ $prodval.name }]</td>
							<td class="listing right pr">
								<span id="price_[{ $key|replace:' ':'_' }]" class="priceblk">
									[{ $prodval.price }] [{ $edit->oxorder__oxcurrency->value }]
								</span>
							</td>
							<td class="listing" width="40px">
								<form 
								  name="partialRefund" 
								  method="post" 
								  action="[{$oViewConf->getSelfLink()}]cl=nets_order_overview&fnc=getOrderRefund"
								>
									<input type="hidden" name="oxorderid" value="[{$oxid}]"/>
									<input type="hidden" name="reference" value="[{$key}]"/> 
									<button 
									  type="submit" 
									  id="item_[{ $key|replace:' ':'_'}]" 
									  class="nets-btn refund" 
									  name="refund" 
									  value="[{$prodval.quantity}]"
									/>
										<img src="[{$oViewConf->getModuleUrl('esnetseasy', 'out/admin/src/img/refund.png')}]" alt="refund"/>
									</button>
								</form>
							</td>
						</tr>
					[{/foreach}]
				</table>

				[{ if $responseItems.refundedItems }] 
					<table class="item-listing refunded" cellspacing="0" cellpadding="0" border="0" width="100%">		
						[{ foreach key=ar item=item from=$responseItems.refundedItems }]
							<tr class="listing">
								<input type="hidden" class="quant" value="[{ $item.quantity }]"/>
								<input type="hidden" class="price" value="[{ $item.price }]"/>
								<input type="hidden" class="currency" value="[{ $edit->oxorder__oxcurrency->value }]"/>
								<td>[{ oxmultilang ident="SHOP_MODULE_nets_paystatus_refunded" }] : </td>
								<td class="listing">[{ $item.quantity }]</td>
								<td class="listing">[{ $ar }]</td>
								<td class="listing">[{ $item.name }]</td>
								<td class="listing result right">[{ $item.price }] [{ $edit->oxorder__oxcurrency->value }]</td>
							</tr>
						[{/foreach}]
					</table>
				[{/if}]
			</div>

			[{ if $edit && $status.payStatus == "Reserved" }]
				<div class="nets-body">
					<form name="cancelorder" id="cancelorder" action="[{ $oViewConf->getSelfLink() }]cl=nets_order_overview&fnc=getOrderCancel" method="post">
						<input type="hidden" class="edittext" name="oxorderid" value="[{ $oxid }]"/>
						<input type="hidden" class="edittext" name="orderno" value="[{ $edit->oxorder__oxordernr->value }]"/>
						<input type="submit" class="nets-btn cancel" name="cancel" value="[{ oxmultilang ident="SHOP_MODULE_nets_cancel" }]"/>
					</form>
					<form name="captureorder" id="captureorder" action="[{ $oViewConf->getSelfLink() }]cl=nets_order_overview&fnc=getOrderCharged" method="post">
						<input type="hidden" class="edittext" name="oxorderid" value="[{ $oxid }]"/>
						<input type="hidden" class="edittext" name="orderno" value="[{ $edit->oxorder__oxordernr->value }]"/>
						<input type="submit" id="captureBtn" class="nets-btn capture" name="save" value="[{ oxmultilang ident="SHOP_MODULE_nets_chargeall" }]"/>
					</form>
				</div>
			[{ elseif $edit && $status.payStatus == "Charged" && $responseItems.chargedItems|@count gt 1 }]
				<div class="nets-body">
					<form name="refundorder" id="refundorder" action="[{ $oViewConf->getSelfLink() }]cl=nets_order_overview&fnc=getOrderRefund" method="post">
						<input type="hidden" class="edittext" name="oxorderid" value="[{ $oxid }]" />
						<input type="hidden" class="edittext" name="orderno" value="[{ $edit->oxorder__oxordernr->value }]"/>
						<input type="submit" class="nets-btn refund" name="refund" value="[{ oxmultilang ident="SHOP_MODULE_nets_refundall" }]"/>
					</form>
				</div>
			[{ elseif $edit && $status.payStatus == "Refunded" }]
				<div class="nets-body">
					<div class="nets-status">[{ oxmultilang ident="SHOP_MODULE_nets_refund_msg" }]</div>
				</div>
			[{ elseif $edit && $status.payStatus == "Refund Pending" }]
				<div class="nets-body">
					<div class="nets-status">[{ oxmultilang ident="SHOP_MODULE_nets_refund_pending" }]</div>
				</div>
			[{ elseif $edit && $status.payStatus == "Cancelled" }]
				<div class="nets-body">
					<div class="nets-status">[{ oxmultilang ident="SHOP_MODULE_nets_cancel_msg" }]</div>
				</div>
			 [{ elseif $edit && $status.payStatus == "Failed" }]
				<div class="nets-body">
					<div class="nets-status">[{ oxmultilang ident="SHOP_MODULE_nets_failed_msg" }]</div>
				</div>
			[{/if}]
		</div>

		[{ if $deBug }]
			<textarea>[{ $oView->getResponse($oxid) }]</textarea>
		[{/if}]

		<script src="[{ $oViewConf->getModuleUrl('esnetseasy', 'out/admin/src/js/order.js') }]"></script>
	[{/if}]
[{else}]
	[{$smarty.block.parent}] 
[{/if}]
