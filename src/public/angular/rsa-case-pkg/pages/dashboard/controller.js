app.component('dashboard', {
    templateUrl: dashboard_template_url,
    controller: function($http, $window, HelperService, $scope, $rootScope, $mdSelect) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.filter_img_url = filter_img_url;
            alert('kaliga');
        
        $http.get(
            dashboard_data_url
        ).then(function(response) {
            // self.status_list.splice(0, 1);
            
            

        });

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('activityStatusDelete', {
    controller: function($http, $window, HelperService, $scope, $routeParams) {
        $.ajax({
            url: activity_status_delete_row + '/' + $routeParams.id,
            type: 'get',
            success: function(response) {
                // console.log(response);
                if (response.success == true) {
                    new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Activity deleted successfully',
                    }).show();
                    $window.location.href = activity_status_list_url;
                }
            }
        });
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('activityStatusView', {
    templateUrl: activity_status_view_template_url,
    controller: function($http, $location, $window, HelperService, $scope, $routeParams, $rootScope, $location) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.filter_img_url = filter_img_url;
        // self.style_dot_image_url = style_dot_image_url;
        get_view_data_url = typeof($routeParams.id) == 'undefined' ? activity_status_view_data_url + '/' : activity_status_view_data_url + '/' + $routeParams.view_type_id + '/view/' + $routeParams.id;
        $http.get(
            get_view_data_url
        ).then(function(response) {
            console.log(response);
            if (!response.data.success) {
                var errors = '';
                for (var i in response.data.errors) {
                    errors += '<li>' + response.data.errors[i] + '</li>';
                }
                $noty = new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: errors,
                    animation: {
                        speed: 500 // unavailable - no need
                    },

                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 1000);
                $location.path('/rsa-case-pkg/activity-status/list');
                $scope.$apply();
                return;
            }
            self.data = response.data.data.activities;
            self.data.style_dot_image_url = style_dot_image_url;
            self.data.style_service_type_image_url = style_service_type_image_url;
            self.data.style_car_image_url = style_car_image_url;
            self.data.style_location_image_url = style_location_image_url;
            self.data.style_profile_image_url = style_profile_image_url;
            self.data.style_phone_image_url = style_car_image_url;
            self.data.verification = 0;

            $('.viewData-toggle--inner.noToggle .viewData-threeColumn--wrapper').slideDown();
            $('#viewData-toggle--btn1').click(function() {
                $(this).toggleClass('viewData-toggle--btn_reverse');
                $('#viewData-threeColumn--wrapper1').slideToggle();
            });
            $('#viewData-toggle--btnasp').click(function() {
                $(this).toggleClass('viewData-toggle--btn_reverse');
                $('#viewData-threeColumn--wrapperasp').slideToggle();
            });
            $rootScope.loading = false;
        });

    }
});