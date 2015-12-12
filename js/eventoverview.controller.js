
app.controller('EventOverviewController', ['$scope', '$http', '$routeParams', '$location', '$sce',
function ($scope, $http, $routeParams, $location, $sce) {
	$scope.eventCode = $routeParams.eventCode;
	$scope.userCode = $routeParams.userCode;

	$scope.hasResult = false;
	$scope.dates = [];
	$scope.isCreator = 0;
	$scope.totals = null;
	$scope.users = [];

	$scope.allUniqueDates = [];
	$scope.checkPlausibleDate = function (data) {
		console.log(data);

		for (var i in data) {
			var user = data[i].user;
			var dates = data[i].dates;
			//Collect unique dates
			for (var j in dates) {
				/*if ($scope.allUniqueDates.indexOf(dates[i].date) == -1) {
					$scope.allUniqueDates.push(dates[i].date);
				}*/
				if (!$scope.allUniqueDates[dates[j].date]) {
					$scope.allUniqueDates[dates[j].date] = [];
				}
				
				$scope.allUniqueDates[dates[j].date].push(dates[j].choice.choice);
			}
		}

		var newArray = [];

		var yesVotes = 0;
		var noVotes = 0;
		var maybeVotes = 0;
		var unvoted = 0;
		for (var date in $scope.allUniqueDates) {
			var choices = $scope.allUniqueDates[date];

			//Assume there are only yes votes unless the switch states otherwise
			var onlyYesAndMaybeVotes = true;
			for (var j in choices) {
				var choice = choices[j];
				$scope.getChoiceString (choice);

				switch (choice) {
					case '1':
						yesVotes++;
						break;
					case '2':
						noVotes++;
						onlyYesAndMaybeVotes = false;
						break;
					case '3':
						maybeVotes++;
						break;
					default:
						unvoted++;

						onlyYesAndMaybeVotes = false;
						break;
				}
			}

			if (onlyYesAndMaybeVotes) {
				var text = $sce.trustAsHtml('Ja (' + yesVotes + ')<br>\
				Misschien (' + maybeVotes + ')<br>');
				newArray.push({
					formatted: date,
					text: text 
				});	
			}

		}
		
		return newArray;
		
	}

	$scope.editEvent = function (e) {
		e.preventDefault();
		if ($scope.isCreator && $scope.userCode) {
			console.log('event/edit/' + $scope.eventCode +'/' + $scope.userCode);
			$location.path('event/edit/' + $scope.eventCode +'/' + $scope.userCode);
		}
	}

	$scope.getChoiceString = function (choice) {
		var choiceString = 'Geen keuze';
		switch (choice) {
			case '1':
				choiceString = 'Ja';
				
				break;
			case '2':
				choiceString = 'Nee';
				
				break;
			case '3':
				choiceString = 'Misschien';
				
				break;
		}
		return choiceString;
	}

	$scope.getEvent = function (code) {
		$http({
			method  : 'POST',
			url     : url + 'api/get_event_overview.php',
			data    : {code: code, userCode: $scope.userCode}, 
		}).success(function (data, status, headers) {
			console.log(data);
			if (data && data.result && data.data) {
				$scope.isCreator = data.isCreator;
				/*if ($scope.isCreator != 1) {
					$location.path('/');
				} else {*/


					for (var i in data.data) {
						
						var newDates = [];
						for (var j in data.data[i].dates) {
							

							var choiceString = $scope.getChoiceString (data.data[i].dates[j].choice.choice);

							var theDate = data.data[i].dates[j].timestamp;
							theDate = new Date(theDate*1000);
							theDateString = theDate.getDate() +'-'+(theDate.getMonth()+1) +'-'+theDate.getFullYear();
							newDates.push({
								timestamp: data.data[i].dates[j].timestamp,
								formatted: theDateString,
								choice: choiceString
							});
						}
						$scope.users.push({
							userData: data.data[i].user,
							dates: newDates
						});

						
					//}
				}
				var plausibleDate = $scope.checkPlausibleDate(data.data);

				
				$scope.totals = {
					plausibleDates: plausibleDate
				}

				//$scope.plausibleDatesString = plausibleDate;

				$scope.hasResult = true;
			} else {
				console.log('voldoet niet');
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