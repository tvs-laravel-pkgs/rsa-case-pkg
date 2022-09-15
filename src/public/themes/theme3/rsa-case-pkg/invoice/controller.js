app.component('invoiceList', {
    templateUrl: invoice_list_template_url,
    controller: function($http, $window, HelperService, $scope, $rootScope, $routeParams) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('asp-invoices')) {
            window.location = "#!/page-permission-denied";
            return false;
        }

        self.filter_img_url = filter_img_url;
        if (typeof($routeParams.type_id) == 'undefined') {
            $location.path('/page-not-found');
            $scope.$apply();
            return;
        }

        if ($routeParams.type_id != 1 && $routeParams.type_id != 2 && $routeParams.type_id != 3) {
            $location.path('/page-not-found');
            $scope.$apply();
            return;
        }
        self.type_id = $routeParams.type_id;
        self.export_invoices_url = export_invoices;
        self.csrf = token;
        self.canExport = canExport;
        $http.get(
            invoice_filter_url + '/' + $routeParams.type_id
        ).then(function(response) {
            self.extras = response.data.extras;
            if (self.type_id != 3 && self.canExport) {
                var col1 = [
                    { data: 'action', searchable: false },
                ];
            } else {
                var col1 = [];
            }

            var col2 = [
                { data: 'invoice_no', name: 'invoice_no', searchable: true },
                { data: 'invoice_date', searchable: false },
                { data: 'asp_code', name: 'asps.asp_code', searchable: true },
                { data: 'workshop_name', name: 'asps.workshop_name', searchable: true },
                { data: 'no_of_activities', searchable: false },
                { data: 'payment_status', name: 'invoice_statuses.name', searchable: true },
                { data: 'invoice_amount', searchable: false },
            ];

            var cols = $.merge(col1, col2);

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
                            d.type_id = self.type_id;
                        }
                    },
                    infoCallback: function(settings, start, end, max, total, pre) {
                        $('.count').html(total + ' / ' + max + ' listings');
                        if ($('#submit').length > 0) {
                            if (!total) {
                                $('#submit').hide();
                            } else {
                                $('#submit').show();
                            }
                        }
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

            $('#select_all_checkbox').click(function() {
                if ($(this).prop("checked")) {
                    $(".child_select_all").prop("checked", true);
                } else {
                    $(".child_select_all").prop("checked", false);
                }
            });

            var form_id = '#invoice_export';
            var v = jQuery(form_id).validate({
                rules: {
                    'invoice_ids[]': {
                        required: true,
                    },
                },
                messages: {
                    // 'invoice_ids[]': {
                    //     required: "Please Select Invoice",
                    // },
                },
                errorPlacement: function(error, element) {
                    $noty = new Noty({
                        type: 'error',
                        layout: 'topRight',
                        text: 'Please select atleast one invoice',
                        animation: {
                            speed: 500 // unavailable - no need
                        },
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 1000);
                },
                submitHandler: function(form) {
                    $('#invoice_export').submit();
                }
            });

            //CANCEL iNVOICE
            $scope.cancelInvoice = () => {
                invoice_ids = [];
                $('input[type="checkbox"]').each(function() {
                    if ($(this).is(':checked') && this.name != 'check_all') {
                        invoice_ids[invoice_ids.length] = this.value;
                    }
                });
                console.log(invoice_ids);
                if (invoice_ids == undefined || invoice_ids == null) {
                    custom_noty('error', 'Please select atleast one invoice');
                }
                if (invoice_ids !== undefined || invoice_ids !== null) {
                    $http({
                        url: laravel_routes['cancelInvoice'],
                        method: 'POST',
                        data: { 'invoice_ids': invoice_ids }
                    }).then(function(res) {
                        // console.log(res);
                        if (!res.data.success) {
                            var errors = '';
                            if (res.data.errors) {
                                for (var i in res.data.errors) {
                                    errors += '<li>' + res.data.errors[i] + '</li>';
                                }
                            }
                            if (res.data.error) {
                                errors += '<li>' + res.data.error + '</li>';
                            }
                            custom_noty('error', errors);
                        } else {
                            custom_noty('success', 'Invoice Cancelled Successfully');
                            $('#invoice_table').DataTable().ajax.reload();
                        }
                    });
                }
            }


            // END OF CANCEL INVOICE

            $rootScope.loading = false;
        });
    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('invoiceView', {
    templateUrl: invoice_view_template_url,
    controller: function($http, $location, $window, HelperService, $scope, $routeParams, $rootScope, $location) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.filter_img_url = filter_img_url;
        self.refresh_img_url = refresh_img_url;
        self.type_id = $routeParams.type_id;
        self.invoice_pdf_generate_url = invoice_pdf_generate_url;
        self.canViewPaymentInfo = canViewPaymentInfo;

        if (typeof($routeParams.type_id) == 'undefined') {
            $location.path('/page-not-found');
            $scope.$apply();
            return;
        }

        if ($routeParams.type_id != 1 && $routeParams.type_id != 2 && $routeParams.type_id != 3) {
            $location.path('/page-not-found');
            $scope.$apply();
            return;
        }

        get_view_data_url = typeof($routeParams.id) == 'undefined' ? invoice_view_data_url : invoice_view_data_url + '/' + $routeParams.id + '/' + $routeParams.type_id;

        $http.get(
            get_view_data_url
        ).then(function(response) {
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
                $location.path('/rsa-case-pkg/invoice/list/' + $routeParams.type_id);
                // $scope.$apply();
                return;
            }

            self.period = response.data.period;
            self.title = response.data.title;
            self.asp = response.data.asp;
            self.rsa_address = response.data.rsa_address;
            self.activities = response.data.activities;
            self.invoice_amount = response.data.invoice_amount;
            self.invoice_amount_in_word = response.data.invoice_amount_in_word;
            self.signature_attachment = response.data.signature_attachment;
            self.signature_attachment_path = response.data.signature_attachment_path;
            self.invoice_attachment_file = response.data.invoice_attachment_file;
            self.invoice = response.data.invoice;
            self.inv_no = response.data.inv_no;
            self.invoice_availability = response.data.invoice_availability;
            self.invoice_vouchers_amount = response.data.invoice_vouchers_amount;
            self.invoice_vouchers = response.data.invoice_vouchers;
            // self.new_company_address = response.data.new_company_address;
            self.auto_assist_company_address = response.data.auto_assist_company_address;
            self.automobile_company_address = response.data.automobile_company_address;
            self.ki_company_address = response.data.ki_company_address;

            if (self.asp.tax_calculation_method == '1') {
                self.asp.tax_calculation_method = true;
            } else {
                self.asp.tax_calculation_method = false;
            }

            setTimeout(function() {
                var dataTable = $('#aspLogin-table').DataTable({
                    "bLengthChange": false,
                    "bRetrieve": true,
                    "paginate": false,
                    "oLanguage": { "sZeroRecords": "", "sEmptyTable": "" },
                });
            }, 1000);

            setTimeout(function() {
                if (self.canViewPaymentInfo) {
                    var paymentDataTable = $('#aspPaymentInfo-table').DataTable({
                        "bLengthChange": false,
                        "bRetrieve": true,
                        "paginate": false,
                        "oLanguage": { "sZeroRecords": "", "sEmptyTable": "" },
                    });
                }
            }, 1500);

            if (self.hasPermission('view-invoice-payment-info')) {
                setTimeout(function() {
                    $scope.getPaymenyInfo();
                }, 2000);
            }

            $('.viewData-toggle--inner.noToggle .viewData-threeColumn--wrapper').slideDown();
            $('.viewData-toggle--btn').click(function() {
                $(this).toggleClass('viewData-toggle--btn_reverse');
                $('.viewData-toggle--inner .viewData-threeColumn--wrapper').slideToggle();
            });

            $scope.isSelf = function(asp) {
                if (asp.has_gst && !asp.is_auto_invoice) {
                    return true;
                } else {
                    return false;
                }
            };

            $scope.isSystem = function(asp) {
                if (!asp.has_gst || (asp.has_gst && asp.is_auto_invoice)) {
                    return true;
                } else {
                    return false;
                }
            };

            $scope.getPaymenyInfo = function() {
                if ($(".loader-type-2").hasClass("loader-hide")) {
                    $(".loader-type-2").removeClass("loader-hide");
                }
                $http.get(
                    get_invoice_payment_info_url + '/' + $routeParams.id
                ).then(function(response) {
                    console.log(response);
                    $(".loader-type-2").addClass("loader-hide");
                    if (!response.data.success) {
                        $noty = new Noty({
                            type: 'error',
                            layout: 'topRight',
                            text: response.data.error,
                            animation: {
                                speed: 500 // unavailable - no need
                            },

                        }).show();
                        setTimeout(function() {
                            $noty.close();
                        }, 2000);
                    } else {
                        self.invoice_vouchers_amount = response.data.data.invoice_vouchers_amount;
                        self.invoice_vouchers = response.data.data.invoice_vouchers;
                        $scope.$apply();
                    }
                });
            }

            $rootScope.loading = false;
        });



    }
});