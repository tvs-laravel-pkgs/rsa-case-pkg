app.component('dashboard', {
    templateUrl: dashboard_template_url,
    controller: function($http, $window, HelperService, $scope, $rootScope, $mdSelect) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('admin-dashboard') && !self.hasPermission('bo-dashboard') && !self.hasPermission('finance-dashboard') && !self.hasPermission('rm-dashboard')) {
            window.location = "#!/page-permission-denied";
            return false;
        }

        self.filter_img_url = filter_img_url;
        $http.get(
            dashboard_data_url
        ).then(function(response) {
            if (!response.data.success) {
                var errors = '';
                for (var i in response.data.errors) {
                    errors += '<li>' + response.data.errors[i] + '</li>';
                }
                $noty = new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: errors,
                    animation: {
                        speed: 500 // unavailable - no need
                    },

                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 1000);
                $location.path('/rsa-case-pkg/activity-status/list');
                $scope.$apply();
                return;
            }
            self.data = response.data.data;
            console.log(self.data);
            self.data.dash1 = dash1;
            self.data.dash2 = dash2;
            self.data.dash3 = dash3;
            self.data.dash4 = dash4;
            console.log(self.data);
            console.log(self.data.role);
            if (self.data.role_name == 'super-admin' || self.data.role_name == 'rm' || self.data.role_name == 'bo' || self.data.role_name == 'finance') {
                var d = new Date();
                var Curr_Year = d.getFullYear();
                var num_month = d.getMonth();
                var months = [];
                var all_months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                for (var i = 0; i <= num_month; i++) {
                    months[i] = all_months[i];
                }
                var amounts = [];
                angular.forEach(self.data.completed_ticket_count, function(value, key) {
                    amounts[key] = value;
                });

                for (var i in months) {

                    var month = months[i];
                    if (typeof(amounts[month]) == 'undefined') {
                        amounts[i] = 0;
                    } else {
                        amounts[i] = amounts[month];
                    }
                }
                Highcharts.chart('completed_ticket', {
                    chart: {
                        type: 'column',
                    },
                    credits: {
                        enabled: false
                    },
                    title: {
                        text: 'Completed Ticket Count',
                        align: 'left',
                        style: {
                            border: '1px solid red',
                        }
                    },
                    legend: {
                        enabled: false,
                    },
                    subtitle: null,
                    xAxis: {
                        categories: months, //['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                        crosshair: true
                    },
                    yAxis: {
                        min: 0,
                        title: null,
                        allowDecimals: false
                    },
                    tooltip: {
                        headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
                        pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                            '<td style="padding:0"><b>{point.y}</b></td></tr>',
                        footerFormat: '</table>',
                        shared: true,
                        useHTML: true
                    },
                    plotOptions: {
                        column: {
                            pointPadding: 0.2,
                            borderWidth: 0
                        },
                        series: {
                            pointWidth: 4,
                            color: {
                                linearGradient: { x1: 0, x2: 0, y1: 0, y2: 1 },
                                stops: [
                                    [0, '#003399'],
                                    [1, '#3366AA']
                                ]
                            }
                        }
                    },
                    series: [{
                        name: 'Completed Tickets',
                        data: amounts, //[49.9, 71.5, 106.4, 129.2, 144.0, 176.0, 135.6, 148.5, 216.4, 194.1, 95.6, 54.4],
                        color: '#ffcb2c',
                        lineWidth: 2
                    }]
                });

                var num_days_in_month = new Date("{{date('Y')}}", "{{date('m')}}", 0).getDate();
                //check number of days in month
                var current_day = new Date().getDate();
                var days = [];
                for (var i = 0; i < current_day; i++) {
                    days[i] = i + 1;
                }
                //assign values
                var amounts_day = [];
                var final_amounts_day = [];
                angular.forEach(self.data.payment_day, function(value, key) {
                    amounts_day[key] = value;
                });

                $scope.monthly = function() {
                    $("#yearly").hide();

                    $("#monthly").modal();
                }

                $scope.yearly = function() {
                    $("#monthly").hide();

                    $("#yearly").modal();
                }
                /*
                foreach(self.data.payment_day as $day => $pay){
                    amounts_day["{{$day}}"] = {{$pay}}
                }*/
                console.log('days');
                console.log(days);

                for (var i in days) {
                    var day = days[i];
                    if (typeof(amounts_day[day]) == 'undefined') {
                        final_amounts_day[i] = 0;
                    } else {
                        data_val = amounts_day[day];
                        final_amounts_day[i] = parseFloat(amounts_day[day]);
                    }
                }
                console.log('final_amounts_day');
                console.log(final_amounts_day);
                //var d = [12,200,300,500,12,200,300,500,12,200,300,500,12,200,300,500,12,200,300,500,12,200,300,500,12,200,300,500,12,200,300];
                //console.log(d); 
                Highcharts.chart('paymentChartMonthly', {
                    chart: {
                        type: 'line'
                    },
                    xAxis: {
                        title: null,
                        categories: days
                    },
                    title: {
                        text: 'Payment',
                        align: 'left',
                        x: 0
                    },
                    yAxis: {
                        min: 0,
                        title: null,
                        allowDecimals: false
                    },
                    legend: {
                        enabled: false
                    },
                    credits: {
                        enabled: false
                    },
                    series: [{
                        name: 'Monthly',
                        data: final_amounts_day, //[12,200,300,500,12,200,300,500,12,200,300,500,12,200,300,500,12,200,300,500,12,200,300,500,12,200,300,500,12,200,300],
                        color: '#ffc72d',
                    }]
                });


                var amounts = [];

                angular.forEach(self.data.payment, function(value, key) {
                    console.log('key');
                    console.log(key);
                    console.log(value);
                    amounts[key] = value;
                });
                console.log('amounts');
                console.log(amounts);
                console.log('months');
                console.log(months);
                for (var i in months) {
                    console.log(i);
                    var month = months[i];
                    if (typeof(amounts[month]) == 'undefined') {
                        amounts[i] = 0;
                    } else {
                        amounts[i] = parseFloat(amounts[month]);
                    }
                }
                console.log('amounts');
                console.log(amounts);

                Highcharts.chart('paymentChart', {
                    chart: {
                        type: 'line',
                    },
                    xAxis: {
                        title: null,
                        categories: months,
                    },

                    title: {
                        text: 'Payment',
                        align: 'left',
                        x: 0
                    },

                    yAxis: {
                        min: 0,
                        title: null,
                        allowDecimals: false
                    },


                    legend: {
                        enabled: false,
                        align: 'right',
                        verticalAlign: 'top',
                        backgroundColor: '#ff0',
                        navigation: {
                            activeColor: '#ffffff',
                            animation: true,
                            inactiveColor: '#f00',
                            backgroundColor: '#ff0'
                        },
                        itemStyle: {
                            fontSize: '18px'
                        }
                    },
                    credits: {
                        enabled: false
                    },

                    series: [{
                        name: 'Yearly',
                        data: amounts, //[1, 10, 800, 1600, 1500, 1600, 1200, 1400, 1900,20,30,22],
                        color: '#ffc72d',
                    }],

                    responsive: {
                        rules: [{
                            condition: {
                                maxWidth: 500
                            },
                            chartOptions: {
                                legend: {
                                    layout: 'horizontal',
                                    align: 'center',
                                    verticalAlign: 'bottom'
                                }
                            }
                        }]
                    }

                });

            }
        });
    }
});