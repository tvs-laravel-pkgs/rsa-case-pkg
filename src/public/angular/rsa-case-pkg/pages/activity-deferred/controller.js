app.component('deferredActivityList', {
    templateUrl: activity_deferred_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;

        self.filter_img_url = filter_img_url;
        $http.get(
            activity_deferred_filter_url
        ).then(function(response) {
            self.extras = response.data.extras;

            var cols = [
                { data: 'action', searchable: false },
                { data: 'case_date', searchable: false },
                { data: 'number', name: 'cases.number', searchable: true },
                // { data: 'asp_code', name: 'asps.asp_code', searchable: true },
                { data: 'crm_activity_id', searchable: false },
                { data: 'activity_number', name: 'activities.number', searchable: true },
                { data: 'sub_service', name: 'service_types.name', searchable: true },
                { data: 'finance_status', name: 'activity_finance_statuses.name', searchable: true },
                // { data: 'asp_status', name: 'activity_asp_statuses.name', searchable: true },
                { data: 'status', name: 'activity_portal_statuses.name', searchable: true },
                { data: 'activity_status', name: 'activity_statuses.name', searchable: true },
                { data: 'client', name: 'clients.name', searchable: true },
                { data: 'call_center', name: 'call_centers.name', searchable: true },
            ];

            var activities_deferred_dt_config = JSON.parse(JSON.stringify(dt_config));

            $('#activities_deferred_table').DataTable(
                $.extend(activities_deferred_dt_config, {
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
                        url: activity_deferred_get_list_url,
                        data: function(d) {
                            d.ticket_date = $('#ticket_date').val();
                            d.call_center_id = $('#call_center_id').val();
                            d.case_number = $('#case_number').val();
                            // d.asp_code = $('#asp_code').val();
                            d.service_type_id = $('#service_type_id').val();
                            d.finance_status_id = $('#finance_status_id').val();
                            d.status_id = $('#status_id').val();
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

            var dataTable = $('#activities_deferred_table').dataTable();

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
                $('#activities_deferred_table').DataTable().ajax.reload();
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
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('deferredActivityUpdate', {
    templateUrl: asp_new_ticket_update_details_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {

        $form_data_url = typeof($routeParams.id) == 'undefined' ? get_ticket_form_data_url : get_ticket_form_data_url + '/' + $routeParams.id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;

        $http.get(
            $form_data_url
        ).then(function(response) {
            console.log(response.data);
            self.service_types_list = response.data.service_types;
            self.for_deffer_ticket = response.data.for_deffer_ticket;
            self.actual_km = response.data.mis_info.total_km;
            self.mis_id = response.data.mis_info.id;
            self.unpaid_amount = response.data.mis_info.unpaid_amount;
            self.service_type_id = response.data.mis_info.service_type_id;
            self.range_limit = response.data.range_limit;
            $rootScope.loading = false;
            // console.log(self.for_deffer_ticket);
            if (self.for_deffer_ticket != '') {
                $('.resolve_comment').show();
            } else {
                $('.resolve_comment').hide();
            }

        });


        $('body').on('focusout', '.km_travel', function() {
            var entry_val = parseInt($(this).val());
            var mis_km = parseInt($(this).parents(".asp_for_find").find(".actual_km").val());
            var range_limit = $(".service_range_limit").val();

            var km_travel = parseInt($(this).parents(".asp_for_find").find(".km_travel").val());

            if ($.isNumeric(km_travel)) {

                if (entry_val > range_limit || range_limit == "") {
                    var allowed_variation = 0.5;
                    var mis_percentage = mis_km * allowed_variation / 100;
                    if (entry_val > mis_km) { var per = entry_val - mis_km; }
                    var actual_val = Math.round(per - mis_percentage);

                    if (entry_val) {
                        if (entry_val > mis_km) {

                            if (actual_val >= 1) {
                                $(this).parents(".asp_for_find").find(".map_attachment").show();
                                $(this).parents(".asp_for_find").find(".for_differ_km").val(1);
                            } else {
                                $(this).parents(".asp_for_find").find(".map_attachment").hide();
                                $(this).parents(".asp_for_find").find(".for_differ_km").val(0);
                            }
                        } else {
                            $(this).parents(".asp_for_find").find(".map_attachment").hide();
                            $(this).parents(".asp_for_find").find(".for_differ_km").val(0);
                        }

                    } else {
                        $(this).parents(".asp_for_find").find(".map_attachment").hide();
                        $(this).parents(".asp_for_find").find(".for_differ_km").val(0);
                    }
                    // $("#"+ids).after(html);
                } else {
                    $(this).parents(".asp_for_find").find(".map_attachment").hide();
                    $(this).parents(".asp_for_find").find(".for_differ_km").val(0);
                }
            } else {
                $(this).parents(".asp_for_find").find(".km_travel").val("");
            }

        });


        $('body').on('focusout', '.other_charge', function() {
            var entry_val = parseInt($(this).val());
            var other_not_collected = parseInt($(this).parents(".asp_for_find").find(".unpaid_amount").val());

            var other_charge = parseInt($(this).parents(".asp_for_find").find(".other_charge").val());

            if ($.isNumeric(other_charge)) {

                if (entry_val) {
                    if (entry_val > other_not_collected) {

                        $(this).parents(".asp_for_find").find(".other_attachment").show();
                        $(this).parents(".asp_for_find").find(".remarks_notcollected").show();
                        $(this).parents(".asp_for_find").find(".for_differ_other").val(1);
                    } else {
                        $(this).parents(".asp_for_find").find(".other_attachment").hide();
                        $(this).parents(".asp_for_find").find(".remarks_notcollected").hide();
                        $(this).parents(".asp_for_find").find(".for_differ_other").val(0);
                    }

                } else {
                    $(this).parents(".asp_for_find").find(".other_attachment").hide();
                    $(this).parents(".asp_for_find").find(".remarks_notcollected").hide();
                    $(this).parents(".asp_for_find").find(".for_differ_other").val(0);
                }
            }
            //$("#"+ids).after(html);
            else {
                $(this).parents(".asp_for_find").find(".other_attachment").hide();
                $(this).parents(".asp_for_find").find(".remarks_notcollected").hide();
                $(this).parents(".asp_for_find").find(".other_charge").val("");
            }

        });


        $('body').on('focusout', '.asp_collected_charges', function() {
            var asp_collected_charges = parseInt($(this).parents(".asp_for_find").find(".asp_collected_charges").val());
            if (!$.isNumeric(asp_collected_charges)) {
                $(this).parents(".asp_for_find").find(".asp_collected_charges").val("");
            }
        });

        //Jquery Validation
        var form_id = '#new-tickect-form';
        var v = jQuery(form_id).validate({
            invalidHandler: function(event, validator) {
                var errors = validator.numberOfInvalids();
                $(".alert-danger").show();
                var errors = validator.numberOfInvalids();
                if (errors) {
                    var message = errors == 1 ?
                        'Please correct the following error:\n' :
                        'Please correct the following ' + errors + ' errors.\n';
                    var errors = "";
                    if (validator.errorList.length > 0) {
                        for (x = 0; x < validator.errorList.length; x++) {
                            errors += "\n\u25CF " + validator.errorList[x].message;
                        }
                    }
                    $(".alert-danger").html(message + errors);
                }
                validator.focusInvalid();

                $("html, body").animate({ scrollTop: 0 });
            },
            errorContainer: '.grouped-error',
            rules: {
                'km_travelled': {
                    required: true,
                    number: true
                },
                'other_charge': {
                    number: true
                },
                'remarks_not_collected': {
                    required: true
                },
                'asp_collected_charges': {
                    number: true
                },
                'map_attachment[]': {
                    required: true,
                    extension: "jpg|jpeg|png|gif"
                },
                'other_attachment[]': {
                    required: true,
                    extension: "jpg|jpeg|png|gif"
                },
            },
            messages: {
                'km_travelled': {
                    required: "Please Enter Kilo Meter Value",
                },
                'remarks_not_collected': {
                    required: "Please Enter Remark Comments",
                },
                'map_attachment[]': {
                    required: 'Please attach google map screenshot',
                },
                'asp_collected_charges': {
                    number: 'Please enter number value',
                },
                'other_attachment[]': {
                    required: 'Please attach other Attachment',
                },
            },
            errorPlacement: function(error, element) {
                if (element.attr("type") == "checkbox") {
                    error.insertBefore($(element).parents('.checkboxList'));
                } else {
                    error.insertAfter($(element));
                }
            },
            submitHandler: function(form) {
                bootbox.confirm({
                    message: 'Do you want to save ticket details?',
                    className: 'action-confirm-modal',
                    buttons: {
                        confirm: {
                            label: 'Yes',
                            className: 'btn-success'
                        },
                        cancel: {
                            label: 'No',
                            className: 'btn-danger'
                        }
                    },
                    callback: function(result) {
                        if (result) {

                            let formData = new FormData($(form_id)[0]);
                            //$('#submit').button('loading');
                            $.ajax({
                                    url: laravel_routes['aspSaveTicket'],
                                    method: "POST",
                                    data: formData,
                                    processData: false,
                                    contentType: false,
                                })
                                .done(function(res) {
                                    console.log(res.errors);
                                    if (!res.success) {
                                        $('#submit').button('reset');
                                        var errors = '';
                                        for (var i in res.errors) {
                                            errors += '<li>' + res.errors[i] + '</li>';
                                        }
                                        console.log(errors);
                                        new Noty({
                                            type: 'error',
                                            layout: 'topRight',
                                            text: errors
                                        }).show();

                                    } else {
                                        new Noty({
                                            type: 'success',
                                            layout: 'topRight',
                                            text: 'Ticket informations saved successfully',
                                        }).show();

                                        $location.path('/asp/new-ticket');
                                        $scope.$apply();
                                    }
                                })
                                .fail(function(xhr) {
                                    $('#submit').button('reset');
                                    new Noty({
                                        type: 'error',
                                        layout: 'topRight',
                                        text: 'Something went wrong at server',
                                    }).show();
                                });

                        }
                    }
                });
            }
        });

    }
});