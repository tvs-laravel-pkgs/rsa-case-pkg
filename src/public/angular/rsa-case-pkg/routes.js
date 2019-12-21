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
    when('/rsa-case-pkg/activity-status/delete/:id', {
        template: '<activity-status-delete></activity-status-delete>',
        title: 'Activity Status Delete',
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

    //INVOICES
    when('/rsa-case-pkg/invoice/list', {
        template: '<invoice-list></invoice-list>',
        title: 'Invoices',
    }).

    //INVOICE VIEW
    when('/rsa-case-pkg/invoice/view/:id/:type_id?', {
        template: '<invoice-view></invoice-view>',
        title: 'Invoice View',
    }).

    //BATCH GENERATION
    when('/rsa-case-pkg/batch-generation/list', {
        template: '<batch-generation-list></batch-generation-list>',
        title: 'Invoices',
    }).

    //PAID BATCHES
    when('/rsa-case-pkg/paid-batches/list', {
        template: '<paid-batches-list></paid-batches-list>',
        title: 'Paid Batches',
    }).

    //UNPAID BATCHES
    when('/rsa-case-pkg/unpaid-batches/list', {
        template: '<unpaid-batches-list></unpaid-batches-list>',
        title: 'Unpaid Batches',
    }).

    //BATCH VIEW
    when('/rsa-case-pkg/batch-view/:id', {
        template: '<batch-view></unpaid-batches-list>',
        title: 'Batch View',
    })


    ;
}]);