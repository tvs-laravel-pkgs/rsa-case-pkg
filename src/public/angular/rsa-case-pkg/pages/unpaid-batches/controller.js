app.component('unpaidBatchesList', {
    templateUrl: unpaid_batch_list_template_url,
    controller: function($http, $window, HelperService, $scope, $rootScope) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.filter_img_url = filter_img_url;
        self.export_unpaid_batch_url = export_unpaid_batch_url;
        self.token = token;
        $("#status").val('-1');

        var cols = [
            { data: 'action', orderable: false, targets: 0, searchable: false },
            { data: 'batchid', name: 'batches.id', searchable: false },
            { data: 'batch_number', name: 'batches.batch_number', searchable: true },
            { data: 'created_at', searchable: false },
            { data: 'asp_code', name: 'asps.asp_code', searchable: true },
            { data: 'asp_name', name: 'asps.name', searchable: true },
            { data: 'asp_type', searchable: false },
            { data: 'tickets_count', searchable: false },
            { data: 'invoices_count', searchable: false },
            { data: 'paid_amount', searchable: false },
            { data: 'status', searchable: false },
        ];

        var activities_status_dt_config = JSON.parse(JSON.stringify(dt_config));

        $('#unpaid_batch_table').DataTable(
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
                    url: unpaid_batch_get_list_url,
                    data: function(d) {
                        d.date = $('#date').val();
                        d.workshop_name = $('#work_shop').val();
                        d.batch_number = $('#batch_number').val();
                        d.asp_code = $('#asp_code').val();
                        d.status = $('#status').val();
                    }
                },
                infoCallback: function(settings, start, end, max, total, pre) {
                    $('.count').html(total + ' / ' + max + ' listings')
                },
                initComplete: function() {},
            }));

        $('.dataTables_length select').select2();

        var dataTable = $('#unpaid_batch_table').dataTable();

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
            $("#status").val('-1');
            self.status = '-1';
            $('.approval-batch').prop('disabled', true);
            dataTable.fnFilter();
        });
        $scope.refresh = function() {
            $('#unpaid_batch_table').DataTable().ajax.reload();
        };

        $('.approval-batch').prop('disabled', true);
        $('#select_all_checkbox').click(function() {
            if ($(this).prop("checked")) {
                $(".child_select_all").prop("checked", true);
                $('.approval-batch').prop('disabled', false);
            } else {
                $(".child_select_all").prop("checked", false);
                $('.approval-batch').prop('disabled', true);
            }
        });

        $scope.selectStatus = function(status) {
            $('.approval-batch').prop('disabled', true);
            setTimeout(function() {
                dataTable.fnFilter();
            }, 500);
        }


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


        $(document).on("click", ".ibtnDel", function() {
            if ($('[name="batch_ids[]"]:checked').length > 0) {
                $('.approval-batch').prop('disabled', false);
            } else {
                $('.approval-batch').prop('disabled', true);
            }
        });


        // var form_id = form_ids = '#unpaid_batch';
        // var v = jQuery(form_ids).validate({
        //     ignore: '',
        //     rules: {
        //         // 'invoice_ids[]': {
        //         //     required: true,
        //         // },
        //     },
        //     invalidHandler: function(event, validator) {
        //         $noty = new Noty({
        //             type: 'error',
        //             layout: 'topRight',
        //             text: 'Please select atleast one invoice',
        //         }).show();
        //         setTimeout(function() {
        //             $noty.close();
        //         }, 1000);
        //     },
        //     submitHandler: function(form) {
        //         let formData = new FormData($(form_id)[0]);
        //         $('#submit').button('loading');
        //         $.ajax({
        //                 url: laravel_routes['generateBatch'],
        //                 method: "POST",
        //                 data: formData,
        //                 processData: false,
        //                 contentType: false,
        //             })
        //             .done(function(res) {
        //                 console.log(res.success);
        //                 if (!res.success) {
        //                     $('#submit').button('reset');
        //                     $noty = new Noty({
        //                         type: 'error',
        //                         layout: 'topRight',
        //                         text: res.error,
        //                     }).show();
        //                     setTimeout(function() {
        //                         $noty.close();
        //                     }, 5000);
        //                 } else {
        //                     $noty = new Noty({
        //                         type: 'success',
        //                         layout: 'topRight',
        //                         text: 'Batches generated successfully.',
        //                     }).show();
        //                     setTimeout(function() {
        //                         $noty.close();
        //                     }, 5000);
        //                     $('#submit').button('reset');
        //                     $('#batch_generation_table').DataTable().ajax.reload();
        //                     $scope.$apply()
        //                 }
        //             })
        //             .fail(function(xhr) {
        //                 $('#submit').button('reset');
        //                 custom_noty('error', 'Something went wrong at server');
        //             });
        //     },
        // });



        $rootScope.loading = false;
    }
});