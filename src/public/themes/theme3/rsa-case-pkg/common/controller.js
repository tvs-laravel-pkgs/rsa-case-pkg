app.component('aspActivitiesHeader', {
    templateUrl: activity_status_view_tab_header_template_url,
    bindings: {
        data: '<',
    },
    controller: function($http, HelperService, $scope, $rootScope, $routeParams, $location) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
    }
});

//----------------------------------------------------------------------------------------------------------------------------

app.component('caseDetailHeader', {
    templateUrl: activity_status_view_tab_header_template_url,
    bindings: {
        data: '<',
    },
    controller: function($http, HelperService, $scope, $rootScope, $routeParams, $location) {
        $scope.loading = true;
        var self = this;
    }
});

//----------------------------------------------------------------------------------------------------------------------------

app.component('aspDetails', {
    templateUrl: activity_status_view_asp_details_template_url,
    bindings: {
        data: '<',
    },
    controller: function($http, HelperService, $scope, $rootScope, $routeParams, $location) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
    }
});
//----------------------------------------------------------------------------------------------------------------------------
app.component('ticketHeader', {
    templateUrl: activity_status_view_ticket_header2_template_url,
    bindings: {
        data: '<',
    },
    controller: function($http, HelperService, $scope, $rootScope, $routeParams, $location) {
        $scope.loading = true;
        var self = this;
    }
});
//----------------------------------------------------------------------------------------------------------------------------

app.component('serviceDetails', {
    templateUrl: activity_status_view_service_details_template_url,
    bindings: {
        data: '<',
    },
    controller: function($http, HelperService, $scope, $rootScope, $routeParams, $location) {
        $scope.loading = true;
        var self = this;

        this.$onInit = function() {
            setTimeout(function() {
                //self.data = activity;
                $(".km_value").keyup(function() {
                    var entry = parseInt($(this).val());
                    var asp = parseInt($(".asp_value").text());
                    if (entry > asp) {
                        $(this).val("");
                        $(".alert-danger").show();
                    } else { $(".alert-danger").hide(); }

                });
                $(".collected_amount").keyup(function() {
                    var entry = parseInt($(this).val());
                    var asp = parseInt($(".collected_value").text());
                    if (entry > asp) {
                        $(this).val("");
                        $(".alert-danger1").show();
                    } else {
                        console.log(entry);
                        $("#info-1 .collected_by_asp").val(entry);
                        $(".alert-danger1").hide();
                    }

                });
                $(".non_collected_amount").keyup(function() {
                    var entry = parseInt($(this).val());
                    var asp = parseInt($(".other_value").text());
                    if (entry > asp) {
                        $(this).val("");
                        $(".alert-danger2").show();
                    } else {
                        console.log(entry);
                        $("#info-1 .not_collected_by_asp").val(entry);
                        $(".alert-danger2").hide();
                    }

                });
            }, 1000);
        };


    }
});

//----------------------------------------------------------------------------------------------------------------------------

app.component('priceInfo', {
    templateUrl: activity_status_view_price_info_template_url,
    bindings: {
        data: '<',
    },
    controller: function($http, HelperService, $scope, $rootScope, $routeParams, $location) {
        $scope.loading = true;
        var self = this;
    }
});

//----------------------------------------------------------------------------------------------------------------------------

app.component('serviceTotalSummaryView', {
    templateUrl: activity_status_view_service_total_summary_view_template_url,
    bindings: {
        data: '<',
    },
    controller: function($http, HelperService, $scope, $rootScope, $routeParams, $location) {
        $scope.loading = true;
        var self = this;
    }
});

//----------------------------------------------------------------------------------------------------------------------------

app.component('serviceTotalSummary', {
    templateUrl: activity_status_view_service_total_summary_template_url,
    bindings: {
        data: '<',
    },
    controller: function($http, HelperService, $scope, $rootScope, $routeParams, $location) {
        $scope.loading = true;
        var self = this;
    }
});


//----------------------------------------------------------------------------------------------------------------------------

app.component('ticketTotalSummaryView', {
    templateUrl: activity_status_view_ticket_total_summary_view_template_url,
    bindings: {
        data: '<',
    },
    controller: function($http, HelperService, $scope, $rootScope, $routeParams, $location) {
        $scope.loading = true;
        var self = this;

    }
});

//----------------------------------------------------------------------------------------------------------------------------

app.component('ticketTotalSummary', {
    templateUrl: activity_status_view_ticket_total_summary_template_url,
    bindings: {
        data: '<',
    },
    controller: function($http, HelperService, $scope, $rootScope, $routeParams, $location) {
        $scope.loading = true;
        var self = this;
    }
});


//----------------------------------------------------------------------------------------------------------------------------

app.component('activityDetails', {
    templateUrl: activity_status_view_activity_details_view_template_url,
    bindings: {
        data: '<',
    },
    controller: function($http, HelperService, $scope, $rootScope, $routeParams, $location) {
        $scope.loading = true;
        var self = this;
    }
});

//----------------------------------------------------------------------------------------------------------------------------

app.component('locationDetails', {
    templateUrl: activity_status_view_location_details_view_template_url,
    bindings: {
        data: '<',
    },
    controller: function($http, HelperService, $scope, $rootScope, $routeParams, $location) {
        $scope.loading = true;
        var self = this;
    }
});
//----------------------------------------------------------------------------------------------------------------------------

app.component('kmDetails', {
    templateUrl: activity_status_view_km_details_view_template_url,
    bindings: {
        data: '<',
    },
    controller: function($http, HelperService, $scope, $rootScope, $routeParams, $location) {
        $scope.loading = true;
        var self = this;
    }
});
//----------------------------------------------------------------------------------------------------------------------------

app.component('invoiceDetails', {
    templateUrl: activity_status_view_invoice_details_view_template_url,
    bindings: {
        data: '<',
    },
    controller: function($http, HelperService, $scope, $rootScope, $routeParams, $location) {
        $scope.loading = true;
        var self = this;
        this.$onInit = function() {
            setTimeout(function() {
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
            }, 1000);
        };

    }
});
//----------------------------------------------------------------------------------------------------------------------------

app.component('financialDetails', {
    templateUrl: activity_status_view_financial_details_view_template_url,
    bindings: {
        data: '<',
    },
    controller: function($http, HelperService, $scope, $rootScope, $routeParams, $location) {
        $scope.loading = true;
        var self = this;
    }
});
//----------------------------------------------------------------------------------------------------------------------------

app.component('caseDetails', {
    templateUrl: activity_status_view_case_details_view_template_url,
    bindings: {
        data: '<',
    },
    controller: function($http, HelperService, $scope, $rootScope, $routeParams, $location) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
    }
});

//----------------------------------------------------------------------------------------------------------------------------

app.component('billingDetails', {
    templateUrl: activity_status_view_billing_details_view_template_url,
    bindings: {
        data: '<',
    },
    controller: function($http, HelperService, $scope, $rootScope, $routeParams, $location) {
        $scope.loading = true;
        var self = this;
        //self.data = activity;
        this.$onInit = function() {
            setTimeout(function() {
                self.hasPermission = HelperService.hasPermission;
                self.activity_back_asp_update_route = activity_back_asp_update;
                self.backstepReason = '';
                self.csrf = token;
                self.data.raw_bo_waiting_charges = (self.data.raw_bo_waiting_charges == '' || self.data.raw_bo_waiting_charges == '-') ? 0.00 : self.data.raw_bo_waiting_charges;


                $scope.boWaitingTime = () => {
                    let seconds = parseFloat(self.data.bo_waiting_time) * 60;

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

                    self.bo_waiting_time = hoursVal + ':' + minsVal;
                }

                if (self.data.verification == 1 && (self.data.activityApprovalLevel == 1 || self.data.activityApprovalLevel == 3)) {
                    $scope.boWaitingTime();
                }

                // SET BO KM, COLLECTED AND NOT COLLECTED VALUE AS ZERO FOR L1 LEVEL VERIFICATIONS
                if (self.data.verification == 1 && (self.data.activity_portal_status_id == 5 || self.data.activity_portal_status_id == 6 || self.data.activity_portal_status_id == 8 || self.data.activity_portal_status_id == 9)) {
                    self.data.raw_bo_collected = 0;
                    // self.data.raw_bo_not_collected = 0;
                    // self.data.raw_bo_km_travelled = 0;
                }

                self.show_km = 0;
                if (self.data.verification == 1) {
                    self.data.bo_comments = "";
                    self.data.deduction_reason = "";
                }

                $scope.asp_data_entry_submit = function() {
                    $('#ticket_status_id').val(1);
                    if (self.backstepReason == "") {
                        custom_noty('error', 'Reason is required');
                    } else {
                        setTimeout(function() {
                            $('#tickect-back-asp-form').submit();
                        }, 1000);
                    }
                };

                $scope.asp_bo_deffered_submit = function() {
                    $('#ticket_status_id').val(2);
                    if (self.backstepReason == "") {
                        custom_noty('error', 'Reason is required');
                    } else {
                        setTimeout(function() {
                            $('#tickect-back-asp-form').submit();
                        }, 1000);
                    }
                };

                $scope.deferTicket = function() {
                    $("#reject-modal").modal();
                }

                $scope.approveTicket = function() {

                    if (self.data.verification == 1 && self.data.boServiceTypeId == '') {
                        custom_noty('error', 'Service is required');
                        return;
                    }
                    if (self.data.raw_bo_km_travelled !== 0 && self.data.raw_bo_km_travelled === '') {
                        custom_noty('error', 'KM Travelled is required');
                        return;
                    } else if (self.data.raw_bo_not_collected !== 0 && self.data.raw_bo_not_collected === '') {
                        custom_noty('error', 'Charges not collected is required');
                        return;
                    } else if (self.data.raw_bo_border_charges !== 0 && self.data.raw_bo_border_charges === '') {
                        custom_noty('error', 'Border Charges is required');
                        return;
                    } else if (self.data.raw_bo_green_tax_charges !== 0 && self.data.raw_bo_green_tax_charges === '') {
                        custom_noty('error', 'Green Tax Charges is required');
                        return;
                    } else if (self.data.raw_bo_toll_charges !== 0 && self.data.raw_bo_toll_charges === '') {
                        custom_noty('error', 'Toll Charges is required');
                        return;
                    } else if (self.data.raw_bo_eatable_items_charges !== 0 && self.data.raw_bo_eatable_items_charges === '') {
                        custom_noty('error', 'Eatable Items Charges is required');
                        return;
                    } else if (self.data.raw_bo_fuel_charges !== 0 && self.data.raw_bo_fuel_charges === '') {
                        custom_noty('error', 'Fuel Charges is required');
                        return;
                    } else if (self.data.raw_bo_collected !== 0 && self.data.raw_bo_collected === '') {
                        custom_noty('error', 'Charges collected is required');
                        return;
                    } else if (self.show_km == 1) {
                        custom_noty('error', 'Enter BO KM less than ASP KM');
                        return;
                    } else {
                        if (self.data.raw_bo_km_travelled !== '' && self.data.raw_bo_km_travelled <= 0) {
                            custom_noty('error', 'KM Travelled should be greater than zero');
                            return;
                        }

                        if (self.data.bo_net_amount <= 0) {
                            custom_noty('error', 'Payout amount should be greater than zero');
                            return;
                        }
                        $("#confirm-ticket-modal").modal();
                    }
                }

                $scope.saveApproval = function() {
                    if ($scope.myForm.$valid) {
                        $('.approve_btn').button('loading');
                        if ($(".loader-type-2").hasClass("loader-hide")) {
                            $(".loader-type-2").removeClass("loader-hide");
                        }
                        $http.post(
                            laravel_routes['approveActivity'], {
                                activity_id: self.data.id,
                                case_number: self.data.number,
                                bo_km_travelled: self.data.raw_bo_km_travelled,
                                bo_collected: self.data.raw_bo_collected,
                                bo_not_collected: self.data.raw_bo_not_collected,
                                bo_waiting_time: self.data.bo_waiting_time,
                                bo_waiting_charges: self.data.raw_bo_waiting_charges,
                                bo_border_charges: self.data.raw_bo_border_charges,
                                bo_green_tax_charges: self.data.raw_bo_green_tax_charges,
                                bo_toll_charges: self.data.raw_bo_toll_charges,
                                bo_eatable_items_charges: self.data.raw_bo_eatable_items_charges,
                                bo_fuel_charges: self.data.raw_bo_fuel_charges,
                                bo_deduction: self.data.bo_deduction,
                                bo_po_amount: self.data.bo_po_amount,
                                bo_net_amount: self.data.bo_net_amount,
                                bo_amount: self.data.bo_amount,
                                bo_comments: self.data.bo_comments,
                                deduction_reason: self.data.deduction_reason,
                                exceptional_reason: self.exceptional_reason,
                                // is_exceptional_check: self.is_exceptional_check,
                                is_exceptional_check: 1,
                                bo_service_type: self.data.bo_service_type,
                                boServiceTypeId: self.data.boServiceTypeId,
                            }
                        ).then(function(response) {
                            $(".loader-type-2").addClass("loader-hide");
                            $('.approve_btn').button('reset');
                            $("#confirm-ticket-modal").modal("hide");

                            if (!response.data.success) {
                                var errors = '';
                                for (var i in response.data.errors) {
                                    errors += '<li>' + response.data.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);
                                return;
                            } else {
                                custom_noty('success', response.data.message);
                                setTimeout(function() {
                                    $location.path('/rsa-case-pkg/activity-verification/list');
                                    $scope.$apply();
                                }, 1500);
                            }
                        });
                    }
                }
                $scope.differ = function() {
                    if ($scope.differForm.$valid) {
                        $('.differ_btn').button('loading');
                        if ($(".loader-type-2").hasClass("loader-hide")) {
                            $(".loader-type-2").removeClass("loader-hide");
                        }
                        $http.post(
                            laravel_routes['saveActivityDiffer'], {
                                activity_id: self.data.id,
                                defer_reason: self.defer_reason,
                                bo_comments: self.data.bo_comments,
                                deduction_reason: self.data.deduction_reason,
                                case_number: self.data.number,
                                /*bo_km_travelled : self.data.bo_km_travelled,
                                raw_asp_collected : self.data.raw_asp_collected,
                                raw_asp_not_collected : self.data.raw_asp_not_collected,
                                bo_deduction : self.data.bo_deduction,
                                bo_po_amount : self.data.bo_po_amount,
                                bo_net_amount : self.data.bo_net_amount,
                                bo_amount : self.data.bo_amount,*/
                            }
                        ).then(function(response) {
                            $(".loader-type-2").addClass("loader-hide");
                            $('.differ_btn').button('reset');
                            $("#reject-modal").modal("hide");
                            if (!response.data.success) {
                                var errors = '';
                                for (var i in response.data.errors) {
                                    errors += '<li>' + response.data.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);
                                return;
                            } else {
                                custom_noty('success', response.data.message);
                                setTimeout(function() {
                                    $location.path('/rsa-case-pkg/activity-verification/list');
                                    $scope.$apply();
                                }, 1500);
                            }
                        });
                    }
                }

                $scope.getServiceTypeRateCardDetail = () => {
                    if (self.data.boServiceTypeId && self.data.asp_id) {
                        $.ajax({
                                url: getServiceTypeRateCardDetail,
                                method: "POST",
                                data: {
                                    activity_id: self.data.id,
                                    service_type_id: self.data.boServiceTypeId,
                                    asp_id: self.data.asp_id,
                                },
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
                                    self.data.bo_service_type = self.data.service = res.service;
                                    self.data.asp_service_type_data = res.asp_service_type_data;
                                    $scope.calculate();
                                    $scope.$apply()
                                }
                            })
                            .fail(function(xhr) {
                                custom_noty('error', 'Something went wrong at server');
                                console.log(xhr);
                            });
                    }
                }

                $('#bo_waiting_time').on('dp.change', function(e) {
                    if (e.date) {
                        let boWaitingTime = e.date.format('HH:mm');
                        let [waitingTimeHour, waitingTimeMin] = boWaitingTime.split(':');
                        self.data.bo_waiting_time = parseFloat(waitingTimeHour * 60) + parseFloat(waitingTimeMin);
                        setTimeout(function() {
                            $scope.calculate();
                            $scope.$apply()
                        }, 500);
                    }
                });

                $scope.calculatePO = function() {
                    total = (parseFloat(self.data.bo_po_amount) + parseFloat(self.data.raw_bo_waiting_charges) + parseFloat(self.data.raw_bo_not_collected)) - parseFloat(self.data.raw_bo_collected);
                    if (self.data.bo_deduction) {
                        total -= parseFloat(self.data.bo_deduction);
                    }
                    self.data.bo_net_amount = self.data.bo_amount = parseFloat(total).toFixed(2);
                }

                $scope.calculate = function() {
                    //If view page and activity has been initiated for payment process
                    if (self.data.verification == 0 && (self.data.activity_portal_status_id == 1 || self.data.activity_portal_status_id == 10 || self.data.activity_portal_status_id == 11 || self.data.activity_portal_status_id == 12 || self.data.activity_portal_status_id == 13 || self.data.activity_portal_status_id == 14 || self.data.activity_portal_status_id == 20 || self.data.activity_portal_status_id == 23)) {
                        self.show_km = 0;
                        self.data.bo_po_amount = self.data.raw_bo_po_amount;
                        self.data.bo_deduction = self.data.raw_bo_deduction;
                        self.data.bo_net_amount = self.data.raw_bo_amount;
                    } else {
                        if (self.data.finance_status.po_eligibility_type_id == 341) {
                            var below_amount = parseFloat(self.data.raw_bo_km_travelled) == 0 ? 0 : parseFloat(self.data.asp_service_type_data.empty_return_range_price);
                        } else {
                            var below_amount = parseFloat(self.data.raw_bo_km_travelled) == 0 ? 0 : parseFloat(self.data.asp_service_type_data.below_range_price);
                        }
                        if (parseFloat(self.data.raw_bo_km_travelled) > parseFloat(self.data.asp_km_travelled)) {
                            self.show_km = 1;
                        } else {
                            self.show_km = 0;
                        }

                        if (parseFloat(self.data.asp_service_type_data.range_limit) > parseFloat(self.data.raw_bo_km_travelled)) {
                            var above_amount = 0;
                        } else {
                            var excess = parseFloat(self.data.raw_bo_km_travelled) - parseFloat(self.data.asp_service_type_data.range_limit);
                            var above_amount = (parseFloat(excess) * parseFloat(self.data.asp_service_type_data.above_range_price));
                        }
                        var amount_wo_deduction = parseFloat(below_amount) + parseFloat(above_amount);

                        //DISABLED AS THERE IS NO ADJUSTMENT TYPE IN FUTURE
                        // var adjustment = 0;
                        // if (parseFloat(self.data.asp_service_type_data.adjustment_type) == 2) {
                        //     adjustment = parseFloat(self.data.asp_service_type_data.adjustment);
                        // } else if (self.data.asp_service_type_data.adjustment_type == 1) {
                        //     adjustment = parseFloat(parseFloat(amount_wo_deduction) * (parseFloat(self.data.asp_service_type_data.adjustment) / 100));
                        // }

                        //FORMULAE DISABLED AS PER CLIENT REQUEST
                        // var amount = parseFloat(amount_wo_deduction) + parseFloat(adjustment);
                        var amount = parseFloat(amount_wo_deduction);

                        self.data.bo_po_amount = amount;

                        //FORMULAE DISABLED AS PER CLIENT REQUEST
                        // if (self.data.asp.app_user == 0) {
                        //     adjustment = 0;
                        // }
                        let boDeduction = 0;
                        if (self.data.bo_deduction != '') {
                            boDeduction = self.data.bo_deduction;
                        }

                        self.data.raw_bo_waiting_charges = 0;
                        if (self.data.asp_service_type_data.waiting_charge_per_hour && self.data.bo_waiting_time) {
                            self.data.raw_bo_waiting_charges = parseFloat(parseFloat(self.data.bo_waiting_time / 60) * parseFloat(self.data.asp_service_type_data.waiting_charge_per_hour)).toFixed(2);
                        }

                        if (self.data.eligibleForOthersplitupCharges) {
                            let otherCharge = 0;
                            let borderCharge = parseFloat(self.data.raw_bo_border_charges) || 0;
                            let greenTaxCharge = parseFloat(self.data.raw_bo_green_tax_charges) || 0;
                            let tollCharge = parseFloat(self.data.raw_bo_toll_charges) || 0;
                            let eatableItemCharge = parseFloat(self.data.raw_bo_eatable_items_charges) || 0;
                            let fuelCharge = parseFloat(self.data.raw_bo_fuel_charges) || 0;
                            otherCharge = borderCharge + greenTaxCharge + tollCharge + eatableItemCharge + fuelCharge;
                            self.data.raw_bo_not_collected = parseFloat(otherCharge).toFixed(2);
                        }

                        // self.data.bo_deduction = parseFloat(adjustment);
                        self.data.bo_deduction = parseFloat(boDeduction);
                        var total = (parseFloat(amount) + parseFloat(self.data.raw_bo_not_collected) + parseFloat(self.data.raw_bo_waiting_charges)) - parseFloat(self.data.raw_bo_collected) - parseFloat(self.data.bo_deduction);

                        self.data.bo_net_amount = self.data.bo_amount = parseFloat(total).toFixed(2);
                    }
                }
                $scope.kmTravelledMapView = function(){
                //Dynamic Data
                  window.open("https://www.google.co.in/maps/dir/"+self.data.asp_start_location+"/"+self.data.bd_lat+","+self.data.bd_long+"/"+self.data.drop_location_lat+","+self.data.drop_location_long+"/"+self.data.asp_end_location)
                //static data with only location
                    //window.open("https://www.google.co.in/maps/dir/"+self.data.asp_start_location+"/"+"Tiruppur"+"/"+"karur"+"/"+self.data.asp_end_location)
                //static data with only LAT LONG
                   // window.open("https://www.google.co.in/maps/dir/11.3044741,77.003596/10.608394,77.067367/10.986107,76.968137")
             //static data with only LAT LONG ONLY START AND END POINTS .ITS SHOWS THE MULTIPLE ROUTES
                   // window.open("https://www.google.co.in/maps/dir/11.3044741,77.003596/10.608394, 77.067367")

                }

                $scope.calculate();
                $scope.$apply()
            }, 3000);
        };
    }
});
//----------------------------------------------------------------------------------------------------------------------------

app.component('activityHistories', {
    templateUrl: activity_status_view_histories_template_url,
    bindings: {
        data: '<',
    },
    controller: function($http, HelperService, $scope, $rootScope, $routeParams, $location) {
        $scope.loading = true;
        var self = this;

        $scope.formatDate = date => {
            var dateOut = new Date(date);
            return dateOut;
        }
    }
});