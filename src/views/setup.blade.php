@if(config('rsa-case-pkg.DEV'))
    <?php $rsa_case_pkg_path = 'packages/abs/rsa-case-pkg/src'?>
@else
    <?php $rsa_case_pkg_path = ''?>
@endif

<!-- RSA-ACTIVITY-STATUS-PKG -->
<script type="text/javascript">

	app.config(['$routeProvider', function($routeProvider) {

	    $routeProvider.
	    //case
	    when('/case-pkg/case/list', {
	        template: '<case-list></case-list>',
	        title: 'RSA Cases',
	    }).
	    when('/case-pkg/case/add', {
	        template: '<case-form></case-form>',
	        title: 'Add RSA Case',
	    }).
	    when('/case-pkg/case/edit/:id', {
	        template: '<case-form></case-form>',
	        title: 'Edit RSA Case',
	    }).

	    //DASHBOARD
	     when('/rsa-case-pkg/dashboard', {
	        template: '<dashboard></dashboard>',
	        title: 'Dashboard',
	    }).

	    //ACTIVITIES
	    when('/rsa-case-pkg/activity-status/list', {
	        template: '<activity-status-list></activity-status-list>',
	        title: 'Activity Status',
	    }).
	    when('/rsa-case-pkg/activity-status/:view_type_id/view/:id', {
	        template: '<activity-status-view></activity-status-view>',
	        title: 'Activity Status View',
	    }).
	    when('/rsa-case-pkg/activity-status/delete/:id', {
	        template: '<activity-status-delete></activity-status-delete>',
	        title: 'Activity Status Delete',
	    }).

	    //ACTIVITY SEARCH
	    when('/rsa-case-pkg/activity-search', {
	        template: '<activity-search-form></activity-search-form>',
	        title: 'Activity Search',
	    }).

	    //ACTIVITY VERIFICATION
	    when('/rsa-case-pkg/activity-verification/list', {
	        template: '<activity-verification-list></activity-verification-list>',
	        title: 'Activity Verification',
	    }).
	    when('/rsa-case-pkg/activity-verification/:view_type_id/view/:id', {
	        template: '<activity-verification-view></activity-verification-view>',
	        title: 'Activity Verification View',
	    }).

	    //NEW ACTIVITY
	    when('/rsa-case-pkg/new-activity', {
	        template: '<new-activity></new-activity>'
	    }).
	    when('/rsa-case-pkg/new-activity/update-details/:id', {
	        template: '<new-activity-update-details></new-activity-update-details>'
	    }).

	    //ASP DEFERRED ACTIVITY
	    when('/rsa-case-pkg/deferred-activity/list', {
	        template: '<deferred-activity-list></deferred-activity-list>',
	        title: 'Deferred Activities',
	    }).
	    when('/rsa-case-pkg/deferred-activity/update/:id', {
	        template: '<deferred-activity-update></deferred-activity-update>',
	        title: 'Deferred Activity Update',
	    }).

	    //ASP APPROVED ACTIVITY
	    when('/rsa-case-pkg/approved-activity/list', {
	        template: '<approved-activity-list></approved-activity-list>',
	        title: 'Approved Activities',
	    }).
	    when('/rsa-case-pkg/approved-activity/invoice/preview/:encryption_key', {
	        template: '<approved-activity-invoice-preview></approved-activity-invoice-preview>',
	        title: 'Approved Activities Invoice Preview',
	    }).

	    //ASP INVOICES
	    when('/rsa-case-pkg/invoice/list/:type_id', {
	        template: '<invoice-list></invoice-list>',
	        title: 'Invoices',
	    }).
	    when('/rsa-case-pkg/invoice/view/:id/:type_id', {
	        template: '<invoice-view></invoice-view>',
	        title: 'Invoice View',
	    }).

	    //ASP BATCH GENERATION
	    when('/rsa-case-pkg/batch-generation/list', {
	        template: '<batch-generation-list></batch-generation-list>',
	        title: 'Invoices',
	    })
	    ;
	}]);

    var asp_new_activity_form_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/new-activity/form.html')}}";
    var asp_new_activity_update_details_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/new-activity/update-details.html')}}";
    var get_activity_form_data_url = "{{route('activityNewGetFormData')}}";
    var getActivityServiceTypeDetail = "{{url('rsa-case-pkg/new-activity/get-service-type-detail')}}";

    var activity_status_list_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/activity-status/list.html')}}";
    
    var activity_search_form_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/activity-search/form.html')}}";

    var activity_status_view_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/activity-status/view.html')}}";
    var activity_status_view_tab_header_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/partials/tab_header.html')}}";
    var activity_status_view_ticket_header2_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/partials/ticket_header2.html')}}";
    var activity_status_view_price_comparison_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/partials/price_comparison.html')}}";
    var activity_status_view_service_details_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/partials/service_details.html')}}";
    var activity_status_view_price_info_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/partials/price_info.html')}}";
    var activity_status_view_service_total_summary_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/partials/service_total_summary.html')}}";
    var activity_status_view_service_total_summary_view_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/partials/service_total_summary_view.html')}}";
    var activity_status_view_activity_details_view_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/partials/activity_details.html')}}";
    var activity_status_view_billing_details_view_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/partials/billing_details.html')}}";
    var view_cc_details = "{{Entrust::can('view-cc-details')}}";

	var activity_status_view_location_details_view_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/partials/location_details.html')}}";
 	var activity_status_view_km_details_view_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/partials/km_details.html')}}";
	var activity_status_view_invoice_details_view_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/partials/invoice_details.html')}}";
	var activity_status_view_financial_details_view_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/partials/financial_details.html')}}";
	var activity_status_view_case_details_view_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/partials/case_details.html')}}";
	var activity_status_view_histories_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/partials/activity_histories.html')}}";
	var style_dot_image_url = "{{URL::asset('resources/assets/images/oval.svg')}}";
	var style_car_image_url = "{{URL::asset('resources/assets/images/ic-car.svg')}}";
	var style_service_type_image_url = "{{URL::asset('resources/assets/images/ic-servicetype.svg')}}";
	var style_location_image_url = "{{URL::asset('resources/assets/images/ic-location.svg')}}";
	var style_profile_image_url = "{{URL::asset('resources/assets/images/ic-profile.svg')}}";
	var style_phone_image_url = "{{URL::asset('resources/assets/images/ic-phone.svg')}}";
	var style_modal_close_image_url = "{{URL::asset('resources/assets/images/modal-close.svg')}}";
	var style_question_image_url = "{{URL::asset('resources/assets/images/question.svg')}}";
	var style_checked_image_url = "{{URL::asset('resources/assets/images/checked.svg')}}";
    var activity_status_view_ticket_total_summary_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/partials/ticket_total_summary.html')}}";
    var activity_status_view_ticket_total_summary_view_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/partials/ticket_total_summary_view.html')}}";
    var activity_status_view_asp_details_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/partials/asp_details.html')}}";
    var activity_status_get_list_url = "{{url('rsa-case-pkg/activity-status/get-list/')}}";
    var activity_status_delete_url = "{{url('#!/rsa-case-pkg/activity-status/delete')}}";
    var activity_status_delete_row = "{{url('/rsa-case-pkg/activity-status/delete')}}";
    var activity_status_list_url = "{{url('#!/rsa-case-pkg/activity-status/list')}}";
    var activity_status_filter_url = "{{url('rsa-case-pkg/activity-status/get-filter-data')}}";
    var activity_status_view_data_url = "{{url('rsa-case-pkg/activity-status/')}}";
    var activity_verification_view_data_url = "{{url('rsa-case-pkg/activity-verification/')}}";
    var filter_img_url = "{{ asset('resources/assets/images/filter.svg') }}";
    var export_activities = "{{route('exportActivities')}}";
    var canExportActivity = "{{Entrust::can('export-activities')}}";
    var canImportActivity = "{{Entrust::can('import-cron-jobs')}}";
    var activity_back_asp_update = "{{route('activityBackAspUpdate')}}";
    var activity_towing_images_required_url = "{{route('activityTowingImagesRequiredUpdated')}}";
    var releaseOnHold = "{{route('releaseOnHold')}}";
    var releaseOnHoldActivity = "{{url('rsa-case-pkg/onhold-activity/release')}}";

    var getServiceTypeRateCardDetail = "{{route('getActivityServiceTypeRateCardDetail')}}";

</script>
<script type="text/javascript" src="{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/common/controller.js?v=12')}}"></script>
<script type="text/javascript" src="{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/activity-status/controller.js?v=9')}}"></script>
<!-- RSA-NEW-ACTIVITY-PKG -->
<script type="text/javascript">
    var asp_new_activity_form_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/new-activity/form.html')}}";
    var asp_new_activity_update_details_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/new-activity/update-details.html')}}";
    var get_activity_form_data_url = "{{route('activityNewGetFormData')}}";
</script>
<script type="text/javascript" src="{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/new-activity/controller.js?v=15')}}"></script>

<!-- RSA-DASHBOARD -->
<script type="text/javascript">
    var dashboard_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/dashboard/dashboard.html')}}";
    var dashboard_data_url = "{{url('rsa-case-pkg/dashboard/get-data/')}}";
    var dash2 = "{{ asset('resources/assets/images/dash-icon-2.svg') }}";
    var dash1 = "{{ asset('resources/assets/images/dash-icon-1.svg') }}";
    var dash3 = "{{ asset('resources/assets/images/dash-icon-3.svg') }}";
    var dash4 = "{{ asset('resources/assets/images/dash-icon-4.svg') }}";

</script>
<script type="text/javascript" src="{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/dashboard/controller.js?v=3')}}"></script>

<!-- RSA-ACTIVITY-VERIFICATION-PKG -->
<script type="text/javascript">
    var activity_verification_list_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/activity-verification/list.html')}}";
    var activity_verification_view_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/activity-verification/view.html')}}";
    var activity_verification_bulk_get_list_url = "{{url('rsa-case-pkg/activity-verification/bulk/get-list/')}}";
    var activity_verification_individual_get_list_url = "{{url('rsa-case-pkg/activity-verification/individual/get-list/')}}";
</script>
<script type="text/javascript" src="{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/activity-verification/controller.js?v=8')}}"></script>


<!-- RSA-INVOICE -->
<script type="text/javascript">
    var invoice_list_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/invoice/list.html')}}";
    var invoice_view_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/invoice/view.html')}}";
    var invoice_filter_url = "{{url('rsa-case-pkg/invoice/get-filter-data')}}";
    var invoice_get_list_url = "{{url('rsa-case-pkg/invoice/get-list/')}}";
    var invoice_view_data_url = "{{url('rsa-case-pkg/invoice/view/')}}";
    var invoice_pdf_generate_url = "{{url('rsa-case-pkg/invoice/download/')}}";
    var canExport = "{{Entrust::can('export-asp-unpaid-invoices')}}";
    var canViewPaymentInfo = "{{Entrust::can('view-invoice-payment-info')}}";
    var export_invoices = "{{route('exportInvoice')}}";
    var get_invoice_payment_info_url = "{{url('/rsa-case-pkg/invoice/get/payment-info/')}}";

</script>
<script type="text/javascript" src="{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/invoice/controller.js?v=7')}}"></script>

<!-- RSA-BATCH GENEATION -->
<script type="text/javascript">
    var batch_generation_list_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/batch-generation/list.html')}}";
    var batch_generation_filter_url = "{{url('rsa-case-pkg/batch-generation/get-filter-data')}}";
    var batch_generation_get_list_url = "{{url('rsa-case-pkg/batch-generation/get-list/')}}";
</script>
<script type="text/javascript" src="{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/batch-generation/controller.js?v=2')}}"></script>

<!-- RSA-ACTIVITY-DEFERRED -->
<script type="text/javascript">
    var activity_deferred_list_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/activity-deferred/list.html')}}";
    var activity_deferred_filter_url = "{{url('rsa-case-pkg/activity-deferred/get-filter-data')}}";
    var activity_deferred_get_list_url = "{{url('rsa-case-pkg/activity-deferred/get-list/')}}";

    var asp_activity_deferred_update_details_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/activity-deferred/update-details.html')}}";
    var get_deferred_activity_form_data_url = "{{route('activityDeferredGetFormData')}}";
</script>
<script type="text/javascript" src="{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/activity-deferred/controller.js?v=13')}}"></script>

<script type="text/javascript" src="{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/activity-search/controller.js')}}"></script>

<!-- RSA-ACTIVITY-APPROVED -->
<script type="text/javascript">
    var activity_approved_list_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/activity-approved/list.html')}}";
    var activity_approved_filter_url = "{{url('rsa-case-pkg/activity-approved/get-filter-data')}}";
    var activity_approved_get_list_url = "{{url('rsa-case-pkg/activity-approved/get-list/')}}";

    var activity_approved_invoice_preview_template_url = "{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/activity-approved/invoice-preview.html')}}";
    var activity_approved_invoice_preview_data_url = "{{url('rsa-case-pkg/activity-approved/get-details/')}}";
</script>
<script type="text/javascript" src="{{URL::asset($rsa_case_pkg_path.'/public/themes/'.$theme.'/rsa-case-pkg/activity-approved/controller.js?v=6')}}"></script>

