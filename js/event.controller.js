
var app = angular.module('testApp');
app.controller('EventController', ['$scope', '$controller', '$http', '$location', 
function ($scope, $controller, $http, $location) {
	//You need to supply a scope while instantiating.
	var EventController = $scope.$new();

	$scope.eventName = '';
	$scope.eventDescription = '';

	$scope.creator = [{name: '', id: '', email: ''}];

	$scope.users = [];
	$scope.isSaving = false;

	$scope.chosenDates = [{date: null}];


	$scope.addNewEmptyUser = function (e) {
		if (e) {
			e.preventDefault();
		}
		
		$scope.users.push($controller('UserController', {$scope : EventController }));
	};

	$scope.addNewDate = function (date, ind) {
		//Add the date to the earlier-created date object
		$scope.chosenDates[ind] = {date: date};

		//create a new date object for the next pass
		$scope.chosenDates.push({date: null});
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

			var data = {
				name: $scope.eventName,
				description: $scope.eventDescription,
				creator_name: $scope.creator.name,
				creator_email: $scope.creator.email,
				users: $scope.users,
				dates: $scope.chosenDates

			};


			$http({
				method  : 'POST',
				url     : url + 'api/new_event.php',
				data    : data, 
			}).success(function (data, status, headers) {
				console.log(data);
				if (data.result) {
					if (data[0].code && data[0].creator_code) {
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

	$scope.addNewEmptyUser();

}]);
