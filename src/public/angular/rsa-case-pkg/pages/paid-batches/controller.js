app.component('paidBatchesList', {
    templateUrl: paid_batch_list_template_url,
    controller: function($http, $window, HelperService, $scope, $rootScope) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.filter_img_url = filter_img_url;
     
        var cols = [
            { data: 'batchid', name: 'batches.id', searchable: false },
            { data: 'batch_number', name: 'batches.batch_number', searchable: true },
            { data: 'created_at', searchable: false },
            { data: 'asp_code', name: 'asps.asp_code', searchable: true },
            { data: 'asp_name', name: 'asps.name', searchable: true },
            { data: 'asp_type', searchable: false },
            { data: 'date_period', searchable: false },
            { data: 'tickets_count', searchable: false },
            { data: 'tds', searchable: false },
            { data: 'paid_amount', searchable: false },
        ];

        var activities_status_dt_config = JSON.parse(JSON.stringify(dt_config));

        $('#paid_batch_table').DataTable(
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
                    url: paid_batch_get_list_url,
                    data: function(d) {
                        d.date = $('#date').val();
                        d.workshop_name = $('#work_shop').val();
                        d.batch_number = $('#batch_number').val();
                        d.asp_code = $('#asp_code').val();
                    }
                },
                infoCallback: function(settings, start, end, max, total, pre) {
                    $('.count').html(total + ' / ' + max + ' listings')
                },
                initComplete: function() {},
            }));

        $('.dataTables_length select').select2();

        var dataTable = $('#paid_batch_table').dataTable();

        $('#batch_number,#asp_code,#work_shop').on('keyup', function() {
            dataTable.fnFilter();
        });

        $(".filterTable").keyup(function() {
            dataTable.fnFilter(this.value);
        });

        $('#date').on('change', function() {
            dataTable.fnFilter();
        });

        $('.reset-filter').on('click', function() {
            $('#date').val('');
            $('#batch_number').val('');
            $('#asp_code').val('');
            $('#work_shop').val('');
            dataTable.fnFilter();
        });
        $scope.refresh = function() {
            $('#paid_batch_table').DataTable().ajax.reload();
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
    }
});

app.component('batchView', {
    templateUrl: batch_view_template_url,
    controller: function($http, $location, $window, HelperService, $scope, $routeParams, $rootScope, $location) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.filter_img_url = filter_img_url;
        self.type_id = $routeParams.type_id;
        self.invoice_pdf_generate_url = invoice_pdf_generate_url;
        get_view_data_url = typeof($routeParams.id) == 'undefined' ? invoice_view_data_url : invoice_view_data_url + '/' + $routeParams.id;
        $http.get(
            get_view_data_url
        ).then(function(response) {
            console.log(response.data);
            self.period = response.data.period;
            self.asp = response.data.asp;
            self.rsa_address = response.data.rsa_address;
            self.activities = response.data.activities;
            self.invoice_amount = response.data.invoice_amount;
            self.signature_attachment = response.data.signature_attachment;
            self.signature_attachment_path = response.data.signature_attachment_path;
            self.invoice_attachment_file = response.data.invoice_attachment_file;
            self.invoice = response.data.invoice;
            self.invoice_availability = response.data.invoice_availability;
            $rootScope.loading = false;
        });

        setTimeout(function() {
            $('#aspLogin-table').DataTable({
                "bLengthChange": false,
                "paginate": false,
                "oLanguage": {"sZeroRecords": "", "sEmptyTable": ""},
            });
        }, 10);

        $('.viewData-toggle--inner.noToggle .viewData-threeColumn--wrapper').slideDown();
        $('.viewData-toggle--btn').click(function() {
            $(this).toggleClass('viewData-toggle--btn_reverse');
            $('.viewData-toggle--inner .viewData-threeColumn--wrapper').slideToggle();
        });

    }
});