app.component('exceptionalReportList', {
    templateUrl: exceptional_report_list_template_url,
    controller: function($http, $window, HelperService, $scope, $rootScope, $mdSelect) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.filter_img_url = filter_img_url;
        self.csrf = token;
        $http.get(
            exceptional_report_filter_url
        ).then(function(response) {
            self.extras = response.data.extras;

            var cols = [
                { data: 'number', name: 'cases.number', searchable: true },
                { data: 'case_date', searchable: false },
                { data: 'asp_code', name: 'asps.asp_code', searchable: true },
                { data: 'asp_name', name: 'asps.name', searchable: true },
                { data: 'asp_type', name: 'asps.asp_type', searchable: false },
                { data: 'sub_service', name: 'service_types.name', searchable: true },
                { data: 'bo_km', name: 'bo_km_travelled.value', searchable: true },
                { data: 'deviation_reason', name: 'activities.reason_for_asp_rejected_cc_details', searchable: true },
                { data: 'bo_paid_amount', name: 'bo_paid_amt.value', searchable: true },
            ];

            var exceptional_report_dt_config = JSON.parse(JSON.stringify(dt_config));

            $('#exceptional_report_table').DataTable(
                $.extend(exceptional_report_dt_config, {
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
                        url: exceptional_report_get_list_url,
                        data: function(d) {
                            d.ticket_date = $('#ticket_date').val();
                            d.call_center_id = $('#call_center_id').val();
                            d.case_number = $('#case_number').val();
                            d.service_type_id = $('#service_type_id').val();
                            d.finance_status_id = $('#finance_status_id').val();
                        }
                    },
                    infoCallback: function(settings, start, end, max, total, pre) {
                        $('.count').html(total + ' / ' + max + ' listings')
                    },
                    initComplete: function() {},
                }));
            $('.dataTables_length select').select2();

            var dataTable = $('#exceptional_report_table').dataTable();

            $(".filterTable").keyup(function() {
                dataTable.fnFilter(this.value);
            });

            $('#ticket_date').on('change', function() {
                dataTable.fnFilter();
            });

            $('#case_number').on('keyup', function() {
                dataTable.fnFilter();
            });

            $scope.changeCommonFilter = function(val, id) {
                $('#' + id).val(val);
                dataTable.fnFilter();
            };

            $scope.resetFilter = function() {
                self.ticket_filter = [];
                $('#call_center_id').val('');
                $('#service_type_id').val('');
                $('#finance_status_id').val('');

                setTimeout(function() {
                    dataTable.fnFilter();
                    $('#exceptional_report_table').DataTable().ajax.reload();
                }, 1000);
            };

            $scope.refresh = function() {
                $('#exceptional_report_table').DataTable().ajax.reload();
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
            $('input[name="period"]').daterangepicker({
                startDate: moment().startOf('month'),
                endDate: moment().endOf('month'),
            });

            $rootScope.loading = false;

        });

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------