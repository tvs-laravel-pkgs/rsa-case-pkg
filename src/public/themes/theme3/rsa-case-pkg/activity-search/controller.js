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
                custom_noty('error', 'Enter Case Number / Vehicle Registration Number / VIN / Mobile Number / CRM Activity ID');
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
                        { data: 'case_date', searchable: false },
                        { data: 'case_number', name: 'cases.number', searchable: true },
                        { data: 'vehicle_registration_number', name: 'cases.vehicle_registration_number', searchable: true },
                        { data: 'vin', name: 'cases.vin_no', searchable: true },
                        { data: 'asp', name: 'asp', searchable: true },
                        { data: 'crm_activity_id', name: 'activities.crm_activity_id', searchable: true },
                        { data: 'sub_service', name: 'service_types.name', searchable: true },
                        { data: 'finance_status', name: 'activity_finance_statuses.name', searchable: true },
                        { data: 'status', name: 'activity_portal_statuses.name', searchable: true },
                        { data: 'activity_status', name: 'activity_statuses.name', searchable: true },
                        { data: 'client', name: 'clients.name', searchable: true },
                        { data: 'call_center', name: 'call_centers.name', searchable: true },
                    ],
                    ordering: false,
                    processing: true,
                    serverSide: true,
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