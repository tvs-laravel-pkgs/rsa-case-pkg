app.component('generalReportView', {
    templateUrl: general_report_list_template_url,
    controller: function($http, $window, HelperService, $scope, $rootScope, $mdSelect) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.csrf = token;
        $http.get(
            laravel_routes['getGeneralReport']
        ).then(function(response) {
            console.log(response);

            $rootScope.loading = false;

        });
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------