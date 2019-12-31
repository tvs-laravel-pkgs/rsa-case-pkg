app.component('approvedActivityList', {
    templateUrl: activity_approved_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;

        self.filter_img_url = filter_img_url;
        $http.get(
            activity_approved_filter_url
        ).then(function(response) {
            self.extras = response.data.extras;

            var cols = [
                { data: 'action', searchable: false },
                { data: 'case_date', searchable: false },
                { data: 'number', name: 'cases.number', searchable: true },
                { data: 'crm_activity_id', searchable: false },
                { data: 'client', name: 'clients.name', searchable: true },
                { data: 'call_center', name: 'call_centers.name', searchable: true },
                { data: 'sub_service', name: 'service_types.name', searchable: true },
                { data: 'finance_status', name: 'activity_finance_statuses.name', searchable: true },
                { data: 'net_amount', searchable: false },
                { data: 'not_collected_amount', searchable: false },
                { data: 'colleced_amount', searchable: false },
                { data: 'invoice_amount', searchable: false },
            ];

            var activities_approved_dt_config = JSON.parse(JSON.stringify(dt_config));

            $('#invoice_generation_table').DataTable(
                $.extend(activities_approved_dt_config, {
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
                        url: activity_approved_get_list_url,
                        data: function(d) {
                            d.ticket_date = $('#ticket_date').val();
                            d.call_center_id = $('#call_center_id').val();
                            d.case_number = $('#case_number').val();
                            // d.asp_code = $('#asp_code').val();
                            d.service_type_id = $('#service_type_id').val();
                            d.finance_status_id = $('#finance_status_id').val();
                            d.client_id = $('#client_id').val();
                        }
                    },
                    infoCallback: function(settings, start, end, max, total, pre) {
                        $('.count').html(total + ' / ' + max + ' listings')
                    },
                    initComplete: function() {},
                }));
            $('.dataTables_length select').select2();

            var dataTable = $('#invoice_generation_table').dataTable();

            $(".filterTable").keyup(function() {
                dataTable.fnFilter(this.value);
            });

            $('#ticket_date').on('change', function() {
                dataTable.fnFilter();
            });

            $('#case_number,#asp_code').on('keyup', function() {
                dataTable.fnFilter();
            });

            $scope.changeCommonFilter = function(val, id) {
                $('#' + id).val(val);
                dataTable.fnFilter();
            };

            $scope.refresh = function() {
                $('#invoice_generation_table').DataTable().ajax.reload();
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

            $('#select_all_checkbox').click(function() {
                if ($(this).prop("checked")) {
                    $(".child_select_all").prop("checked", true);
                } else {
                    $(".child_select_all").prop("checked", false);
                }
            });

            var form_id = form_ids = '#invoice_generation';
            var v = jQuery(form_ids).validate({
                ignore: '',
                rules: {
                    'invoice_ids[]': {
                        required: true,
                    },
                },
                invalidHandler: function(event, validator) {
                    $noty = new Noty({
                        type: 'error',
                        layout: 'topRight',
                        text: 'Please select atleast one activity',
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 1000);
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $('#submit').button('loading');
                    $.ajax({
                            url: laravel_routes['getActivityEncryptionKey'],
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            // console.log(res.success);
                            if (!res.success) {
                                $('#submit').button('reset');
                                $noty = new Noty({
                                    type: 'error',
                                    layout: 'topRight',
                                    text: res.error,
                                }).show();
                                setTimeout(function() {
                                    $noty.close();
                                }, 5000);
                            } else {
                                $location.path('/rsa-case-pkg/approved-activity/invoice/preview/' + res.encryption_key);
                                $scope.$apply();
                            }
                        })
                        .fail(function(xhr) {
                            $('#submit').button('reset');
                            $noty = new Noty({
                                type: 'error',
                                layout: 'topRight',
                                text: 'Something went wrong at server',
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 5000);
                        });
                },
            });

            $rootScope.loading = false;
        });
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('approvedActivityInvoicePreview', {
    templateUrl: activity_approved_invoice_preview_template_url,
    controller: function($http, $location, $window, HelperService, $scope, $routeParams, $rootScope, $location) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        get_invoice_preview_data_url = typeof($routeParams.encryption_key) == 'undefined' ? activity_approved_invoice_preview_data_url + '/' : activity_approved_invoice_preview_data_url + '/' + $routeParams.encryption_key;
        $http.get(
            get_invoice_preview_data_url
        ).then(function(response) {
            // console.log(response);
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
                $location.path('/rsa-case-pkg/approved-activity/list');
                $scope.$apply();
                return;
            }
            self.asp = response.data.asp;
            self.activities = response.data.activities;
            self.invoice_amount = response.data.invoice_amount;
            self.invoice_amount_in_word = response.data.invoice_amount_in_word;
            self.inv_no = response.data.inv_no;
            self.inv_date = response.data.inv_date;
            self.signature_attachment = response.data.signature_attachment;

            $('.date-picker').datepicker({
                format: 'dd-mm-yyyy',
                autoclose: true,
            });

            $rootScope.loading = false;
        });

        setTimeout(function() {
            $('#invoice-preview-table').DataTable({
                "bLengthChange": false,
                "paginate": false,
                "oLanguage": { "sZeroRecords": "", "sEmptyTable": "" },
            });
        }, 10);

        $('.viewData-toggle--inner.noToggle .viewData-threeColumn--wrapper').slideDown();
        $('.viewData-toggle--btn').click(function() {
            $(this).toggleClass('viewData-toggle--btn_reverse');
            $('.viewData-toggle--inner .viewData-threeColumn--wrapper').slideToggle();
        });

        var form_id = form_ids = '#invoice-create-form';
        var v = jQuery(form_ids).validate({
            ignore: "",
            rules: {
                invoice_no: {
                    required: true,
                },
                inv_date: {
                    required: true,
                },
            },
            messages: {
                invoice_no: {
                    required: 'Invoice number is required',
                },
                inv_date: {
                    required: 'Invoice date is required',
                },
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['generateInvoice'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        // console.log(res.success);
                        if (!res.success) {
                            $('#submit').button('reset');
                            $noty = new Noty({
                                type: 'error',
                                layout: 'topRight',
                                text: res.error,
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 5000);
                        } else {
                            $location.path('/rsa-case-pkg/approved-activity/list');
                            $scope.$apply();
                        }
                    })
                    .fail(function(xhr) {
                        $('#submit').button('reset');
                        $noty = new Noty({
                            type: 'error',
                            layout: 'topRight',
                            text: 'Something went wrong at server',
                        }).show();
                        setTimeout(function() {
                            $noty.close();
                        }, 5000);
                    });
            },
        });

    }
});