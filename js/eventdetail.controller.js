app.controller('EventDetailController', ['$scope', '$http', '$routeParams', '$location',
function ($scope, $http, $routeParams, $location) {
	$scope.eventCode = $routeParams.eventCode;
	$scope.userCode = $routeParams.userCode;

	$scope.hasResult = false;
	
	$scope.creator_email = '';
	$scope.creator_name = '';
	$scope.name = '';
	$scope.description = '';
	$scope.creation_date = '';
	$scope.creation_time = '';
	$scope.dates = [];

	$scope.isCreator = 0;

	$scope.getEvent = function (code) {
		$http({
			method  : 'POST',
			url     : url + 'api/get_event.php',
			data    : {code: code, userCode: $scope.userCode}, 
		}).success(function (data, status, headers) {
			if (data && data.result && data.data.length > 0) {
				for (var i in data.data) {
					$scope.isCreator = data.data[i].isCreator;
					$scope.creator_email = data.data[i].creator_email;
					$scope.creator_name = data.data[i].creator_name;
					$scope.name = data.data[i].name;
					$scope.description = data.data[i].description;
					$scope.creation_date = data.data[i].creation_date;
					$scope.creation_time = data.data[i].creation_time;
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

	$scope.editEvent = function (e) {
		e.preventDefault();
		if ($scope.isCreator && $scope.userCode) {
			console.log('event/edit/' + $scope.eventCode +'/' + $scope.userCode);
			$location.path('event/edit/' + $scope.eventCode +'/' + $scope.userCode);
		}
	}

	$scope.viewEvent = function (e) {
		e.preventDefault();
		if ($scope.isCreator && $scope.userCode) {
			console.log('event/overview/' + $scope.eventCode +'/' + $scope.userCode);
			$location.path('event/overview/' + $scope.eventCode +'/' + $scope.userCode);
		}
	}

	//viewEvent

	$scope.voteForDate = function (dateIndex, choice, choiceId) {
		/*
		choices:
		1: yes
		2: no
		3: maybe
		0: no choice (Primary state in DB)
		*/
		//
		//Dont save when its already saving. You can see that by checking choiceLoading bool
		if ($scope.dates[dateIndex].choiceLoading === false) {
			$scope.dates[dateIndex].choiceLoading = true;
			$http({
				method  : 'POST',
				url     : url + 'api/save_user_choice.php',
				data    : {choiceId: choiceId, choice: choice}, 
			}).success(function (data, status, headers) {
				$scope.dates[dateIndex].choiceLoading = false;
				$scope.dates[dateIndex].choice = choice;
			})
			.error(function (data, status, header) {
				console.error("Data: " + data +
				"<hr />status: " + status +
				"<hr />headers: " + header);
			});
		}
		
	}

	if ($scope.eventCode) {
		$scope.getEvent($scope.eventCode);
	}


}]);