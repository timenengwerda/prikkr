app.controller('EventDetailController', ['$scope', '$http', '$routeParams', function ($scope, $http, $routeParams) {
	$scope.eventCode = $routeParams.eventCode;

	$scope.getEvent = function (code) {
		$http({
			method  : 'POST',
			url     : url + 'api/get_event.php',
			data    : {code: code}, 
		}).success(function (data, status, headers) {
			if (data && data.result && data.data.length > 0) {
				for (var i in data.data) {
					$scope.creator_email = data.data[i].creator_email;
					$scope.creator_name = data.data[i].creator_name;
					$scope.name = data.data[i].name;
					$scope.description = data.data[i].description;
					$scope.creation_date = data.data[i].creation_date;
					$scope.creation_time = data.data[i].creation_time;
				}
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