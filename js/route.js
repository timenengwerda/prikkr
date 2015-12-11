var app = angular.module('testApp');
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




app.config(['$routeProvider', function($routeProvider) {
	$routeProvider.
	when('/new', {
		templateUrl: 'partials/form.html',
		controller: 'NavigationController',
		activeTab: 2
	}).
	when('/event/edit/:eventCode/:userCode', {
		templateUrl: 'partials/form.html'
	}).
	when('/event/overview/:eventCode/:userCode', {
		templateUrl: 'partials/overview.html',
		controller: 'EventOverviewController'
	}).
	when('/event/:eventCode/:userCode', {
		templateUrl: 'partials/event_detail.html',
		controller: 'EventDetailController'
	}).
	otherwise({
		redirectTo: '/',
		controller: 'NavigationController',
		activeTab: 1
	});
}]);