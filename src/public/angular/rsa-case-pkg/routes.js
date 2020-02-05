app.config(['$routeProvider', function($routeProvider) {

    $routeProvider.
    //case
    when('/case-pkg/case/list', {
        template: '<case-list></case-list>',
        title: 'RsaCases',
    }).
    when('/case-pkg/case/add', {
        template: '<case-form></case-form>',
        title: 'Add RsaCase',
    }).
    when('/case-pkg/case/edit/:id', {
        template: '<case-form></case-form>',
        title: 'Edit RsaCase',
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
    }).

    //EXCEPTIONAL REPORT
    when('/rsa-case-pkg/exceptional-report/list', {
        template: '<exceptional-report-list></exceptional-report-list>',
        title: 'Exceptional Report',
    }).

    //RECONCILIATION REPORT
    when('/rsa-case-pkg/reconciliation-report/view', {
        template: '<reconciliation-report-view></reconciliation-report-view>',
        title: 'Reconciliation Report',
    }).

    //PROVISIONAL REPORT
    when('/rsa-case-pkg/provisional-report/view', {
        template: '<provisional-report-view></provisional-report-view>',
        title: 'Provisional Report',
    }).

    //GENERAL REPORT
    when('/rsa-case-pkg/general-report/view', {
        template: '<general-report-view></general-report-view>',
        title: 'General Report',
    })

    ;
}]);