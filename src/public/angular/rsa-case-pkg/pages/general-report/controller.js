app.component('generalReportView', {
    templateUrl: general_report_list_template_url,
    controller: function($http, $window, HelperService, $scope, $rootScope, $mdSelect) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.csrf = token;
        $http.get(
            laravel_routes['getGeneralReport']
        ).then(function(response) {
            // console.log(response);
            self.general = response.data.general;

            /* ------- Chart Main ------------*/
            var d = new Date();
            var Curr_Year = d.getFullYear();
            var num_month = d.getMonth();
            var months = [];
            var all_months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            for (var i = 0; i <= num_month; i++) {
                months[i] = all_months[i];
            }

            var counts = [];
            angular.forEach(self.general.general_report_ticket_count, function(value, key) {
                counts[key] = value;
            });

            for (var i in months) {
                var month = months[i];
                if (typeof(counts[month]) == 'undefined') {
                    counts[i] = 0;
                } else {
                    counts[i] = counts[month];
                }
            }
            Highcharts.chart('report_chart', {
                chart: {
                    type: 'spline'
                },
                title: {
                    text: self.general.general_report_ticket_count_year
                },
                subtitle: {
                    text: 'Total Tickets completed -' + Curr_Year,
                },
                xAxis: {
                    title: null,
                    categories: months,
                },
                yAxis: {
                    title: {
                        text: 'Ticket counts'

                    },
                    min: 0,
                    allowDecimals: false
                },
                /*tooltip: {
                    headerFormat: '<b>{series.name}</b><br>',
                    pointFormat: '{point.x:%e. %b}: {point.y:.2f} m'
                },*/
                plotOptions: {
                    spline: {
                        marker: {
                            enabled: true
                        }
                    }
                },
                series: [{
                    name: Curr_Year,
                    data: counts, //[1, 10, 800, 1600, 1500, 1600, 1200, 1400, 1900,20,30,22],
                    color: '#ffc72d',

                }]
            });
            /* ------- Chart Main ------------*/

            /* ---------- Swipper ---------- */
            setTimeout(function() {
                var swiper = new Swiper('.swiper-container', {
                    slidesPerView: 4,
                    spaceBetween: 10,
                    freeMode: true,
                    navigation: {
                        nextEl: '.swiper-button-next',
                        prevEl: '.swiper-button-prev',
                    },
                    breakpoints: {
                        1024: {
                            slidesPerView: 3,
                            spaceBetween: 20,
                        },
                        768: {
                            slidesPerView: 2,
                            spaceBetween: 30,
                        },
                        640: {
                            slidesPerView: 1,
                            spaceBetween: 15,
                        },
                        320: {
                            slidesPerView: 1,
                            spaceBetween: 10,
                        }
                    }
                });

                /* Custom Scroller */
                (function($) {
                    $(window).on("load", function() {
                        $("#content-1").mCustomScrollbar({
                            theme: "minimal-dark",
                            mouseWheelPixels: 50
                        });
                    });
                })(jQuery);
            }, 500);
            /* ---------- Swipper ---------- */

            $rootScope.loading = false;
        });
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('generalReportAsp', {
    templateUrl: general_report_asp_wise_template_url,
    controller: function($http, $window, HelperService, $scope, $rootScope, $mdSelect) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        var cols = [
            { data: "asp_code", name: 'asps.asp_code' },
            { data: "asp_name", name: 'asps.name' },
            { data: "asp_type", name: 'asps.is_self' },
            { data: "city_name", name: 'districts.name' },
            { data: "state_name", name: 'states.name' },
            { data: "ticket_count", name: 'ticket_count', searchable: false },
            { data: "collected_from_customer", name: 'asp_collected_amt.value' },
            { data: "invoice_amount", name: 'activity_details.value', searchable: false }
        ];
        var asp_payment_list_dt_config = JSON.parse(JSON.stringify(dt_config));

        $('#asp_payment_summary').DataTable(
            $.extend(asp_payment_list_dt_config, {
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
                    url: laravel_routes['getAspPaymentList'],
                    data: function(d) {}
                },
                infoCallback: function(settings, start, end, max, total, pre) {
                    $('.count').html(total + ' / ' + max + ' listings')
                },
                initComplete: function() {},
            })
        );
        $('.dataTables_length select').select2();

        var dataTable = $('#asp_payment_summary').dataTable();
        $scope.refresh = function() {
            dataTable.DataTable().ajax.reload();
        };

        $(".filterTable").keyup(function() {
            dataTable.fnFilter(this.value);
        });

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('generalReportCity', {
    templateUrl: general_report_city_wise_template_url,
    controller: function($http, $window, HelperService, $scope, $rootScope, $mdSelect, $routeParams) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.csrf = token;
        $http.get(
            laravel_routes['getCityPaymentList']
            // , {
            //     params: {
            //         name: $routeParams.city_name,
            //     }
            // }
        ).then(function(response) {
            // console.log(response.data.city);
            self.city = response.data.city;

            $rootScope.loading = false;
        });
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('generalReportState', {
    templateUrl: general_report_state_wise_template_url,
    controller: function($http, $window, HelperService, $scope, $rootScope, $mdSelect, $routeParams) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.csrf = token;
        $http.get(
            laravel_routes['getStatePaymentList']
            // , {
            //     params: {
            //         name: $routeParams.state_name,
            //     }
            // }
        ).then(function(response) {
            // console.log(response.data.state);
            self.state = response.data.state;

            $rootScope.loading = false;
        });
    }
});