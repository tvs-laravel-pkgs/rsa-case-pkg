app.component('aspActivitiesHeader', {
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

                self.show_km = 0;
                if (self.data.verification == 1) {
                    self.data.bo_comments = "";
                    self.data.deduction_reason = "";
                }
                $scope.deferTicket = function() {
                    $("#reject-modal").modal();
                }

                $scope.approveTicket = function() {
                    if (self.data.raw_bo_km_travelled !== '' && self.data.raw_bo_km_travelled <= 0) {
                        custom_noty('error', 'KM Travelled should be greater than zero');
                        return;
                    }
                    if (self.data.raw_bo_km_travelled !== 0 && self.data.raw_bo_km_travelled === '') {
                        custom_noty('error', 'KM Travelled is required');
                        return;
                    } else if (self.data.raw_bo_not_collected !== 0 && self.data.raw_bo_not_collected === '') {
                        custom_noty('error', 'Charges not collected is required');
                        return;
                    } else if (self.data.raw_bo_collected !== 0 && self.data.raw_bo_collected === '') {
                        custom_noty('error', 'Charges collected is required');
                        return;
                    } else if (self.show_km == 1) {
                        custom_noty('error', 'Enter BO KM less than ASP KM');
                        return;
                    } else {
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
                                bo_deduction: self.data.bo_deduction,
                                bo_po_amount: self.data.bo_po_amount,
                                bo_net_amount: self.data.bo_net_amount,
                                bo_amount: self.data.bo_amount,
                                bo_comments: self.data.bo_comments,
                                deduction_reason: self.data.deduction_reason,
                                exceptional_reason: self.exceptional_reason,
                                is_exceptional_check: self.is_exceptional_check,
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

                $scope.calculatePO = function() {
                    total = parseFloat(self.data.bo_po_amount) + parseFloat(self.data.raw_bo_not_collected) - parseFloat(self.data.raw_bo_collected);
                    if (self.data.bo_deduction) {
                        total -= parseFloat(self.data.bo_deduction);
                    }
                    self.data.bo_net_amount = self.data.bo_amount = total;
                }

                $scope.calculate = function() {
                    //If view page and activity has been initiated for payment process
                    if (self.data.verification == 0 && (self.data.activity_portal_status_id == 1 || self.data.activity_portal_status_id == 10 || self.data.activity_portal_status_id == 11 || self.data.activity_portal_status_id == 12 || self.data.activity_portal_status_id == 13 || self.data.activity_portal_status_id == 14)) {
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
                        var adjustment = 0;
                        if (parseFloat(self.data.asp_service_type_data.adjustment_type) == 2) {
                            adjustment = parseFloat(self.data.asp_service_type_data.adjustment);
                        } else if (self.data.asp_service_type_data.adjustment_type == 1) {
                            adjustment = parseFloat(parseFloat(amount_wo_deduction) * (parseFloat(self.data.asp_service_type_data.adjustment) / 100));
                        }

                        //FORMULAE DISABLED AS PER CLIENT REQUEST
                        // var amount = parseFloat(amount_wo_deduction) + parseFloat(adjustment);
                        var amount = parseFloat(amount_wo_deduction);

                        self.data.bo_po_amount = amount;

                        //FORMULAE DISABLED AS PER CLIENT REQUEST
                        // if (self.data.asp.app_user == 0) {
                        //     adjustment = 0;
                        // }
                        self.data.bo_deduction = parseFloat(adjustment);
                        var total = (parseFloat(amount) + parseFloat(self.data.raw_bo_not_collected)) - parseFloat(self.data.raw_bo_collected) - parseFloat(self.data.bo_deduction);

                        self.data.bo_net_amount = self.data.bo_amount = total;
                    }
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