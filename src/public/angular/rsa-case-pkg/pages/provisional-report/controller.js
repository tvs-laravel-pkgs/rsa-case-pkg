app.component('provisionalReportView', {
    templateUrl: provisional_report_list_template_url,
    controller: function($http, $window, HelperService, $scope, $rootScope, $mdSelect, $element) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.csrf = token;
        self.export_provisional_report = laravel_routes['exportProvisionalReport'];
        $http.get(
            laravel_routes['getProvisionalReport']
        ).then(function(response) {
            console.log(response.data);
            self.report = response.data.report;
            self.service_type_list = response.data.services_type_list;
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

        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        $scope.clearSearchTerm = function() {
            $scope.searchTerm = '';
        };
        /* Modal Md Select Hide */
        $('.modal').bind('click', function(event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });

        $scope.selectAll = function(val) {
            self.pc_all = (!self.pc_all);
            if (!val) {
                r_list = [];
                angular.forEach(self.service_type_list, function(value, key) {
                    r_list.push(value.id);
                });
                $('#pc_sel_all').addClass('pc_sel_all');
            } else {
                r_list = [];
                $('#pc_sel_all').removeClass('pc_sel_all');
            }
            self.service_ids = r_list;
        }
        $scope.changeService = function(ids) {
            if (ids.length == self.service_type_list.length) {
                $('#pc_sel_all').addClass('pc_sel_all');
            } else {
                $('#pc_sel_all').removeClass('pc_sel_all');
            }
        }

        $('input[name="period"]').daterangepicker({
            locale: {
                format: 'DD/MM/YYYY'
            },
            startDate: moment().startOf('month'),
            endDate: moment().endOf('month'),
        });

        $(".select2").addClass('ng-hide');

        $("form[name='export_provisional_form']").validate({
            ignore: '',
            rules: {
                period: {
                    required: true,
                },
                services_type_id: {
                    required: true,
                },
            },
            messages: {
                period: "Please Choose Date",
                services_type_id: 'Please Select Service Type',
            },
            submitHandler: function(form) {
                console.log(form);
                form.submit();
            }
        });


    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------