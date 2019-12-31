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
                // { data: 'activity_number', name: 'activities.number', searchable: true },
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
    templateUrl: asp_activity_deferred_update_details_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {

        $form_data_url = typeof($routeParams.id) == 'undefined' ? get_deferred_activity_form_data_url : get_deferred_activity_form_data_url + '/' + $routeParams.id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;

        var update_attach_other_id = [];
        var update_attach_km_map_id = [];

        $http.get(
            $form_data_url
        ).then(function(response) {
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
                }, 1000);
                $location.path('/rsa-case-pkg/deferred-activity/list')
                $scope.$apply()
                return;
            }

            self.service_types_list = response.data.service_types;
            self.for_deffer_activity = response.data.for_deffer_activity;
            self.activity = response.data.activity;
            self.cc_collected_charges = parseInt(response.data.cc_collected_charges);
            self.cc_not_collected = parseInt(response.data.cc_other_charge);
            self.cc_actual_km = parseInt(response.data.cc_km_travelled);
            self.asp_other_charge = parseInt(response.data.asp_other_charge);
            self.asp_collected_charges = parseInt(response.data.asp_collected_charges);
            self.asp_km_travelled = parseInt(response.data.asp_km_travelled);
            self.service_type_id = response.data.activity.service_type_id;
            self.range_limit = response.data.range_limit;
            self.km_attachment = response.data.km_attachment;
            self.other_attachment = response.data.other_attachment;

            self.kmTravelledHideShow();
            self.otherChargeHideShow();
            $rootScope.loading = false;
        });

        self.closeOtherAttach = function(index, other_attach_id) {
            if (other_attach_id) {
                update_attach_other_id.push(other_attach_id);
                $('#update_attach_other_id').val(JSON.stringify(update_attach_other_id));
            }
            self.other_attachment.splice(index, 1);
        }

        self.closeKmMapAttach = function(index, km_attach_id) {
            if (km_attach_id) {
                update_attach_km_map_id.push(km_attach_id);
                $('#update_attach_km_map_id').val(JSON.stringify(update_attach_km_map_id));
            }
            self.km_attachment.splice(index, 1);
        }

        self.kmTravelledHideShow = function() {
            var km_travelled_entered = parseInt(self.asp_km_travelled);
            var mis_km = parseInt(self.cc_actual_km);
            var range_limit = self.range_limit;

            if ($.isNumeric(km_travelled_entered)) {
                if (km_travelled_entered > range_limit || range_limit == "") {
                    var allowed_variation = 0.5;
                    var mis_percentage = mis_km * allowed_variation / 100;
                    if (km_travelled_entered > mis_km) {
                        var per = km_travelled_entered - mis_km;
                    }
                    var actual_val = Math.round(per - mis_percentage);
                    if (km_travelled_entered) {
                        if (km_travelled_entered > mis_km) {
                            if (actual_val >= 1) {
                                $(".map_attachment").show();
                                $(".for_differ_km").val(1);
                            } else {
                                $(".map_attachment").hide();
                                $(".for_differ_km").val(0);
                            }
                        } else {
                            $(".map_attachment").hide();
                            $(".for_differ_km").val(0);
                        }

                    } else {
                        $(".map_attachment").hide();
                        $(".for_differ_km").val(0);
                    }
                } else {
                    $(".map_attachment").hide();
                    $(".for_differ_km").val(0);
                }
            } else {
                $(".km_travel").val("");
            }
        }

        $('body').on('focusout', '.km_travel', function() {
            self.kmTravelledHideShow();
        });

        self.otherChargeHideShow = function() {
            var other_charge_entered = parseInt(self.asp_other_charge);
            var other_charge = parseInt(self.cc_not_collected);
            if ($.isNumeric(other_charge_entered)) {
                if (other_charge_entered) {
                    if (other_charge_entered > other_charge) {
                        $(".other_attachment").show();
                        $(".remarks_notcollected").show();
                        $(".for_differ_other").val(1);
                    } else {
                        $(".other_attachment").hide();
                        $(".remarks_notcollected").hide();
                        $(".for_differ_other").val(0);
                    }
                } else {
                    $(".other_attachment").hide();
                    $(".remarks_notcollected").hide();
                    $(".for_differ_other").val(0);
                }
            } else {
                $(".other_attachment").hide();
                $(".remarks_notcollected").hide();
                $(".other_charge").val("");
            }
        }

        $('body').on('focusout', '.other_charge', function() {
            self.otherChargeHideShow();
        });


        $('body').on('focusout', '.asp_collected_charges', function() {
            var asp_collected_charges = parseInt(self.asp_collected_charges);
            if (!$.isNumeric(asp_collected_charges)) {
                $(".asp_collected_charges").val("");
            }
        });

        $.validator.addMethod("check_other_attach", function(number, element) {
            var other_attached = $(".close_other").attr('id');
            var other_attaching_now = $(".other_attachment_data").val();
            if (other_attached) return true;
            else if (other_attaching_now) return true;
            else return false;
            return true;
        }, 'Please attach other Attachment');

        $.validator.addMethod("check_map_attach", function(number, element) {
            var map_attached = $(".close_map").attr('id');
            var map_attaching_now = $(".map_attachment_data").val();
            if (map_attached) return true;
            else if (map_attaching_now) return true;
            else return false;
            return true;
        }, 'Please attach google map screenshot');


        //Jquery Validation
        var form_id = '#activity-deferred-form';
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
                    // required: true,
                    check_map_attach: true,
                    extension: "jpg|jpeg|png|gif"
                },
                'other_attachment[]': {
                    // required: true,
                    check_other_attach: true,
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
                    message: 'Do you want to save activity details?',
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
                            $('#submit').button('loading');
                            $.ajax({
                                    url: laravel_routes['updateActivity'],
                                    method: "POST",
                                    data: formData,
                                    processData: false,
                                    contentType: false,
                                })
                                .done(function(res) {
                                    // console.log(res.errors);
                                    if (!res.success) {
                                        $('#submit').button('reset');
                                        var errors = '';
                                        for (var i in res.errors) {
                                            errors += '<li>' + res.errors[i] + '</li>';
                                        }
                                        // console.log(errors);
                                        new Noty({
                                            type: 'error',
                                            layout: 'topRight',
                                            text: errors
                                        }).show();

                                    } else {
                                        new Noty({
                                            type: 'success',
                                            layout: 'topRight',
                                            text: 'Activity informations saved successfully',
                                        }).show();

                                        $location.path('/rsa-case-pkg/deferred-activity/list');
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