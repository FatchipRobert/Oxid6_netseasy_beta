[{$smarty.block.parent}]

<!-- Reporting Api -->
[{if $oViewConf->showPopup()}]
[{assign var="customData" value=$oViewConf->showPopup()}]

    [{if $customData->status=="00" OR $customData->status=="11"}]
        <div class="modal fade" id="netseasy-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <!--<div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
                        <h4 class="modal-title">
                            [{if $customData->status=="00"}]
                            <strong>Update Notification</strong>
                            [{/if}]
                            [{if $customData->status=="11"}]
                            <strong>Success Notification</strong>
                            [{/if}]
                            </h4>
                        </div>-->
                        <div class="modal-body">
                            [{if $customData->status=="00"}]
                            <h4 class="modal-title"><span style="color:red">Note: </span>[{$customData->data->notification_message}]</h4>
                                <div class="form-group-lg" style="font-size: small;">
                                    <label class="form-control-label">Latest Plugin Version : </label>  [{$customData->data->plugin_version}] version </br>
                                    <label class="form-control-label">Shop Version Compatible : </label>[{$customData->data->shop_version}] </br>

                                    [{if $customData->data->repo_links}]
                                    <label class="form-control-label">Github Link : </label> <a href="[{$customData->data->repo_links}]" target="_blank">Click here</a> </br>
                                    [{/if}]

                                    [{if $customData->data->tech_site_links}]
                                    <label class="form-control-label">TechSite Link : </label> <a href="[{$customData->data->tech_site_links}]" target="_blank">Click here</a>
                                    [{/if}]

                                    [{if $customData->data->marketplace_links}]
                                    <label class="form-control-label">MarketPlace Link : </label> <a href="[{$customData->data->marketplace_links}]" target="_blank">Click here</a>
                                    [{/if}]
                                </div>
                            [{/if}]

                            [{if $customData->status=="11"}]
                                <h4 class="modal-title"><span style="color:green">Note: </span> [{$customData->data->notification_message}]</h4>
                            [{/if}]
                        </div>

                        <!--<div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-dismiss="modal">Ok</button>
                        </div>-->

                    </div><!-- /.modal-content -->
                </div><!-- /.modal-dialog -->
            </div><!-- /.modal -->
            <div class="modal-overlay"></div>
        [{/if}]

        [{capture assign=pageScript}]

            $('#agpopup-modal').modal('show');

        [{/capture}]
        [{oxscript add=$pageScript}]

[{/if}]