app.component('newActivity', {
    templateUrl: asp_new_activity_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('asp-new-activities')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
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
                // console.log(self.user);
                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                if ($(".loader-type-2").hasClass("loader-hide")) {
                    $(".loader-type-2").removeClass("loader-hide");
                }
                $.ajax({
                        url: laravel_routes['verifyActivity'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        // console.log(res);
                        if (!res.success) {
                            $(".loader-type-2").addClass("loader-hide");
                            $('#submit').button('reset');
                            var errors = '';
                            for (var i in res.errors) {
                                errors += '<li>' + res.errors[i] + '</li>';
                            }
                            custom_noty('error', errors);
                        } else {
                            $location.path('/rsa-case-pkg/new-activity/update-details/' + res.activity_id)
                            $scope.$apply()
                        }
                    })
                    .fail(function(xhr) {
                        $(".loader-type-2").addClass("loader-hide");
                        $('#submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
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
                custom_noty('error', response.data.error);
                $location.path('/rsa-case-pkg/new-activity')
                return;
            }
            self.service_types_list = response.data.service_types;
            self.for_deffer_activity = response.data.for_deffer_activity;
            //self.actual_km = response.data.activity.total_km;
            self.activity = response.data.activity;
            self.towingAttachmentsMandatoryLabel = response.data.towingAttachmentsMandatoryLabel;
            self.towingAttachmentSamplePhoto = 1;
            //TOWING GROUP
            if (self.activity.service_type.service_group_id == 3) {
                self.showTowingAttachment = true;
            } else {
                self.showTowingAttachment = false;
            }
            self.case_details = response.data.case_details;
            self.unpaid_amount = response.data.cc_other_charge;
            self.actual_km = response.data.cc_km_travelled;
            self.collected_charges = response.data.cc_collected_charges;
            //self.data.unpaid_amount = response.data.activity.unpaid_amount;
            self.service_type_id = response.data.activity.service_type_id;
            self.range_limit = response.data.range_limit;
            self.bd_location = response.data.case_details.bd_location;
            self.dropDealer = response.data.dropDealer;
            self.dropLocation = response.data.dropLocation;
            $rootScope.loading = false;
            if (self.for_deffer_activity) {
                $('.resolve_comment').show();
            } else {
                $('.resolve_comment').hide();
            }
        });

        $('body').on('focusout', '.km_travel', function() {
            var entry_val = self.km_travelled;
            var mis_km = self.actual_km;
            var range_limit = self.range_limit;
            var km_travel = self.km_travelled;
            if ($.isNumeric(km_travel)) {
                if (entry_val > range_limit || range_limit == "") {
                    var allowed_variation = 0.5;
                    var mis_percentage_difference = mis_km * allowed_variation / 100;
                    if (entry_val) {
                        if (entry_val > mis_km) {
                            var km_difference = entry_val - mis_km;
                            // var actual_val = Math.round(per - mis_percentage);
                            // if (actual_val >= 1) {

                            if (km_difference > mis_percentage_difference) {
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
                    // $("#"+ids).after(html);
                } else {
                    $(".map_attachment").hide();
                    $(".for_differ_km").val(0);
                }
            } else {
                $(".km_travel").val("");
            }

        });

        $('body').on('focusout', '.other_charge', function() {
            var entry_val = self.other_charge;
            var other_not_collected = self.unpaid_amount;
            var other_charge = self.other_charge;

            if ($.isNumeric(other_charge)) {
                if (entry_val) {
                    //DISABLED
                    // if (entry_val > other_not_collected) {
                    //NEW LOGIC BY CLIENT
                    if (parseFloat(entry_val) >= 31) {
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
        });

        $scope.getServiceTypeDetail = () => {
            if (self.service_type_id) {
                $.ajax({
                        url: getActivityServiceTypeDetail + '/' + self.service_type_id,
                        method: "GET",
                    })
                    .done(function(res) {
                        if (!res.success) {
                            var errors = '';
                            for (var i in res.errors) {
                                errors += '<li>' + res.errors[i] + '</li>';
                            }
                            custom_noty('error', errors);
                            return;
                        } else {
                            //TOWING
                            if (res.serviceType.service_group_id == 3) {
                                self.showTowingAttachment = true;
                            } else {
                                self.showTowingAttachment = false;
                            }
                            $scope.$apply()
                        }
                    })
                    .fail(function(xhr) {
                        custom_noty('error', 'Something went wrong at server');
                        console.log(xhr);
                    });
            }
        }

        $.validator.addMethod('imageFileSize', function(value, element, param) {
            return this.optional(element) || (element.files[0].size <= param)
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
                    number: true,
                },
                'other_charge': {
                    number: true,
                    required: true,
                },
                'remarks_not_collected': {
                    required: true,
                },
                'asp_collected_charges': {
                    number: true,
                    required: true,
                },
                'map_attachment[]': {
                    required: true,
                    extension: "jpg|jpeg|png|gif",
                },
                'other_attachment[]': {
                    required: true,
                    extension: "jpg|jpeg|png|gif|pdf",
                },
                'vehicle_pickup_attachment': {
                    required: function(element) {
                        return self.activity.is_towing_attachments_mandatory === 1 && self.activity.finance_status.po_eligibility_type_id == 340;
                    },
                    imageFileSize: 1048576,
                },
                'vehicle_drop_attachment': {
                    required: function(element) {
                        return self.activity.is_towing_attachments_mandatory === 1 && self.activity.finance_status.po_eligibility_type_id == 340;
                    },
                    imageFileSize: 1048576,
                },
                'inventory_job_sheet_attachment': {
                    required: function(element) {
                        return self.activity.is_towing_attachments_mandatory === 1 && self.activity.finance_status.po_eligibility_type_id == 340;
                    },
                    imageFileSize: 1048576,
                }
            },
            messages: {
                'km_travelled': {
                    required: "Please Enter KM Travelled",
                },
                'other_charge': {
                    required: "Please Enter Other Charges",
                },
                'asp_collected_charges': {
                    required: "Please Enter Charges Collected",
                },
                'remarks_not_collected': {
                    required: "Please Enter Remark Comments",
                },
                'map_attachment[]': {
                    required: 'Please attach google map screenshot',
                },
                'other_attachment[]': {
                    required: 'Please attach other Attachment',
                },
                'vehicle_pickup_attachment': {
                    required: 'Please Upload Vehicle Pickup image',
                    imageFileSize: "Vehicle Pickup image size must be less than 1MB",
                },
                'vehicle_drop_attachment': {
                    required: 'Please Upload Vehicle Drop image',
                    imageFileSize: "Vehicle Drop image size must be less than 1MB",
                },
                'inventory_job_sheet_attachment': {
                    required: 'Please Upload Inventory Job Sheet image',
                    imageFileSize: "Inventory Job Sheet image size must be less than 1MB",
                }
            },
            errorPlacement: function(error, element) {
                if (element.attr("type") == "checkbox") {
                    error.insertBefore($(element).parents('.checkboxList'));
                } else {
                    error.insertAfter($(element));
                }
            },
            submitHandler: function(form) {
                if (self.km_travelled <= 0) {
                    custom_noty('error', 'KM travelled should be greater than zero');
                    return;
                }
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
                            if ($(".loader-type-2").hasClass("loader-hide")) {
                                $(".loader-type-2").removeClass("loader-hide");
                            }
                            $.ajax({
                                    url: laravel_routes['updateActivity'],
                                    method: "POST",
                                    data: formData,
                                    processData: false,
                                    contentType: false,
                                })
                                .done(function(res) {
                                    if (!res.success) {
                                        $(".loader-type-2").addClass("loader-hide");
                                        $('#submit').button('reset');
                                        var errors = '';
                                        for (var i in res.errors) {
                                            errors += '<li>' + res.errors[i] + '</li>';
                                        }
                                        custom_noty('error', errors);
                                    } else {
                                        custom_noty('success', "Activity informations saved successfully");
                                        $location.path('/rsa-case-pkg/new-activity');
                                        $scope.$apply();
                                    }
                                })
                                .fail(function(xhr) {
                                    $(".loader-type-2").addClass("loader-hide");
                                    $('#submit').button('reset');
                                    custom_noty('error', "Something went wrong at server");
                                });
                        }
                    }
                });
            }
        });

    }
});