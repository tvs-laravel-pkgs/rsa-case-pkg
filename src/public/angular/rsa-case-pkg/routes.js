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
    when('/rsa-case-pkg/activity-status/view/:id', {
        template: '<activity-status-view></activity-status-view>',
        title: 'Activity Status View',
    }).

    //ACTIVITY VERIFICATION
    when('/rsa-case-pkg/activity-verification/list', {
        template: '<activity-verification-list></activity-verification-list>',
        title: 'Activity Verification',
    }).
    when('/rsa-case-pkg/activity-verification/view/:id', {
        template: '<activity-verification-view></activity-verification-view>',
        title: 'Activity Verification View',
    }).

    //ASP INVOICES
    when('/rsa-case-pkg/asp - invoice / list', {
        template: '<asp-invoice-list></asp-invoice-list>',
        title: 'ASP Invoices',
    }).
    when('/rsa-case-pkg/asp-invoice/view/:id', {
        template: '<asp-invoice-view></asp-invoice-view>',
        title: 'ASP Invoice View',
    })

    ;
}]);