app.component('deferredActivityList', {
    templateUrl: activity_deferred_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $mdSelect) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('asp-deferred-activities')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
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
                { data: 'vehicle_registration_number', name: 'cases.vehicle_registration_number', searchable: true },
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

            $('.filter-content').bind('click', function(event) {

                if ($('.md-select-menu-container').hasClass('md-active')) {
                    $mdSelect.hide();
                }
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
        const vehiclePickupAttachRemovelIds = [];
        const vehicleDropAttachRemovelIds = [];
        const inventoryJobSheetAttachRemovelIds = [];

        $('#waiting_time').datetimepicker({
            format: 'HH:mm',
            ignoreReadonly: true
        });

        $http.get(
            $form_data_url
        ).then(function(response) {
            if (!response.data.success) {
                custom_noty('error', response.data.error);
                $location.path('/rsa-case-pkg/deferred-activity/list')
                return;
            }

            if (response.data.activity.status_id != 7) {
                custom_noty('error', "Ticket not eligible for data re-entry");
                $location.path('/rsa-case-pkg/deferred-activity/list')
                return;
            }

            self.service_types_list = response.data.service_types;
            self.for_deffer_activity = response.data.for_deffer_activity;
            self.activity = response.data.activity;
            self.cc_collected_charges = response.data.cc_collected_charges;
            self.cc_not_collected = response.data.cc_other_charge;
            self.cc_actual_km = response.data.cc_km_travelled;
            self.asp_other_charge = response.data.asp_other_charge;
            self.asp_collected_charges = response.data.asp_collected_charges;
            self.asp_km_travelled = response.data.asp_km_travelled;
            self.service_type_id = response.data.activity.service_type_id;
            self.range_limit = response.data.range_limit;
            self.km_attachment = response.data.km_attachment;
            self.other_attachment = response.data.other_attachment;
            self.defer_reason = response.data.activity.defer_reason;
            self.case = response.data.case;
            self.bd_location = response.data.case.bd_location;
            self.dropDealer = response.data.dropDealer;
            self.dropLocation = response.data.dropLocation;
            self.vehiclePickupAttach = response.data.vehiclePickupAttach;
            self.vehicleDropAttach = response.data.vehicleDropAttach;
            self.inventoryJobSheetAttach = response.data.inventoryJobSheetAttach;
            self.towingAttachmentsMandatoryLabel = response.data.towingAttachmentsMandatoryLabel;
            self.border_charge = response.data.border_charges;
            self.green_tax_charge = response.data.green_tax_charges;
            self.toll_charge = response.data.toll_charges;
            self.eatable_item_charge = response.data.eatable_item_charges;
            self.fuel_charge = response.data.fuel_charges;
            // self.waiting_time = response.data.waiting_time;
            self.towingAttachmentSamplePhoto = 1;
            //TOWING GROUP
            if (self.activity.service_type.service_group_id == 3) {
                self.showTowingAttachment = true;
            } else {
                self.showTowingAttachment = false;
            }

            self.kmTravelledHideShow();
            $scope.aspWaitingTime(response.data.waiting_time);
            $scope.calculateOtherCharges();
            $rootScope.loading = false;
        });

        $scope.aspWaitingTime = (waitingTime) => {
            if (waitingTime) {
                let seconds = parseFloat(waitingTime) * 60;

                // calculate (and subtract) whole days
                let days = Math.floor(seconds / 86400);
                seconds -= days * 86400;

                // calculate (and subtract) whole hours
                let hours = Math.floor(seconds / 3600) % 24;
                seconds -= hours * 3600;

                // calculate (and subtract) whole minutes
                let minutes = Math.floor(seconds / 60) % 60;

                let hoursVal = ('0' + hours).slice(-2);
                let minsVal = ('0' + minutes).slice(-2);

                self.waiting_time = hoursVal + ':' + minsVal;
            } else {
                self.waiting_time = '00:00';
            }
        }

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

        self.closeVehiclePickupAttach = (index, vehiclePickupAttachId) => {
            if (vehiclePickupAttachId) {
                vehiclePickupAttachRemovelIds.push(vehiclePickupAttachId);
                $('#vehiclePickupAttachRemovelIds').val(JSON.stringify(vehiclePickupAttachRemovelIds));
            }
            self.vehiclePickupAttach = '';
        }

        self.closeVehicleDropAttach = (index, vehicleDropAttachId) => {
            if (vehicleDropAttachId) {
                vehicleDropAttachRemovelIds.push(vehicleDropAttachId);
                $('#vehicleDropAttachRemovelIds').val(JSON.stringify(vehicleDropAttachRemovelIds));
            }
            self.vehicleDropAttach = '';
        }

        self.closeInventoryJobSheetAttach = (index, inventoryJobSheetAttachId) => {
            if (inventoryJobSheetAttachId) {
                inventoryJobSheetAttachRemovelIds.push(inventoryJobSheetAttachId);
                $('#inventoryJobSheetAttachRemovelIds').val(JSON.stringify(inventoryJobSheetAttachRemovelIds));
            }
            self.inventoryJobSheetAttach = '';
        }

        self.kmTravelledHideShow = function() {
            let kmTravelled = parseFloat(self.asp_km_travelled) || 0;
            let actualKm = parseFloat(self.cc_actual_km) || 0;
            let rangeLimit = parseFloat(self.range_limit) || 0;
            if ($.isNumeric(kmTravelled)) {
                if (kmTravelled > rangeLimit || rangeLimit == "") {
                    let allowedVariation = 0.5;
                    let misPercentageDifference = parseFloat(actualKm * allowedVariation / 100);
                    if (kmTravelled) {
                        if (kmTravelled > actualKm) {
                            let kmDifference = parseFloat(kmTravelled - actualKm);
                            if (kmDifference > misPercentageDifference) {
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

        self.otherChargeHideShow = function() {
            var other_charge_entered = parseFloat(self.asp_other_charge);
            var other_charge = parseFloat(self.cc_not_collected);
            if ($.isNumeric(other_charge_entered)) {
                if (other_charge_entered) {
                    //DISABLED
                    // if (other_charge_entered > other_charge) {
                    //NEW LOGIC BY CLIENT
                    if (parseFloat(other_charge_entered) >= 31) {
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

        $scope.calculateOtherCharges = () => {
            let otherCharge = 0;
            let borderCharge = parseFloat(self.border_charge) || 0;
            let greenTaxCharge = parseFloat(self.green_tax_charge) || 0;
            let tollCharge = parseFloat(self.toll_charge) || 0;
            let eatableItemCharge = parseFloat(self.eatable_item_charge) || 0;
            let fuelCharge = parseFloat(self.fuel_charge) || 0;

            otherCharge = borderCharge + greenTaxCharge + tollCharge + eatableItemCharge + fuelCharge;
            self.asp_other_charge = parseFloat(otherCharge).toFixed(2);
            self.otherChargeHideShow();
        }

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

        $scope.getServiceTypeDetail = () => {
            if (self.service_type_id) {
                $.ajax({
                        url: getActivityServiceTypeDetail + '/' + self.service_type_id + '/' + self.activity.id,
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
                            self.activity = res.activity;
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
                    number: true,
                },
                'other_charge': {
                    number: true,
                    required: true,
                },
                'remarks_not_collected': {
                    required: true,
                },
                'comments': {
                    required: true,
                },
                'asp_collected_charges': {
                    number: true,
                    required: true,
                },
                'map_attachment[]': {
                    // required: true,
                    check_map_attach: true,
                    extension: "jpg|jpeg|png|gif"
                },
                'other_attachment[]': {
                    // required: true,
                    check_other_attach: true,
                    extension: "jpg|jpeg|png|gif|pdf"
                },
                'vehicle_pickup_attachment': {
                    required: function(element) {
                        return self.activity.is_towing_attachments_mandatory === 1 && !self.vehiclePickupAttach && self.activity.finance_status.po_eligibility_type_id == 340;
                    },
                    //imageFileSize: 1048576,
                    extension: "jpg|jpeg|png",
                },
                'vehicle_drop_attachment': {
                    required: function(element) {
                        return self.activity.is_towing_attachments_mandatory === 1 && !self.vehicleDropAttach && self.activity.finance_status.po_eligibility_type_id == 340;
                    },
                    //imageFileSize: 1048576,
                    extension: "jpg|jpeg|png",
                },
                'inventory_job_sheet_attachment': {
                    required: function(element) {
                        return self.activity.is_towing_attachments_mandatory === 1 && !self.inventoryJobSheetAttach && self.activity.finance_status.po_eligibility_type_id == 340;
                    },
                    //imageFileSize: 1048576,
                    extension: "jpg|jpeg|png",
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
                    required: "Please Enter Remarks",
                },
                'comments': {
                    required: "Please Enter Resolve Comments",
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
                    extension: "Please Upload Vehicle Pickup image in jpeg, png, jpg formats",
                },
                'vehicle_drop_attachment': {
                    required: 'Please Upload Vehicle Drop image',
                    imageFileSize: "Vehicle Drop image size must be less than 1MB",
                    extension: "Please Upload Vehicle Drop image in jpeg, png, jpg formats",
                },
                'inventory_job_sheet_attachment': {
                    required: 'Please Upload Inventory Job Sheet image',
                    imageFileSize: "Inventory Job Sheet image size must be less than 1MB",
                    extension: "Please Upload Inventory Job Sheet image in jpeg, png, jpg formats",
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
                if (self.asp_km_travelled <= 0) {
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
                                    $(".loader-type-2").addClass("loader-hide");
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