app.component('newActivity', {
    templateUrl: asp_new_activity_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;


        var form_id = '#ticket_verify_form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'number': {
                    required: true,
                },
            },
            submitHandler: function(form) {
                console.log(self.user);
                let formData = new FormData($(form_id)[0]);
                //$('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['verifyActivity'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        console.log(res);
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
                            $location.path('/rsa-case-pkg/new-activity/update-details/' + res.activity_id)
                            $scope.$apply()
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
            },
        });
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('newActivityUpdateDetails', {
    templateUrl: asp_new_activity_update_details_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {

        $form_data_url = typeof($routeParams.id) == 'undefined' ? get_activity_form_data_url : get_activity_form_data_url + '/' + $routeParams.id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;

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
                $location.path('/rsa-case-pkg/new-activity')
                $scope.$apply()
                return;
            }

            self.service_types_list = response.data.service_types;
            self.for_deffer_activity = response.data.for_deffer_activity;
            //self.actual_km = response.data.activity.total_km;
            self.activity = response.data.activity;
            self.unpaid_amount = response.data.cc_other_charge;
            self.actual_km = response.data.cc_km_travelled;
            self.collected_charges = response.data.cc_collected_charges;
            //self.data.unpaid_amount = response.data.activity.unpaid_amount;
            self.service_type_id = response.data.activity.service_type_id;
            self.range_limit = response.data.range_limit;
            $rootScope.loading = false;
            if (self.for_deffer_activity) {
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
                            //$('#submit').button('loading');
                            $.ajax({
                                    url: laravel_routes['updateActitvity'],
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
                                            text: 'Activity informations saved successfully',
                                        }).show();

                                        $location.path('/rsa-case-pkg/new-activity');
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