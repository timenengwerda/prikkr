
var app = angular.module('testApp');
app.controller('EventController', ['$scope', '$controller', '$http', '$location', '$routeParams', 
function ($scope, $controller, $http, $location, $routeParams) {

	//You need to supply a scope while instantiating.
	var EventController = $scope.$new();

	$scope.eventName = '';
	$scope.eventDescription = '';

	$scope.creator = {name: '', id: '', email: ''};

	$scope.users = [];
	$scope.isSaving = false;

	$scope.chosenDates = [];
	$scope.isNewEvent = true;
	$scope.eventCode = null;
	


	$scope.addNewEmptyUser = function (e) {
		if (e) {
			e.preventDefault();
		}
		
		$scope.users.push($controller('UserController', {$scope : EventController }));
	};

	$scope.getExistingEvent = function (userCode, eventCode) {
		$http({
			method  : 'POST',
			url     : url + 'api/get_event.php',
			data    : {code: eventCode, userCode: $scope.userCode}, 
		}).success(function (data, status, headers) {
			if (data && data.result && data.data && data.data.length > 0) {
				for (var i in data.data) {
					$scope.isCreator = data.data[i].isCreator;
					if ($scope.isCreator != 1) {
						$location.path('/');
					}

					$scope.eventName = data.data[i].name;
					$scope.eventDescription = data.data[i].description;

					if (data.data[i].dates) {
						for (var j in data.data[i].dates) {
							var theDate = data.data[i].dates[j].timestamp;
							//convert timestamp to the dateformatting used in the date-field(dd-mm-yyyy)
							theDate = new Date(theDate*1000);
							theDateString = theDate.getDate() +'-'+(theDate.getMonth()+1) +'-'+theDate.getFullYear();
							var id = data.data[i].dates[j].dateId;

							$scope.chosenDates.push({id: id, date: theDateString});
						}
						//Add an empty field at the end of the existing dates
						$scope.chosenDates.push({date: null});
						
					}

					if (data.data[i].users) {
						for (var j in data.data[i].users) {
							var user = data.data[i].users[j];
							if (user) {
								if (user.is_creator == 1) {
									$scope.creator.name = user.name;
									$scope.creator.id = user.id;
									$scope.creator.email = user.email;

								} else {
									var newUser = $controller('UserController', {$scope : EventController });
									newUser.name = user.name;
									newUser.id = user.id;
									newUser.email = user.email;
									$scope.users.push(newUser);
								}
							}
							
						}
					}
					$scope.addNewEmptyUser();
					
				}
				$scope.hasResult = true;
			} else {
				$location.path('/');
			}
		})
		.error(function (data, status, header) {
			console.error("Data: " + data +
			"<hr />status: " + status +
			"<hr />headers: " + header);
		});
	}

	$scope.addNewDate = function (date, ind) {
		//If the current date being added wasnt there before, add a new record at the bottom
		if ($scope.chosenDates[ind].date == null) {
			//create a new date object for the next pass
			$scope.chosenDates.push({date: null});
		}
		//Add the date to the earlier-created date object
		$scope.chosenDates[ind].date = date;

		
		$scope.$apply();
	};

	$scope.removeNewDate = function (e, ind) {
		e.preventDefault();
		$scope.chosenDates.splice(ind, 1);
	};

	$scope.atleastOneDateFilled = function () {
		if ($scope.chosenDates.length > 0) {
			for (var i in $scope.chosenDates) {
				var dt = $scope.chosenDates[i];
				if (dt.date != "" && dt.date != null) {
					return true;
				}
			}
		}
		return false;
	}

	$scope.atleastOneUserFilled = function () {
		if ($scope.users.length > 0) {
			for (var i in $scope.users) {
				var user = $scope.users[i];
				if (user.isUserFilled(i)) {
					return true;
				}
			}
		}
		return false;
	}

	$scope.formIsValid = function () {
		return true;
		if ($scope.atleastOneDateFilled()
			&& $scope.atleastOneUserFilled()
			&& $scope.newEvent.$valid) {
				return true;
			}
		return false;
	}

	$scope.addEvent = function (e) {
		e.preventDefault();
		
		if ($scope.formIsValid) {
			$scope.isSaving = true;
			var creatorId = ($scope.creator.id) ? $scope.creator.id  : null;
			var data = {
				eventCode: $scope.eventCode,
				name: $scope.eventName,
				description: $scope.eventDescription,
				creator_name: $scope.creator.name,
				creator_email: $scope.creator.email,
				users: $scope.users,
				dates: $scope.chosenDates,
				creatorId: creatorId
			};

			var fileToNavigateTo = ($scope.isNewEvent) ? 'new_event.php' : 'edit_event.php';

			console.log(data);
			$http({
				method  : 'POST',
				url     : url + 'api/' + fileToNavigateTo,
				data    : data, 
			}).success(function (data, status, headers) {
				console.log(data);
				if (data.result) {
					if (data[0] && data[0].code && data[0].creator_code) {
						$location.path('event/' + data[0].code +'/'+data[0].creator_code);
					}
				}
				$scope.isSaving = false;
			})
			.error(function (data, status, header) {
				console.error("Data: " + data +
				"<hr />status: " + status +
				"<hr />headers: " + header);
				$scope.isSaving = false;
			});
			
		}

	};

	$scope.removeNewUser = function (e, ind) {
		e.preventDefault();
		$scope.users.splice(ind, 1);
	};

	$scope.isUserFilled = function (user) {
		if ($scope.users[user].isUserFilled()) {
			//If the last record is a filled record, add a new empty one.
			if ($scope.users[$scope.users.length-1].isUserFilled()) {
				$scope.addNewEmptyUser();
			}
			
		}
	};

	if ($routeParams.userCode && $routeParams.eventCode) {
		//Its an edit form!
		$scope.isNewEvent = false;

		$scope.userCode = $routeParams.userCode;
		$scope.eventCode = $routeParams.eventCode;

		$scope.getExistingEvent($scope.userCode, $scope.eventCode);
	} else {
		$scope.addNewEmptyUser();
		$scope.chosenDates = [{date: null}];
	}
	

}]);
