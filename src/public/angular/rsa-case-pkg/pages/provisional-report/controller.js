app.component('provisionalReportView', {
    templateUrl: provisional_report_list_template_url,
    controller: function($http, $window, HelperService, $scope, $rootScope, $mdSelect) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.csrf = token;

        $http.get(
            laravel_routes['getProvisionalReport']
        ).then(function(response) {
            console.log(response.data);
            self.report = response.data.report;
            self.before_bo_validation_invoice_amount = response.data.before_bo_validation_invoice_amount;
            self.summary = response.data.summary;
            self.check_new_update = response.data.check_new_update;

            $(function() {
                $('#date_1').daterangepicker({
                    startDate: self.report.date_from,
                    endDate: self.report.date_to,
                    locale: {
                        format: 'DD/MM/YYYY'
                    },
                });
                $('#date_2').daterangepicker({
                    startDate: self.summary.date_from,
                    endDate: self.summary.date_to,
                    locale: {
                        format: 'DD/MM/YYYY'
                    },
                });
            });
            $rootScope.loading = false;
        });

        $("#date_1").on('change', function() {
            $http.get(
                laravel_routes['getReportBasedDate'], {
                    params: {
                        daterange: $("#date_1").val(),
                        id: 1, //FOR REPORT 
                    }
                }
            ).then(function(response) {
                self.report = response.data.report;
                self.before_bo_validation_invoice_amount = response.data.before_bo_validation_invoice_amount;
                // self.summary = response.data.summary;
                self.check_new_update = response.data.check_new_update;
            });
        });
        $("#date_2").on('change', function() {
            $http.get(
                laravel_routes['getReportBasedDate'], {
                    params: {
                        daterange: $("#date_2").val(),
                        id: 2, //FOR SUMMARY
                    }
                }
            ).then(function(response) {
                // self.report = response.data.report;
                self.before_bo_validation_invoice_amount = response.data.before_bo_validation_invoice_amount;
                self.summary = response.data.summary;
                self.check_new_update = response.data.check_new_update;
            });
        });

        $('input[name="period"]').daterangepicker({
            locale: {
                format: 'DD/MM/YYYY'
            },
            startDate: moment().startOf('month'),
            endDate: moment().endOf('month'),
        });

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------