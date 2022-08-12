[{assign var="errorMsg" value=$oView->getErrorMsg()}]
[{ if $errorMsg }]
	<p class="alert alert-danger">[{$errorMsg}]</p>
[{ else }]
	[{$smarty.block.parent}]
[{ /if }]