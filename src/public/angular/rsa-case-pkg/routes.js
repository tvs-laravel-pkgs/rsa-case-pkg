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
    });
}]);