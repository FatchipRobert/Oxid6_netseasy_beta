[{$smarty.block.parent}]

<!-- Reporting Api -->
[{assign var="moduleData" value=$oView->netsGetModuleInfo()}]
[{if $moduleData}]
    [{if $moduleData->status=="00" OR $moduleData->status=="11"}]
        <div class="modal fade" id="netseasy-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <!--<div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
                        <h4 class="modal-title">
                            [{if $moduleData->status=="00"}]
                                <strong>Update Notification</strong>
                            [{/if}]
                            [{if $moduleData->status=="11"}]
                                <strong>Success Notification</strong>
                            [{/if}]
                        </h4>
                    </div>-->
                    <div class="modal-body">
                        [{if $moduleData->status=="00"}]
                            <h4 class="modal-title"><span style="color:red">Note: </span>[{$moduleData->data->notification_message}]</h4>
                            <div class="form-group-lg" style="font-size: small;">
                                <label class="form-control-label">Latest Plugin Version : </label>  [{$moduleData->data->plugin_version}] version </br>
                                <label class="form-control-label">Shop Version Compatible : </label>[{$moduleData->data->shop_version}] </br>

                                [{if $moduleData->data->repo_links}]
                                    <label class="form-control-label">Github Link : </label> <a href="[{$moduleData->data->repo_links}]" target="_blank">Click here</a> </br>
                                [{/if}]

                                [{if $moduleData->data->tech_site_links}]
                                    <label class="form-control-label">TechSite Link : </label> <a href="[{$moduleData->data->tech_site_links}]" target="_blank">Click here</a>
                                [{/if}]

                                [{if $moduleData->data->marketplace_links}]
                                    <label class="form-control-label">MarketPlace Link : </label> <a href="[{$moduleData->data->marketplace_links}]" target="_blank">Click here</a>
                                [{/if}]
                            </div>
                        [{/if}]

                        [{if $moduleData->status=="11"}]
                            <h4 class="modal-title"><span style="color:green">Note: </span> [{$moduleData->data->notification_message}]</h4>
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
