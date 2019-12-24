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

        //self.data = activity;
        $(".km_value").keyup(function(){
            var entry = parseInt($(this).val());
            var asp = parseInt($(".asp_value").text());
            console.log('entry');
            console.log(entry);
            console.log('asp');
            console.log(asp);
            if(entry > asp){ $(this).val(""); $(".alert-danger").show(); }else {  $(".alert-danger").hide(); }

        });
    $(".collected_amount").keyup(function(){
            var entry = parseInt($(this).val());
            var asp = parseInt($(".collected_value").text());
            if(entry > asp){ 
                $(this).val(""); $(".alert-danger1").show(); 
            }else { 
                console.log(entry);
                $("#info-1 .collected_by_asp").val(entry); 
                $(".alert-danger1").hide(); 
            }

        });
    $(".non_collected_amount").keyup(function(){
            var entry = parseInt($(this).val());
            var asp = parseInt($(".other_value").text());
            if(entry > asp){ $(this).val(""); $(".alert-danger2").show(); }else { console.log(entry); $("#info-1 .not_collected_by_asp").val(entry); $(".alert-danger2").hide(); }

        });


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
         setTimeout(function(){ 
            $scope.deferTicket = function(){
                $("#reject-modal").modal();
            }

        $scope.approveTicket = function(){
            $("#confirm-ticket-modal").modal();
            
        }
     
    $scope.saveApproval = function(){
        $http.post(
                    laravel_routes['approveActivity'], {
                        activity_id : self.data.activity_id,
                        bo_km_travelled : self.data.bo_km_travelled,
                        raw_bo_collected : self.data.raw_bo_collected,
                        raw_bo_not_collected : self.data.raw_bo_not_collected,
                        bo_deduction : self.data.bo_deduction,
                        bo_po_amount : self.data.bo_po_amount,
                        bo_net_amount : self.data.bo_net_amount,
                        bo_amount : self.data.bo_amount,

                    }
                ).then(function(response) {
                    $('.save').button('reset');
                    $("#confirm-ticket-modal").modal("hide");

                    if (!response.data.data.success) {
                        var errors = '';
                        for (var i in response.data.data.errors) {
                            errors += '<li>' + response.data.errors[i] + '</li>';
                        }
                        $noty = new Noty({
                            type: 'error',
                            layout: 'bottomRight',
                            text: errors,
                            animation: {
                                speed: 500 // unavailable - no need
                            },

                        }).show();
                        setTimeout(function() {
                            $noty.close();
                        }, 1000);
                        return;
                    }
                    $location.path('/rsa-case-pkg/activity-verification/list');
                    $scope.$apply();
                    $noty = new Noty({
                        type: 'success',
                        layout: 'bottomRight',
                        text: response.data.data.message,
                        animation: {
                            speed: 500
                        }
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 1000);

                    item.selected = false;
                    //$scope.getChannelDiscountAmounts();

                });

    }
    $scope.differ = function(){
        $http.post(
                    laravel_routes['saveActivityDiffer'], {
                        activity_id : self.data.activity_id,
                        /*bo_km_travelled : self.data.bo_km_travelled,
                        raw_bo_collected : self.data.raw_bo_collected,
                        raw_bo_not_collected : self.data.raw_bo_not_collected,
                        bo_deduction : self.data.bo_deduction,
                        bo_po_amount : self.data.bo_po_amount,
                        bo_net_amount : self.data.bo_net_amount,
                        bo_amount : self.data.bo_amount,*/

                    }
                ).then(function(response) {
                    $('.save').button('reset');
                    $("#reject-modal").modal("hide");

                    if (!response.data.data.success) {
                        var errors = '';
                        for (var i in response.data.data.errors) {
                            errors += '<li>' + response.data.errors[i] + '</li>';
                        }
                        $noty = new Noty({
                            type: 'error',
                            layout: 'bottomRight',
                            text: errors,
                            animation: {
                                speed: 500 // unavailable - no need
                            },

                        }).show();
                        setTimeout(function() {
                            $noty.close();
                        }, 1000);
                        return;
                    }
                    $location.path('/rsa-case-pkg/activity-verification/list');
                    $scope.$apply();
                    $noty = new Noty({
                        type: 'success',
                        layout: 'bottomRight',
                        text: response.data.data.message,
                        animation: {
                            speed: 500
                        }
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 1000);

                    item.selected = false;
                    //$scope.getChannelDiscountAmounts();

                });

    }
          }, 3000);
        setTimeout(function() {
            $scope.calculate();
            $scope.$apply();
        }, 4000);
        $scope.calculate = function(){
            var amount = 0;
            if(self.data.asp_service_type_data.range_limit >self.data.bo_km_travelled){
                amount = self.data.asp_service_type_data.below_range_price;
            }else{
                excess = self.data.bo_km_travelled - self.data.asp_service_type_data.range_limit;
                amount = parseFloat(self.data.asp_service_type_data.below_range_price) + (parseFloat(excess)*parseFloat(self.data.asp_service_type_data.above_range_price));
            }
            self.data.bo_po_amount =  parseFloat(amount);
            self.data.bo_deduction = !$.isNumeric(self.data.bo_deduction) ? 0 : parseFloat(self.data.bo_deduction);
            //alert(self.data.bo_km_travelled);
            total = 0;
            if(self.data.asp.tax_calculation_method){
            total = parseFloat(amount) - (parseFloat(self.data.raw_bo_collected) + parseFloat(self.data.bo_deduction))

            }else{
            total = (parseFloat(amount) + parseFloat(self.data.raw_bo_not_collected)) - (parseFloat(self.data.raw_bo_collected) + parseFloat(self.data.bo_deduction))

            }
            self.data.bo_net_amount =  self.data.bo_amount = total /*parseFloat(amount)- parseFloat(self.data.raw_bo_collected)*/;
            //self.data.bo_amount = parseFloat(self.data.bo_net_amount) + parseFloat(self.data.raw_bo_not_collected);
            //inv_amount = parseFloat(self.data.bo_net_amount);
            total_tax = 0;
            taxes = self.data.asp.tax_group.taxes;
            if(self.data.asp.has_gst){
                total_tax = 0;
                /*angular.forEach(taxes, function (value, key) { 
                    console.log('value'); 
                    console.log(value); 
                    total_tax = parseFloat(total_tax) + parseFloat(value.tax_rate);
                }); */
                self.data.bo_tax_amount = parseFloat(((parseFloat(self.data.bo_net_amount) * parseFloat(total_tax)) / 100));
                self.data.bo_amount = parseFloat(self.data.bo_net_amount) + self.data.bo_tax_amount;
            }else{
                self.data.bo_tax_amount = 0;
                self.data.bo_amount = parseFloat(self.data.bo_net_amount) + self.data.bo_tax_amount;
            }
            
            console.log('total_tax');
            console.log(total_tax);
            console.log('self.data.bo_amount');
            console.log(self.data.bo_amount);
            if(self.data.asp.tax_calculation_method == 0){
                self.data.bo_amount = parseFloat(self.data.bo_amount) + parseFloat(self.data.raw_bo_not_collected);
            }
            console.log('self.data.bo_amount');
            console.log(self.data.bo_amount);

        }

    }
});