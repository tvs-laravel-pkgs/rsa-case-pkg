app.component('activityVerificationList', {
    templateUrl: activity_verification_list_template_url,
    controller: function($http, $window, HelperService, $scope, $rootScope, $route) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        self.filter_img_url = filter_img_url;
        $http.get(
            activity_status_filter_url
        ).then(function(response) {
            self.extras = response.data.extras;

            var cols = [
                { data: 'action', searchable: false },
                { data: 'case_date', searchable: false },
                { data: 'number', name: 'cases.number', searchable: true },
                { data: 'asp_code', name: 'asps.asp_code', searchable: true },
                { data: 'crm_activity_id', searchable: false },
                { data: 'sub_service', name: 'service_types.name', searchable: true },
                { data: 'finance_status', name: 'activity_finance_statuses.name', searchable: true },
                // { data: 'asp_status', name: 'activity_asp_statuses.name', searchable: true },
                { data: 'status', name: 'activity_portal_statuses.name', searchable: true },
                { data: 'activity_status', name: 'activity_statuses.name', searchable: true },
                { data: 'client', name: 'clients.name', searchable: true },
                { data: 'call_center', name: 'call_centers.name', searchable: true },
            ];

            var activities_verification_dt_config = JSON.parse(JSON.stringify(dt_config));

            $('#activities_verification_table').DataTable(
                $.extend(activities_verification_dt_config, {
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
                        url: activity_verification_get_list_url,
                        data: function(d) {
                            d.ticket_date = $('#ticket_date').val();
                            d.call_center_id = $('#call_center_id').val();
                            d.case_number = $('#case_number').val();
                            d.asp_code = $('#asp_code').val();
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

            var dataTable = $('#activities_verification_table').dataTable();

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
                $('#activities_verification_table').DataTable().ajax.reload();
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
            $rootScope.loading = false;
        });

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('activityVerificationView', {
    templateUrl: activity_status_view_template_url,
    controller: function($http, $location, $window, HelperService, $scope, $routeParams, $rootScope, $location) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.filter_img_url = filter_img_url;
       self.style_dot_image_url = style_dot_image_url;
        self.csrf = $('#csrf').val();
        
        get_view_data_url = typeof($routeParams.id) == 'undefined' ? activity_status_view_data_url : activity_status_view_data_url + '/' + $routeParams.id;
        $http.get(
            get_view_data_url
        ).then(function(response) {
            console.log(response.data.data.activities);
            self.data = response.data.data.activities;
            self.data.style_dot_image_url = style_dot_image_url;
            self.data.style_service_type_image_url = style_service_type_image_url;
            self.data.style_car_image_url = style_car_image_url;
            self.data.style_location_image_url = style_location_image_url;
            self.data.style_profile_image_url = style_profile_image_url;
            self.data.style_phone_image_url = style_car_image_url;
            self.data.style_modal_close_image_url = style_modal_close_image_url;
            self.data.style_question_image_url = style_question_image_url;
            self.data.verification = 1;
            
                $('.viewData-toggle--inner.noToggle .viewData-threeColumn--wrapper').slideDown();   
                $('.viewData-toggle--btn').click(function(){
                    $(this).toggleClass('viewData-toggle--btn_reverse');
                    $('.viewData-toggle--inner .viewData-threeColumn--wrapper').slideToggle();
                });
            $rootScope.loading = false;

            
        self.data.cc_net_amount = self.data.cc_po_amount - self.data.bo_not_collected;
        
       $scope.differ = function(){
        alert('sss');
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
        });
        
    }
    
});