app.controller('EventDetailController', ['$scope', '$http', '$routeParams', function ($scope, $http, $routeParams) {
	$scope.eventCode = $routeParams.eventCode;

	if ($routeParams.userCode) {
		$scope.userCode = $routeParams.userCode;
	}

	$scope.hasResult = false;
	

	$scope.creator_email = '';
	$scope.creator_name = '';
	$scope.name = '';
	$scope.description = '';
	$scope.creation_date = '';
	$scope.creation_time = '';
	$scope.dates = [];


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
					if (data.data[i].dates) {
						for (var j in data.data[i].dates) {
							var theDate = data.data[i].dates[j];
							$scope.dates.push({
								date: theDate
							});
						}
						
					}
					
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

	$scope.voteForDate = function (dateIndex, choice) {
		/*
		choices:
		1: yes
		2: no
		3: maybe
		0: no choice (Primary state in DB)
		*/
		console.log()
	}

	if ($scope.eventCode) {
		$scope.getEvent($scope.eventCode);
	}


}]);