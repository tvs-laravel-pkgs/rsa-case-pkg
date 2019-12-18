app.component('activityVerificationList', {
    templateUrl: activity_verification_list_template_url,
    controller: function($http, $window, HelperService, $scope, $rootScope, $route) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        var cols = [
            { data: 'action', searchable: false },
            { data: 'case_date', searchable: false },
            { data: 'number', name: 'cases.number', searchable: true },
            { data: 'asp_code', name: 'asps.asp_code', searchable: true },
            { data: 'sub_service', name: 'service_types.name', searchable: true },
            // { data: 'asp_status', name: 'activity_asp_statuses.name', searchable: true },
            { data: 'status', name: 'activity_portal_statuses.name', searchable: true },
            { data: 'activity_status', name: 'activity_statuses.name', searchable: true },
            { data: 'client', name: 'clients.name', searchable: true },
            { data: 'call_center', name: 'call_centers.name', searchable: true },
        ];

        var activities_verification_dt_config = JSON.parse(JSON.stringify(dt_config));

        $('#activities_verification_table').DataTable(
            $.extend(activities_verification_dt_config, {
                columns: cols,
                ordering: false,
                processing: true,
                serverSide: true,
                "scrollX": true,
                stateSave: true,
                stateSaveCallback: function(settings, data) {
                    localStorage.setItem('SIDataTables_' + settings.sInstance, JSON.stringify(data));
                },
                stateLoadCallback: function(settings) {
                    var state_save_val = JSON.parse(localStorage.getItem('SIDataTables_' + settings.sInstance));
                    if (state_save_val) {
                        $('.filterTable').val(state_save_val.search.search);
                    }
                    return JSON.parse(localStorage.getItem('SIDataTables_' + settings.sInstance));
                },
                ajax: {
                    url: activity_verification_get_list_url,
                    data: function(d) {}
                },
                infoCallback: function(settings, start, end, max, total, pre) {
                    $('.count').html(total + ' / ' + max + ' listings')
                },
                initComplete: function() {},
            }));
        $('.dataTables_length select').select2();

        var dataTable = $('#activities_verification_table').dataTable();

        $(".filterTable").keyup(function() {
            dataTable.fnFilter(this.value);
        });

        $scope.refresh = function() {
            $('#activities_verification_table').DataTable().ajax.reload();
        };

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------