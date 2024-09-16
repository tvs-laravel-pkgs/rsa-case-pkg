app.component('unclaimTicketList', {
    templateUrl: unclaim_ticket_list_template_url,
    controller: function($http, $window, HelperService, $scope, $rootScope, $mdSelect) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('unclaim-tickets')) {
            window.location = "#!/page-permission-denied";
            return false;
        }

        self.filter_img_url = filter_img_url;
        self.csrf = token;
        
        $http.get(
            unclaim_ticket_filter_url
        ).then(function(response) {
            self.extras = response.data.extras;
            self.auth_user_details = response.data.auth_user_details;
            self.status_list = response.data.extras.portal_status_list;
            self.client_list = response.data.extras.export_client_list;
            self.asp_list = response.data.extras.asp_list;
            self.modal_close = modal_close;
            var cols = [
                { data: 'action', searchable: false },
                { data: 'case_date', searchable: false },
                { data: 'number', name: 'cases.number', searchable: true },
                { data: 'vehicle_registration_number', name: 'cases.vehicle_registration_number', searchable: true },
                { data: 'asp', name: 'asp', searchable: true },
                // { data: 'asp_code', name: 'asps.asp_code', searchable: true },
                { data: 'crm_activity_id', searchable: false },
                { data: 'source', name: 'configs.name', searchable: true },
                // { data: 'activity_number', name: 'activities.number', searchable: true },
                { data: 'sub_service', name: 'service_types.name', searchable: true },
                { data: 'finance_status', name: 'activity_finance_statuses.name', searchable: true },
                // { data: 'asp_status', name: 'activity_asp_statuses.name', searchable: true },
                { data: 'status', name: 'activity_portal_statuses.name', searchable: true },
                { data: 'activity_status', name: 'activity_statuses.name', searchable: true },
                { data: 'client', name: 'clients.name', searchable: true },
                { data: 'call_center', name: 'call_centers.name', searchable: true },
            ];
            
            $('input[name="date_range_period"]').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear',
                    format: "DD-MM-YYYY"
                }
            });

            var unclaim_ticket_dt_config = JSON.parse(JSON.stringify(dt_config));
            $('#unclaim_ticket_table').DataTable(
                $.extend(unclaim_ticket_dt_config, {
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
                        url: unclaim_ticket_get_list_url,
                        data: function(d) {
                            d.date_range_period = $('#date_range_period').val();
                            d.call_center_id = $('#call_center_id').val();
                            d.case_number = $('#case_number').val();
                            d.asp_code = $('#asp_code').val();
                            d.service_type_id = $('#service_type_id').val();
                            d.finance_status_id = $('#finance_status_id').val();
                            d.activity_status_id = $('#activity_status_id').val();
                            d.client_id = $('#client_id').val();
                        }
                    },
                    infoCallback: function(settings, start, end, max, total, pre) {
                        $('.count').html(total + ' / ' + max + ' listings')
                    },
                    initComplete: function() {},
                }));
            $('.dataTables_length select').select2();

            var dataTable = $('#unclaim_ticket_table').dataTable();

            $('.daterange').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD-MM-YYYY') + ' - ' + picker.endDate.format('DD-MM-YYYY'));
                $('#date_range_period').val(picker.startDate.format('DD-MM-YYYY') + ' - ' + picker.endDate.format('DD-MM-YYYY'));
                dataTable.fnFilter();
            });

            $('.daterange').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                $('#date_range_period').val('');
                dataTable.fnFilter();
            });

            $(".filterTable").keyup(function() {
                dataTable.fnFilter(this.value);
            });

            $('#case_number,#asp_code').on('keyup', function() {
                dataTable.fnFilter();
            });

            $scope.changeCommonFilter = function(val, id) {
                $('#' + id).val(val);
                dataTable.fnFilter();
            };

            $scope.resetFilter = function() {
                self.ticket_filter = [];
                $('#date_range_period').val('');
                $('#call_center_id').val('');
                $('#service_type_id').val('');
                $('#finance_status_id').val('');
                $('#activity_status_id').val('');
                $('#client_id').val('');

                setTimeout(function() {
                    dataTable.fnFilter();
                    $('#unclaim_ticket_table').DataTable().ajax.reload();
                }, 1000);
            };

            $scope.refresh = function() {
                $('#unclaim_ticket_table').DataTable().ajax.reload();
            };

            $('.filterToggle').click(function() {
                $('#filterticket').toggleClass('open');
            });

            $('.close-filter, .filter-overlay').click(function() {
                $(this).parents('.filter-wrapper').removeClass('open');
            });

        
            $rootScope.loading = false;
            window.mdSelectOnKeyDownOverride = function(event) {
                event.stopPropagation();
            };
            $('.filter-content').bind('click', function(event) {
                if ($('.md-select-menu-container').hasClass('md-active')) {
                    $mdSelect.hide();
                }
            });
        });
    }
});