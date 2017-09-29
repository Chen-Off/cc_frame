'use strict';

ngApp.controller('BatchAssign', ['$scope', '$http', '$sce', function($scope, $http, $sce) {
    $scope.authError = null;
    $scope.teamsList = $sce.trustAsHtml('<option value="">请选择操作组</option>');
    $scope.selectTeams = function() {
        $scope.teamsList = '';
        $scope.authError = null;
        if(!isNaN($scope.group)) {
            $http.post('app/CustomerMange/customer_assign/get_teams', {group: $scope.group})
                .then(function (response) {
                    if (response.data.status == 'error') {
                        $scope.authError = response.data.msg;
                    } else {
                        var teams = '<option value="">请选择操作组</option>';
                        angular.forEach(response.data.json, function(data){
                            teams += '<option value="' + data['id'] + '">' + data['name'] + '</option>';
                        });

                        $scope.teamsList = $sce.trustAsHtml(teams);
                    }
                }, function (x) {
                    $scope.authError = '服务器错误';
                });
        } else {
            $scope.authError = '非法提交';
        }

    };
}]);