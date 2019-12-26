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
    //NEW ACTIVITY 
    
    when('/asp/new-activity', {
        template: '<new-activity></new-activity>'
    }).
    when('/asp/new-activity/update-details/:id', {
        template: '<new-activity-update-details></new-activity-update-details>'
    }).

    //NEW ACTIVITY
    when('/asp/new-differed', {
        template: '<new-differed></new-differed>'
    }).
    when('/asp/new-differed/update-details/:id', {
        template: '<new-differed-update-details></new-differed-update-details>'
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

    //ASP INVOICES
    when('/rsa-case-pkg/invoice/list', {
        template: '<invoice-list></invoice-list>',
        title: 'Invoices',
    }).
    when('/rsa-case-pkg/invoice/view/:id/:type_id?', {
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