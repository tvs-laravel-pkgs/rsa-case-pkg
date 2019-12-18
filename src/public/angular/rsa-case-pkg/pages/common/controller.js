app.component('aspActivitiesHeader', {
    templateUrl: activity_status_view_tab_header_template_url,
    bindings: {
        po: '<',
    },
    controller: function($http, HelperService, $scope, $rootScope, $routeParams, $location) {
        
        //end

    }
});

//----------------------------------------------------------------------------------------------------------------------------

app.component('aspDetails', {
    templateUrl: activity_status_view_asp_details_template_url,
    bindings: {
        po: '<',
    },
    controller: function($http, HelperService, $scope, $rootScope, $routeParams, $location) {
        self.data = activity;

    }
});