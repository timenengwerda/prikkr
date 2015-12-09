var url = 'http://localhost:8888/prikkr/';
(function () {
	var app = angular.module('testApp', ['ngRoute']);

	app.directive('jqdatepicker', function () {
	    return {
	        restrict: 'A',
	        require: 'ngModel',
	        scope: {
	        	callback: '&dateupdate'
	        },
	         link: function (scope, element, attrs, ngModelCtrl) {
	            element.datepicker({
	                dateFormat: 'dd-mm-yy',
	                minDate: 0,
	                onSelect: function (date) {
	                   scope.callback({date: date});
	                }
	            });
	        }
	    };
	});

	app.directive('parentFocus', function() {
		return function(scope, elem, attr) {

			elem.on('focus', function() {
				console.log(13);
				$(this).parents('li').addClass('focus');
			});

			elem.on('blur', function() {
				$(this).parents('li').removeClass('focus');
			});

			// Removes bound events in the element itself
			// when the scope is destroyed
			scope.$on('$destroy', function() {
				elem.off('focus');
			});
		};
	});

	app.controller('NavigationController', ['$scope', '$route', function ($scope, $route) {
		$scope.$route = $route;
		this.activeTab = 1;

		this.setActiveTab = function (tab) {
			this.activeTab = tab;
		};

		this.isActive = function (tab) {
			if ($route.current && $route.current.activeTab) {
				return $route.current.activeTab === tab;
			}
			
			return false;
		};
	}]);

	app.controller('EventController', ['$scope', '$controller', '$http', function ($scope, $controller, $http) {
		//You need to supply a scope while instantiating.
		var EventController = $scope.$new();

		this.eventName = '';
		this.eventDescription = '';

		$scope.creator = [{name: '', id: '', email: ''}];

		$scope.users = [];
		$scope.users = [{id: '1', name: 'Timen', email: 'tengwerda@gmail.com'}];

		$scope.chosenDates = [{date: null}];

		$scope.addNewEmptyUser = function (e) {
			if (e) {
				e.preventDefault();
			}
			
			$scope.users.push($controller('UserController', {$scope : EventController }));
		};


		this.addNewDate = function (date, ind) {
			//Add the date to the earlier-created date object
			$scope.chosenDates[ind] = {date: date};

			//create a new date object for the next pass
			$scope.chosenDates.push({date: null});
			$scope.$apply();
		};

		this.removeNewDate = function (e, ind) {
			e.preventDefault();
			$scope.chosenDates.splice(ind, 1);
		};

		$scope.atleastOneDateFilled = function () {
			console.log(123);
			return false;
		}

		this.addEvent = function (e) {
			e.preventDefault();

			
			if ($scope.newEvent.$valid) {
				var data = {
					name: this.eventName,
					description: this.eventDescription,
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
				})
				.error(function (data, status, header) {
					console.error("Data: " + data +
					"<hr />status: " + status +
					"<hr />headers: " + header);

				});
			}

		};

		this.removeNewUser = function (e, ind) {
			e.preventDefault();
			$scope.users.splice(ind, 1);
		};

		$scope.isUserFilled = function (user) {
			if ($scope.users[user].isUserFilled()) {
				console.log($scope.users[$scope.users.length-1].isUserFilled());
				//If the last record is a filled record, add a new empty one.
				if ($scope.users[$scope.users.length-1].isUserFilled()) {
					$scope.addNewEmptyUser();
				}
				
			}
		};

		$scope.addNewEmptyUser();

	}]);

	app.controller('UserController', ['$scope', '$controller', function ($scope, $controller) {
		$scope.id = null;
		$scope.name = null;
		$scope.email = null;

		this.isUserFilled = function () {
			if ((this.name != undefined && this.email != undefined) && this.name != "" && this.email != "") {
				return true;
			}

			return false;
		};
	}]);

	app.config(['$routeProvider', function($routeProvider) {
		$routeProvider.
		when('/new', {
			templateUrl: 'partials/form.html',
			controller: 'NavigationController',
			activeTab: 2
		}).
		when('/another', {
			templateUrl: 'partials/another.html',
			controller: 'NavigationController',
			activeTab: 3
		}).
		otherwise({
			redirectTo: '/',
			controller: 'NavigationController',
			activeTab: 1
		});
	}]);


})();