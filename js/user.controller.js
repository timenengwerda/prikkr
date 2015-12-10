
var app = angular.module('testApp');
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
