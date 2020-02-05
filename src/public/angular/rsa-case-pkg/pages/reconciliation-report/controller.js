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
            console.log(response);

            // /* Chart */
            // Highcharts.chart('chart1', {

            //     chart: {
            //         type: 'column'
            //     },

            //     title: {
            //         text: ''
            //     },

            //     xAxis: {
            //         categories: months,
            //     },

            //     yAxis: {
            //         allowDecimals: false,
            //         min: 0,
            //         title: {
            //             text: 'Amounts'
            //         }
            //     },

            //     tooltip: {
            //         formatter: function() {
            //             return '<b>' + this.x + '</b><br/>' +
            //                 this.series.name + ': ' + this.y + '<br/>' +
            //                 'Total: ' + this.point.stackTotal;
            //         }
            //     },

            //     plotOptions: {
            //         column: {
            //             stacking: 'normal',
            //             pointWidth: 30
            //         }
            //     },

            //     series: [{
            //         name: '',
            //         data: amounts1,
            //         stack: '',
            //         color: '#fa8c46'
            //     }, {
            //         name: '',
            //         data: amounts2,
            //         stack: '',
            //         color: '#216ff2'

            //     }]
            // });

            //  Chart 2 
            // Highcharts.chart('chart2', {

            //     chart: {
            //         type: 'column'
            //     },

            //     title: {
            //         text: ''
            //     },

            //     xAxis: {
            //         categories: months,
            //     },

            //     yAxis: {
            //         allowDecimals: false,
            //         min: 0,
            //         title: {
            //             text: 'Counts'
            //         }
            //     },

            //     tooltip: {
            //         formatter: function() {
            //             return '<b>' + this.x + '</b><br/>' +
            //                 this.series.name + ': ' + this.y + '<br/>' +
            //                 'Total: ' + this.point.stackTotal;
            //         }
            //     },

            //     plotOptions: {
            //         column: {
            //             stacking: 'normal',
            //             pointWidth: 30
            //         }
            //     },

            //     series: [{
            //         name: '',
            //         data: counts1, //[5, 3, 4, 7, 2],
            //         stack: '',
            //         color: '#fa8c46'
            //     }, {
            //         name: '',
            //         data: counts2, //[3, 4, 4, 2, 5],
            //         stack: '',
            //         color: '#216ff2'

            //     }]
            // });

            $rootScope.loading = false;

        });

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------