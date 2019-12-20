app.component('invoiceList', {
    templateUrl: invoice_list_template_url,
    controller: function($http, $window, HelperService, $scope, $rootScope) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.filter_img_url = filter_img_url;
        $http.get(
            invoice_filter_url
        ).then(function(response) {
            self.extras = response.data.extras;

            var cols = [
                { data: 'action', searchable: false },
                { data: 'invoice_no', name: 'Invoices.invoice_no', searchable: true },
                { data: 'invoice_date',  searchable: false },
                { data: 'asp_code', name: 'asps.asp_code', searchable: true },
                { data: 'workshop_name', name: 'asps.workshop_name', searchable: true },
                { data: 'no_of_tickets',  searchable: false },
                { data: 'invoice_amount',  searchable: false },
            ];

            var activities_status_dt_config = JSON.parse(JSON.stringify(dt_config));

            $('#invoice_table').DataTable(
                $.extend(activities_status_dt_config, {
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
                        url: invoice_get_list_url,
                        data: function(d) {
                            d.date = $('#date').val();
                        }
                    },
                    infoCallback: function(settings, start, end, max, total, pre) {
                        $('.count').html(total + ' / ' + max + ' listings')
                    },
                    initComplete: function() {},
                }));

            $('.dataTables_length select').select2();

            var dataTable = $('#invoice_table').dataTable();

            $(".filterTable").keyup(function() {
                dataTable.fnFilter(this.value);
            });

            $('#date').on('change', function() {
                dataTable.fnFilter();
            });

            $('.reset-filter').on('click', function() {
                $('#date').val('');
                dataTable.fnFilter();
            });

            $scope.refresh = function() {
                $('#invoice_table').DataTable().ajax.reload();
            };

            $('.filterToggle').click(function() {
                $('#filterticket').toggleClass('open');
            });

            $('.close-filter, .filter-overlay').click(function() {
                $(this).parents('.filter-wrapper').removeClass('open');
            });

            $('.date-picker').datepicker({
                format: 'dd-mm-yyyy',
                autoclose: true,
            });

            $rootScope.loading = false;
        });
    }
});

app.component('activityStatusView1', {
    templateUrl: activity_status_view_template_url,
    controller: function($http, $location, $window, HelperService, $scope, $routeParams, $rootScope, $location) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.filter_img_url = filter_img_url;
        console.log($routeParams.id);
        console.log(activity_status_view_data_url);
        get_view_data_url = typeof($routeParams.id) == 'undefined' ? activity_status_view_data_url : activity_status_view_data_url + '/' + $routeParams.id;
        $http.get(
            get_view_data_url
        ).then(function(response) {
            console.log(response);
            self.data = response.data.data.activities;
            console.log(self.data);
            $rootScope.loading = false;
        });
    }
});