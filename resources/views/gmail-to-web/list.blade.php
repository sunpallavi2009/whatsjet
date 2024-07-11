@php
/**
* Component : Contact
* Controller : ContactController
* File : contact.list.blade.php
* ----------------------------------------------------------------------------- */
@endphp
@extends('layouts.app', ['title' => __tr('Gmail To Web')])
@section('content')
@include('users.partials.header', [
'title' => __tr('Gmail To Web'),
'class' => 'col-lg-7'
])
<div class="container-fluid mt-lg--6">
    <div class="row">
        <!-- button -->
        <div class="col-xl-12 mb-3">
            <div class="float-right">
                <a class="lw-btn btn btn-primary" href="{{ route('vendor.gmailtoweb.gmails.credentials') }}">{{
                    __tr('Fetch Gmail') }}</a>
            </div>
        </div>


        <!-- Details Contact Modal -->
        <x-lw.modal id="lwDetailsGmailToWeb" :header="__tr('Gmail To Web Details')">
            <!--  Details Contact Form -->
            <!-- Details body -->
            <div id="lwDetailsGmailToWebBody" class="lw-form-modal-body"></div>
            <script type="text/template" id="lwDetailsGmailToWebBody-template">
                <!-- form fields -->
                <div>
                    <label class="small">{{ __tr('From') }}:</label>
                    <div class="lw-details-item" style="float:right;">
                        <%- __tData.from_email %>
                    </div>
                </div>

                <div>
                    <label class="small">{{ __tr('To') }}:</label>
                    <div class="lw-details-item" style="float:right;">
                        <%- __tData.to_email %>
                    </div>
                </div>

                <div>
                    <label class="small">{{ __tr('Date') }}:</label>
                    <div class="lw-details-item" style="float:right;">
                        <%- __tData.received_at %>
                    </div>
                </div>

                <fieldset>
                    <legend>{{ __tr('Subject') }}</legend>
                        <span class="badge badge-light">
                            <%- __tData.subject %>
                        </span>
                </fieldset>
                <fieldset>
                    <legend>{{ __tr('Other Information') }}</legend>
                   
                    <div class="mb-2">
                        <label class="small"></label>
                        <div class="lw-details-item">
                            <%- __tData.body %>
                        </div>
                    </div>
                   
                </fieldset>
            </script>
            <!--/  Details Contact Form -->
        </x-lw.modal>
        <!--/ Edit Contact Modal -->

        <!--/ button -->
        <div class="col-xl-12" x-cloak x-data="{isSelectedAll:false,selectedGmailToWeb: [],selectedGroupsForSelectedGmailToWeb:[],
            toggle(id) {
                if (this.selectedGmailToWeb.includes(id)) {
                    const index = this.selectedGmailToWeb.indexOf(id);
                    this.selectedGmailToWeb.splice(index, 1);
                    this.isSelectedAll = false;
                } else {
                    this.selectedGmailToWeb.push(id);
                    if($('.dataTables_wrapper table>tbody input[type=checkbox].lw-checkboxes').length == this.selectedGmailToWeb.length) {
                        this.isSelectedAll = true;
                    }
                };
            },toggleAll() {
                if(!this.isSelectedAll) {
                    $('.dataTables_wrapper table>tbody input[type=checkbox].lw-checkboxes').not(':checked').trigger('click');
                    this.isSelectedAll = true;
                } else {
                    $('.dataTables_wrapper table>tbody input[type=checkbox].lw-checkboxes:checked').trigger('click');
                    this.isSelectedAll = false;
                }
            },deleteSelectedGmailToWeb() {
                var that = this;
                showConfirmation('{{ __tr('Are you sure you want to delete all selected Gmail To Web?') }}', function() {
                    __DataRequest.post('{{ route('vendor.gmailtoweb.selected.delete') }}', {
                        'selected_gmailtoweb' : that.selectedGmailToWeb
                    });
                }, {
                    confirmButtonText: '{{ __tr('Yes') }}',
                    cancelButtonText: '{{ __tr('No') }}',
                    type: 'error'
                });
            }, assignGroupsToSelectedGmailToWeb(){
                var that = this;
                __DataRequest.post('{{ route('vendor.contacts.selected.write.assign_groups') }}', {
                    'selected_contacts' : that.selectedGmailToWeb,
                    'selected_groups' : that.selectedGroupsForSelectedGmailToWeb
                });
                $('#lwAssignGroups').modal('hide');
                $('.dataTables_wrapper table>tbody input[type=checkbox].lw-checkboxes:checked').trigger('click');
                this.isSelectedAll = false;
            }}" x-init="$('#lwGmailToWebList').on( 'draw.dt', function () {
                $('.dataTables_wrapper table>tbody input[type=checkbox].lw-checkboxes:checked').trigger('click');
                isSelectedAll = false;
            } );">
            <button x-show="!isSelectedAll" class="btn btn-dark btn-sm my-2" @click="toggleAll">{{ __tr('Select All')
                }}</button>
            <button x-show="isSelectedAll" class="btn btn-dark btn-sm my-2" @click="toggleAll">{{ __tr('Unselect All')
                }}</button>
            <div class="btn-group">
                <button :class="!selectedGmailToWeb.length ? 'disabled' : ''"
                    class="btn btn-danger mt-1 btn-sm dropdown-toggle" type="button" data-toggle="dropdown"
                    aria-expanded="false">
                    {{ __tr('Bulk Actions') }}
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" @click.prevent="deleteSelectedGmailToWeb" href="#">{{ __tr('Delete Selected
                        Contacts') }}</a>
                    {{-- <a class="dropdown-item" data-toggle="modal" data-target="#lwAssignGroups" href="#">{{ __tr('Assign
                        Group to Selected Contacts') }}</a> --}}
                </div>
            </div>
            <!-- Assign Groups to the selected contacts -->
            {{-- <x-lw.modal id="lwAssignGroups" :header="__tr('Assign Groups to Selected Contacts')" :hasForm="true"
                data-pre-callback="appFuncs.clearContainer">
                <!-- form body -->
                <div class="lw-form-modal-body p-4">
                   
                </div>
                <!-- form footer -->
                <div class="modal-footer">
                    <!-- Submit Button -->
                    <button type="button" @click="assignGroupsToSelectedContacts" class="btn btn-primary">{{
                        __('Submit') }}</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
                </div>
                <!--/  Add New Contact Form -->
            </x-lw.modal> --}}
            <!--/ Assign Groups to the selected contacts -->
            <x-lw.datatable data-page-length="100" id="lwGmailToWebList" :url="route('vendor.gmailtoweb.read.list')">
                <th style="width: 1px; padding: 0;" data-name="none"></th>
                <th data-name="none" data-template="#lwSelectMultipleGmailsCheckbox">{{ __tr('Select') }}</th>
                <th data-orderable="true" data-name="id">{{ __tr('ID') }}</th>
                <th data-orderable="true" data-name="from_email">{{ __tr('From Email') }}</th>
                <th data-orderable="true" data-name="to_email">{{ __tr('To Email') }}</th>
                <th data-orderable="true" data-name="subject">{{ __tr('Subject') }}</th>
                <th data-orderable="true" data-name="received_at">{{ __tr('Received On') }}</th>
                <th data-orderable="true" data-name="testing">{{ __tr('Status') }}</th>
                <th data-template="#gmailActionColumnTemplate" name="null">{{ __tr('Action') }}</th>
            </x-lw.datatable>
        </div>
        <!-- action template -->
        <script type="text/template" id="lwSelectMultipleGmailsCheckbox">
            <input @click="toggle('<%- __tData.id %>')" type="checkbox" name="selected_gmailtoweb[]" class="lw-checkboxes custom-checkbox" value="<%- __tData.id %>">
        </script>
        <script type="text/template" id="gmailActionColumnTemplate">
            <a data-pre-callback="appFuncs.clearContainer" title="{{  __tr('Details') }}" class="lw-btn btn btn-sm btn-default lw-ajax-link-action" data-response-template="#lwDetailsGmailToWebBody" href="<%= __Utils.apiURL("{{ route('vendor.gmailtoweb.read.data', [ 'gmailIdOrUid']) }}", {'gmailIdOrUid': __tData.id}) %>"  data-toggle="modal" data-target="#lwDetailsGmailToWeb"><i class="fa fa-info-circle"></i> {{  __tr('Details') }}</a>

 <a data-method="post" href="<%= __Utils.apiURL("{{ route('vendor.gmailtoweb.delete', [ 'gmailIdOrUid']) }}", {'gmailIdOrUid': __tData.id}) %>" class="btn btn-danger btn-sm lw-ajax-link-action-via-confirm" data-confirm="#lwDeleteGmailToWeb-template" title="{{ __tr('Delete') }}" data-callback-params="{{ json_encode(['datatableId' => '#lwGmailToWebList']) }}" data-callback="appFuncs.modelSuccessCallback"><i class="fa fa-trash"></i> {{  __tr('Delete') }}</a>
    </script>
        <!-- /action template -->
        <!-- Contact delete template -->
        <script type="text/template" id="lwDeleteGmailToWeb-template">
            <h2>{{ __tr('Are You Sure!') }}</h2>
            <p>{{ __tr('Are you sure you want to delete this Gmail To Web permanently?') }}</p>
    </script>
        <!-- /Contact delete template -->
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css" />
@push('appScripts')
<script>
(function($) {
    'use strict';
    window.onUpdateContactDetails = function(responseData, callbackParams) {
        appFuncs.modelSuccessCallback(responseData, callbackParams);
    }
})(jQuery);
</script>

<script>
     @if(session()->has('success'))
            toastr.success('{{ session('success') }}');
        @endif

        @if(session()->has('error'))
            toastr.error('{{ session('error') }}');
        @endif
</script>
@endpush
@endsection()