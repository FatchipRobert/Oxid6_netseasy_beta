[{if $sPaymentID == "nets_easy"}]

	<style>
		.nets { padding: 15px 10px; background: #f7f7f7; border: 1px solid #d8d8d8; border-radius: 5px; }
		.icon_placeholder { float:right; margin-top: -5px; max-height: 32px; max-width: 225px; }
		.icon_placeholder object { max-height: 32px; max-width: 225px; }
		.icon_placeholder object #nets-iconbar { height: 32px; }
		.nets dd { margin-bottom: 0rem; margin-left: 30px; }
		.nets dl { margin-top: 0; margin-bottom: 0; }
		input[value="nets_easy"] { display: none; } 
		input[value="nets_easy"] + label { margin-bottom: 0; font-weight: 400; font-size: 14px; cursor:pointer; } 
		input[value="nets_easy"] + label span { display: inline-block; width: 18px; height: 18px; margin: -2px 10px 0 0; vertical-align: middle; cursor: pointer; -moz-border-radius: 50%; border-radius: 50%; border: 3px solid #d8d8d8; } 
		input[value="nets_easy"] + label span { background-color: #fff; } 
		input[value="nets_easy"] + label b { padding-right: 10px; } 
		input[value="nets_easy"]:checked + label { color: #333; font-weight: 400; } 
		input[value="nets_easy"]:checked + label span { background-color: #00bef0; border: 2px solid #ffffff; box-shadow: 2px 2px 2px rgba(0,0,0,.1); } 
		input[value="nets_easy"] + label span, 
		input[value="nets_easy"]:checked + label span { -webkit-transition: background-color 0.24s linear; -o-transition: background-color 0.24s linear; -moz-transition: background-color 0.24s linear; transition: background-color 0.24s linear; }

		@media only screen and (max-width: 600px) {
			.icon_placeholder {
				float: unset;
				margin-top: 10px;
				max-height: 32px;
				max-width: unset;
				width: 100%;
				display: block;
				text-align: center;
			}
		}
	</style>

	<div class="well well-sm nets">
		<dl>
			<dt>
				<input id="payment_nets_easy" type="radio" name="paymentid" value="nets_easy" checked="">
				<label for="payment_nets_easy"> 
					<span></span> 
					<b>[{ $paymentmethod->oxpayments__oxdesc->value}]</b> 
					[{if $paymentmethod->oxpayments__oxlongdesc->value}]
						[{ $paymentmethod->oxpayments__oxlongdesc->value}]
					[{/if}]
				</label>
				<span class="icon_placeholder">
					<object type="image/svg+xml" data="[{ $oView->getPaymentUrlConfig() }]"/></object>
				</span>
			</dt>
		</dl>
	</div>
[{else}]
	[{$smarty.block.parent}]
[{/if}]
