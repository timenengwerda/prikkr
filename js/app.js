var url = '/prikkr/';
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

