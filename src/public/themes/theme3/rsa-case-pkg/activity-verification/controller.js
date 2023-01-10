app.component('activityVerificationList', {
    templateUrl: activity_verification_list_template_url,
    controller: function($http, $window, HelperService, $scope, $rootScope, $route, $location, $mdSelect) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('activity-verification')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.filter_img_url = filter_img_url;
        $http.get(
            activity_status_filter_url
        ).then(function(response) {
            self.extras = response.data.extras;
            $(".for-below40").show();
            $(".for-above40").hide();

            var cols1 = [
                { data: 'action', searchable: false },
                { data: 'case_date', searchable: false },
                { data: 'number', name: 'cases.number', searchable: true },
                { data: 'vehicle_registration_number', name: 'cases.vehicle_registration_number', searchable: true },
                { data: 'asp', name: 'asp', searchable: true },
                // { data: 'asp_code', name: 'asps.asp_code', searchable: true },
                { data: 'crm_activity_id', searchable: false },
                // { data: 'source', name: 'configs.name', searchable: true },
                { data: 'boKmTravelled', searchable: false },
                // { data: 'activity_number', name: 'activities.number', searchable: true },
                { data: 'sub_service', name: 'service_types.name', searchable: true },
                { data: 'finance_status', name: 'activity_finance_statuses.name', searchable: true },
                // { data: 'asp_status', name: 'activity_asp_statuses.name', searchable: true },
                { data: 'status', name: 'activity_portal_statuses.name', searchable: true },
                { data: 'activity_status', name: 'activity_statuses.name', searchable: true },
                { data: 'client', name: 'clients.name', searchable: true },
                // { data: 'call_center', name: 'call_centers.name', searchable: true },
                { data: 'boPayoutAmount', searchable: false },
            ];

            var activities_verification_below_40_dt_config = JSON.parse(JSON.stringify(dt_config));

            $('#below40-table').DataTable(
                $.extend(activities_verification_below_40_dt_config, {
                    columns: cols1,
                    ordering: true,
                    "columnDefs": [{
                        "orderable": false,
                        "targets": [0, 5, 6, 12]
                    }],
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
                            $('.for-below40 .filterTable').val(state_save_val.search.search);
                        }
                        return JSON.parse(localStorage.getItem('SIDataTables_' + settings.sInstance));
                    },
                    ajax: {
                        url: activity_verification_bulk_get_list_url,
                        data: function(d) {
                            d.ticket_date = $('.for-below40 #ticket_date').val();
                            d.call_center_id = $('.for-below40 #call_center_id').val();
                            d.case_number = $('.for-below40 #case_number').val();
                            d.asp_code = $('.for-below40 #asp_code').val();
                            d.service_type_id = $('.for-below40 #service_type_id').val();
                            d.finance_status_id = $('.for-below40 #finance_status_id').val();
                            d.status_id = $('.for-below40 #status_id').val();
                            d.activity_status_id = $('.for-below40 #activity_status_id').val();
                            d.client_id = $('.for-below40 #client_id').val();
                        }
                    },
                    infoCallback: function(settings, start, end, max, total, pre) {
                        $('.below40_count').html(total + ' / ' + max + ' listings')
                        if (!total) {
                            $('#submit').hide();
                        } else {
                            $('#submit').show();
                        }
                    },
                    initComplete: function() {},
                }));
            $('.dataTables_length select').select2();

            var belowDataTable = $('#below40-table').dataTable();

            $(".for-below40 .filterTable").keyup(function() {
                belowDataTable.fnFilter(this.value);
            });

            $('.for-below40 #ticket_date').on('change', function() {
                belowDataTable.fnFilter();
            });

            $('.for-below40 #case_number, .for-below40 #asp_code').on('keyup', function() {
                belowDataTable.fnFilter();
            });

            $scope.changeCommonFilterBelow = function(val, id) {
                $('.for-below40 #' + id).val(val);
                belowDataTable.fnFilter();
            };

            $scope.resetFilterBelow40 = function() {
                self.ticket_filter_below40 = [];
                $('.for-below40 #call_center_id').val('');
                $('.for-below40 #service_type_id').val('');
                $('.for-below40 #finance_status_id').val('');
                $('.for-below40 #status_id').val('');
                $('.for-below40 #activity_status_id').val('');
                $('.for-below40 #client_id').val('');

                setTimeout(function() {
                    belowDataTable.fnFilter();
                    $('#below40-table').DataTable().ajax.reload();
                }, 1000);
            };

            $scope.belowRefresh = function() {
                $('#below40-table').DataTable().ajax.reload();
            };

            $('.for-below40 .filterToggle').click(function() {
                $('.for-below40 #filterticket').toggleClass('open');
            });

            var form_id = form_ids = '#bulk_verification';
            var v = jQuery(form_ids).validate({
                ignore: '',
                rules: {
                    // 'invoice_ids[]': {
                    //     required: true,// },
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $('#submit').button('loading');
                    if ($(".loader-type-2").hasClass("loader-hide")) {
                        $(".loader-type-2").removeClass("loader-hide");
                    }
                    $.ajax({
                            url: laravel_routes['bulkApproveActivity'],
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            // console.log(res);
                            $(".loader-type-2").addClass("loader-hide");
                            $('#submit').button('reset');
                            if (!res.success) {
                                custom_noty('error', res.error);
                            } else {
                                custom_noty('success', res.message);
                                $('#below40-table').DataTable().ajax.reload();
                            }
                        })
                        .fail(function(xhr) {
                            $(".loader-type-2").addClass("loader-hide");
                            $('#submit').button('reset');
                            custom_noty('error', "Something went wrong at server");
                        });
                },
            });


            var cols2 = [
                { data: 'case_date', searchable: false },
                { data: 'number', name: 'cases.number', searchable: true },
                { data: 'vehicle_registration_number', name: 'cases.vehicle_registration_number', searchable: true },
                { data: 'asp', name: 'asp', searchable: true },
                // { data: 'asp_code', name: 'asps.asp_code', searchable: true },
                { data: 'crm_activity_id', searchable: false },
                // { data: 'source', name: 'configs.name', searchable: true },
                { data: 'boKmTravelled', searchable: false },
                // { data: 'activity_number', name: 'activities.number', searchable: true },
                { data: 'sub_service', name: 'service_types.name', searchable: true },
                { data: 'finance_status', name: 'activity_finance_statuses.name', searchable: true },
                // { data: 'asp_status', name: 'activity_asp_statuses.name', searchable: true },
                { data: 'status', name: 'activity_portal_statuses.name', searchable: true },
                { data: 'activity_status', name: 'activity_statuses.name', searchable: true },
                { data: 'client', name: 'clients.name', searchable: true },
                // { data: 'call_center', name: 'call_centers.name', searchable: true },
                { data: 'boPayoutAmount', searchable: false },
            ];

            var activities_verification_above_40_dt_config = JSON.parse(JSON.stringify(dt_config));

            $('#above40-table').DataTable(
                $.extend(activities_verification_above_40_dt_config, {
                    columns: cols2,
                    ordering: true,
                    "columnDefs": [{
                        "orderable": false,
                        "targets": [4, 5, 11]
                    }],
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
                            $('.for-above40 .filterTable').val(state_save_val.search.search);
                        }
                        return JSON.parse(localStorage.getItem('SIDataTables_' + settings.sInstance));
                    },
                    ajax: {
                        url: activity_verification_individual_get_list_url,
                        data: function(d) {
                            d.ticket_date = $('.for-above40 #ticket_date').val();
                            d.call_center_id = $('.for-above40 #call_center_id').val();
                            d.case_number = $('.for-above40 #case_number').val();
                            d.asp_code = $('.for-above40 #asp_code').val();
                            d.service_type_id = $('.for-above40 #service_type_id').val();
                            d.finance_status_id = $('.for-above40 #finance_status_id').val();
                            d.status_id = $('.for-above40 #status_id').val();
                            d.activity_status_id = $('.for-above40 #activity_status_id').val();
                            d.client_id = $('.for-above40 #client_id').val();
                        }
                    },
                    infoCallback: function(settings, start, end, max, total, pre) {
                        $('.above40_count').html(total + ' / ' + max + ' listings')
                    },
                    initComplete: function() {},
                }));
            $('.dataTables_length select').select2();

            var aboveDataTable = $('#above40-table').dataTable();

            $(".for-above40 .filterTable").keyup(function() {
                aboveDataTable.fnFilter(this.value);
            });

            $('.for-above40 #ticket_date').on('change', function() {
                aboveDataTable.fnFilter();
            });

            $('.for-above40 #case_number, .for-above40 #asp_code').on('keyup', function() {
                aboveDataTable.fnFilter();
            });

            $scope.changeCommonFilterAbove = function(val, id) {
                $('.for-above40 #' + id).val(val);
                aboveDataTable.fnFilter();
            };

            $scope.resetFilterAbove40 = function() {
                self.ticket_filter_above40 = [];
                $('.for-above40 #call_center_id').val('');
                $('.for-above40 #service_type_id').val('');
                $('.for-above40 #finance_status_id').val('');
                $('.for-above40 #status_id').val('');
                $('.for-above40 #activity_status_id').val('');
                $('.for-above40 #client_id').val('');

                setTimeout(function() {
                    aboveDataTable.fnFilter();
                    $('#above40-table').DataTable().ajax.reload();
                }, 1000);
            };

            $scope.aboveRefresh = function() {
                $('#above40-table').DataTable().ajax.reload();
            };

            $('.for-above40 .filterToggle').click(function() {
                $('.for-above40 #filterticket').toggleClass('open');
            });

            $('.close-filter, .filter-overlay').click(function() {
                $(this).parents('.filter-wrapper').removeClass('open');
            });

            $('.date-picker').datepicker({
                format: 'dd-mm-yyyy',
                autoclose: true,
            });

            $('.filter-content').bind('click', function(event) {

                if ($('.md-select-menu-container').hasClass('md-active')) {
                    $mdSelect.hide();
                }
            });

            $('#select_all_checkbox').click(function() {
                if ($(this).prop("checked")) {
                    $(".child_select_all").prop("checked", true);
                } else {
                    $(".child_select_all").prop("checked", false);
                }
            });


            $(".for-empty-return").hide();
            $(".below40-tab").click(function() {
                $(".for-below40").show();
                $(".for-above40,.for-empty-return").hide();
            });
            $(".above40-tab").click(function() {
                $(".for-above40").show();
                $(".for-below40,.for-empty-return").hide();
            });
            $(".empty-return-tab").click(function() {
                $(".for-empty-return").show();
                $(".for-below40,.for-above40").hide();
            });
            $scope.tabChange = function(add_id, remove_id) {
                $('#' + add_id + '-table').DataTable().ajax.reload();
                $('#' + add_id).addClass('active in');
                $('#' + remove_id).removeClass('active in');
            }

            $rootScope.loading = false;
        });
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('activityVerificationView', {
    templateUrl: activity_status_view_template_url,
    controller: function($http, $location, $window, HelperService, $scope, $routeParams, $rootScope, $location) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.filter_img_url = filter_img_url;
        self.style_dot_image_url = style_dot_image_url;
        self.csrf = $('#csrf').val();
        get_view_data_url = typeof($routeParams.id) == 'undefined' ? activity_verification_view_data_url + '/' + 2 : activity_verification_view_data_url + '/' + $routeParams.view_type_id + '/view/' + $routeParams.id;
        $http.get(
            get_view_data_url
        ).then(function(response) {
            if (!response.data.success) {
                var errors = '';
                for (var i in response.data.errors) {
                    errors += '<li>' + response.data.errors[i] + '</li>';
                }
                custom_noty('error', errors);
                $location.path('/rsa-case-pkg/activity-verification/list');
                return;
            }
            self.data = response.data.data.activities;
            self.data.view_cc_details = view_cc_details;
            if (view_cc_details == 1) {
                self.data.span_value = 3;
            } else {
                self.data.span_value = 2;
            }
            self.data.style_dot_image_url = style_dot_image_url;
            self.data.style_service_type_image_url = style_service_type_image_url;
            self.data.style_car_image_url = style_car_image_url;
            self.data.style_location_image_url = style_location_image_url;
            self.data.style_profile_image_url = style_profile_image_url;
            self.data.style_phone_image_url = style_car_image_url;
            self.data.style_modal_close_image_url = style_modal_close_image_url;
            self.data.style_question_image_url = style_question_image_url;
            self.data.style_checked_image_url = style_checked_image_url;
            self.data.verification = 1;
            self.data.page_title = "Approval";
            if (self.data.verification == 1 && (self.data.activityApprovalLevel == 1 || self.data.activityApprovalLevel == 3)) {
                $('.waiting_time_entry').show();
                $('.bo_waiting_time').datetimepicker({
                    format: 'HH:mm'
                });
            }
            $rootScope.loading = false;
            self.data.cc_net_amount = self.data.cc_po_amount - self.data.bo_not_collected;
            $scope.differ = function() {
                $http.post(
                    laravel_routes['saveActivityDiffer'], {
                        activity_id: self.data.id,
                        bo_km_travelled: self.data.bo_km_travelled,
                        raw_bo_collected: self.data.raw_bo_collected,
                        raw_bo_not_collected: self.data.raw_bo_not_collected,
                        bo_deduction: self.data.bo_deduction,
                        bo_po_amount: self.data.bo_po_amount,
                        bo_net_amount: self.data.bo_net_amount,
                        bo_amount: self.data.bo_amount,

                    }
                ).then(function(response) {
                    $('.save').button('reset');
                    $("#reject-modal").modal("hide");
                    // console.log(response.data.data);
                    if (!response.data.data.success) {
                        var errors = '';
                        for (var i in response.data.data.errors) {
                            errors += '<li>' + response.data.errors[i] + '</li>';
                        }
                        custom_noty('error', errors);
                        return;
                    }
                    custom_noty('success', response.data.data.message);
                    item.selected = false;
                });
            }
            $('.viewData-toggle--inner.noToggle .viewData-threeColumn--wrapper').slideDown();
            $('#viewData-toggle--btn1').click(function() {
                $(this).toggleClass('viewData-toggle--btn_reverse');
                $('#viewData-threeColumn--wrapper1').slideToggle();
            });
            $('#viewData-toggle--btnasp').click(function() {
                $(this).toggleClass('viewData-toggle--btn_reverse');
                $('#viewData-threeColumn--wrapperasp').slideToggle();
            });
        });
    }

});