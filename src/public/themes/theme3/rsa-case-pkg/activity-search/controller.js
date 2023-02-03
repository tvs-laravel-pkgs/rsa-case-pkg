app.component('activitySearchForm', {
    templateUrl: activity_search_form_template_url,
    controller: function($http, $location, $window, HelperService, $scope, $routeParams, $rootScope, $location, $mdSelect, $route, $timeout) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        if (!self.hasPermission('asp-activity-search')) {
            window.location = "#!/page-permission-denied";
            return false;
        }

        self.angular_routes = angular_routes;
        self.style_dot_image_url = style_dot_image_url;
        self.modal_close = modal_close;
        self.type = $routeParams.type;
        self.activities = [];

        $scope.searchActivity = function(query) {
            self.activities = [];
            $http.post(
                laravel_routes['getActivitySearchFormData'], {
                    data: query,
                }
            ).then(function(res) {
                if (!res.data.success) {
                    let errors = '';
                    for (let i in res.data.errors) {
                        errors += '<li>' + res.data.errors[i] + '</li>';
                    }
                    custom_noty('error', errors);
                } else {
                    self.activities = res.data.activities;
                }
            });
        }

        $timeout(function() {
            let dataTable = $('#activityTable').DataTable({
                "bLengthChange": false,
                "bRetrieve": true,
                "paginate": true,
                ordering: false,
                'paging': false,
                'searching': false,
                'info': false,
                'length': false,
                "oLanguage": { "sZeroRecords": "", "sEmptyTable": "" }
            });
            $('#activityTbody .dataTables_empty').hide();
            $scope.$apply()
        }, 500);


        $('.viewData-toggle--inner.noToggle .viewData-threeColumn--wrapper').slideDown();

        $('#viewData-toggle--btn1').click(function() {
            $(this).toggleClass('viewData-toggle--btn_reverse');
            $('#viewData-threeColumn--wrapper1').slideToggle();
        });

        $('#viewData-toggle--btnasp').click(function() {
            $(this).toggleClass('viewData-toggle--btn_reverse');
            $('#viewData-threeColumn--wrapperasp').slideToggle();
        });

        window.mdSelectOnKeyDownOverride = function(event) {
            event.stopPropagation();
        };

        $('.modal').bind('click', function(event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });
        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------