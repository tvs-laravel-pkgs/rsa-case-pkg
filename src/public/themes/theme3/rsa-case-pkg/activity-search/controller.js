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

        $scope.searchActivity = function(searchQuery) {
            if (!searchQuery) {
                custom_noty('error', 'Enter Case Number / Vehicle Registration Number / VIN / Mobile Number / CRM Activity ID / CSR');
                return;
            }

            if ($.fn.dataTable.isDataTable('#activityTable')) {
                $('#activityTable').dataTable().fnDestroy();
            }

            const activitySearchDtConfig = JSON.parse(JSON.stringify(dt_config));
            $('#activityTable').DataTable(
                $.extend(activitySearchDtConfig, {
                    stateSave: true,
                    columns: cols = [
                        { data: 'action', searchable: false },
                        { data: 'case.case_date', searchable: false },
                        { data: 'case.case_number', searchable: true },
                        { data: 'case.vehicle_registration_number', searchable: true },
                        { data: 'case.vin', searchable: true },
                        { data: 'asp.name', searchable: true },
                        { data: 'crm_activity_id', searchable: true },
                        { data: 'case.csr', searchable: true },
                        { data: 'service_type.name', searchable: true },
                        { data: 'finance_status.name', searchable: true },
                        { data: 'status.name', searchable: true },
                        { data: 'activity_status.name', searchable: true },
                        { data: 'case.client.name', searchable: true },
                        { data: 'case.callcenter.name', searchable: true },
                    ],
                    ordering: false,
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: laravel_routes['getActivitySearchList'],
                        type: "POST",
                        dataType: "json",
                        data: function(d) {
                            d.searchQuery = searchQuery;
                        }
                    },
                    infoCallback: function(settings, start, end, max, total, pre) {
                        $('.count').html(total + ' / ' + max + ' listings');
                    },
                    initComplete: function() {
                        $('.dataTables_length select').select2();
                    },
                }));

            const dataTable = $('#activityTable').dataTable();

            $(".filterTable").keyup(function() {
                dataTable.fnFilter(this.value);
            });
        }

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