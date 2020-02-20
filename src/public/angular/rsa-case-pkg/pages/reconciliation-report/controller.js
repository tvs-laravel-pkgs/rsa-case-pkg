app.component('reconciliationReportView', {
    templateUrl: reconciliation_report_list_template_url,
    controller: function($http, $window, HelperService, $scope, $rootScope, $mdSelect) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.csrf = token;
        $http.get(
            laravel_routes['getReconciliationReport']
        ).then(function(response) {
            // console.log(response);
            self.extras = response.data.extras;
            self.monthes = response.data.monthes;
            self.data = response.data.month_wise_data;
            var d = new Date();
            var Curr_Year = d.getFullYear();
            self.current_year = Curr_Year;
            var num_month = d.getMonth();
            var months = [];
            var all_months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

            for (var i = 0; i <= num_month; i++) {
                months[i] = all_months[i];
            }

            var amounts1 = [];
            var amounts2 = [];
            angular.forEach(self.extras.total_amount_submit_in_year_chart, function(value, key) {
                amounts1[key] = value;
            });
            angular.forEach(self.extras.amount_of_bills_yet_to_receive_chart, function(value, key) {
                amounts2[key] = value;
            });
            for (var i in months) {
                var month = months[i];
                // console.log(month);
                if (typeof(amounts1[month]) == 'undefined') {
                    amounts1[i] = 0;
                } else {
                    amounts1[i] = amounts1[month];
                }
                // console.log(amounts1);
                if (typeof(amounts2[month]) == 'undefined') {
                    amounts2[i] = 0;
                } else {
                    amounts2[i] = amounts2[month];
                }
            }

            // /* Chart */
            Highcharts.chart('chart1', {
                chart: {
                    type: 'column'
                },
                title: {
                    text: ''
                },
                xAxis: {
                    categories: months,
                },
                yAxis: {
                    allowDecimals: false,
                    min: 0,
                    title: {
                        text: 'Amounts'
                    }
                },
                tooltip: {
                    formatter: function() {
                        return '<b>' + this.x + '</b><br/>' +
                            this.series.name + ': ' + this.y + '<br/>' +
                            'Total: ' + this.point.stackTotal;
                    }
                },
                plotOptions: {
                    column: {
                        stacking: 'normal',
                        pointWidth: 30
                    }
                },
                series: [{
                    name: '',
                    data: amounts1,
                    stack: '',
                    color: '#fa8c46'
                }, {
                    name: '',
                    data: amounts2,
                    stack: '',
                    color: '#216ff2'

                }]
            });

            var counts1 = [];
            var counts2 = [];
            angular.forEach(self.extras.total_count_submit_in_year_chart, function(value, key) {
                counts1[key] = value;
            });
            angular.forEach(self.extras.total_count_yet_to_receive_in_year_chart, function(value, key) {
                counts2[key] = value;
            });
            for (var i in months) {
                var month = months[i];
                // console.log(month);
                if (typeof(counts1[month]) == 'undefined') {
                    counts1[i] = 0;
                } else {
                    counts1[i] = counts1[month];
                }
                // console.log(counts1);
                if (typeof(counts2[month]) == 'undefined') {
                    counts2[i] = 0;
                } else {
                    counts2[i] = counts2[month];
                }
            }

            // Chart 2
            Highcharts.chart('chart2', {
                chart: {
                    type: 'column'
                },
                title: {
                    text: ''
                },
                xAxis: {
                    categories: months,
                },
                yAxis: {
                    allowDecimals: false,
                    min: 0,
                    title: {
                        text: 'Counts'
                    }
                },
                tooltip: {
                    formatter: function() {
                        return '<b>' + this.x + '</b><br/>' +
                            this.series.name + ': ' + this.y + '<br/>' +
                            'Total: ' + this.point.stackTotal;
                    }
                },
                plotOptions: {
                    column: {
                        stacking: 'normal',
                        pointWidth: 30
                    }
                },
                series: [{
                    name: '',
                    data: counts1, //[5, 3, 4, 7, 2],
                    stack: '',
                    color: '#fa8c46'
                }, {
                    name: '',
                    data: counts2, //[3, 4, 4, 2, 5],
                    stack: '',
                    color: '#216ff2'

                }]
            });

            $rootScope.loading = false;

        });

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------