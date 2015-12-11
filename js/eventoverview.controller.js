app.controller('EventOverviewController', ['$scope', '$http', '$routeParams', '$location',
function ($scope, $http, $routeParams, $location) {
	$scope.eventCode = $routeParams.eventCode;
	$scope.userCode = $routeParams.userCode;

	$scope.hasResult = false;

	$scope.isCreator = 0;

	$scope.getEvent = function (code) {
		$http({
			method  : 'POST',
			url     : url + 'api/get_event_overview.php',
			data    : {code: code, userCode: $scope.userCode}, 
		}).success(function (data, status, headers) {
			if (data && data.result && data.data.length > 0) {
				for (var i in data.data) {
					$scope.isCreator = data.data[i].isCreator;
					$scope.isCreator = data.data[i].isCreator;
					if ($scope.isCreator != 1) {
						$location.path('/');
					}
/*
					if (data.data[i].dates) {
						for (var j in data.data[i].dates) {
							var theDate = data.data[i].dates[j].date;
							$scope.dates.push({
								choiceId: data.data[i].dates[j].choice.choiceId,
								date: theDate,
								choice: data.data[i].dates[j].choice.choice,
								choiceLoading: false
							});
						}
						
					}*/
					
				}
				$scope.hasResult = true;
			}
		})
		.error(function (data, status, header) {
			console.error("Data: " + data +
			"<hr />status: " + status +
			"<hr />headers: " + header);
		});
	}

	if ($scope.eventCode) {
		$scope.getEvent($scope.eventCode);
	}


}]);