'use strict';

/* App Module */
var app = angular.module("app", ["xeditable"]);
var timeZonesApp = angular.module('timeZonesApp', [
  'ngRoute',
  'ngCookies',
  'appControllers',
  'appFilters',
  'appServices',
  'xeditable'
]);

timeZonesApp.config(['$routeProvider',
  function($routeProvider) {
    $routeProvider.
      when('/timezones', {
        templateUrl: 'partials/timezones.html',
        controller: 'timezonesCtrl'
      }).
      when('/login', {
        templateUrl: 'partials/login.html',
        controller: 'loginCtrl'
      }).
      when('/register', {
        templateUrl: 'partials/register.html',
        controller: 'loginCtrl'
      }).
      otherwise({
        redirectTo: '/login'
      });
  }
]);

timeZonesApp.run(['$rootScope', '$location', '$cookieStore', '$http', 'editableOptions',
  function($rootScope, $location, $cookieStore, $http, editableOptions) {
      // keep user logged in after page refresh      

      $rootScope.globals = $cookieStore.get('globals') || {};
      if ($rootScope.globals.currentUser) {          
          $http.defaults.headers.common['Authorization'] = $rootScope.globals.currentUser.authdata; 
      }

      $rootScope.$on('$locationChangeStart', function (event, next, current) {
          // redirect to login page if not logged in and trying to access a restricted page
          var restrictedPage = $.inArray($location.path(), ['/login', '/register']) === -1;
          var loggedIn = $rootScope.globals.currentUser;
          if (restrictedPage && !loggedIn) {
              $location.path('/login');
          }
      });

      editableOptions.theme = 'bs3';

      /*Toastr*/
      toastr.options = {
        "closeButton": false,
        "debug": false,
        "newestOnTop": false,
        "progressBar": false,
        "positionClass": "toast-top-center",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "1000",
        "hideDuration": "1000",
        "timeOut": "2000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
      };      

  }
]);