'use strict';

/* Controllers */
var appControllers = angular.module('appControllers', []);

appControllers.controller('loginCtrl', ['$rootScope', '$scope', '$location', 'AuthenticationService', 
  function($rootScope, $scope, $location, AuthenticationService) {

        (function initController() {
            // reset login status
            console.log('initController');
            AuthenticationService.ClearCredentials();
        })();
 
        $scope.loginRegister = function(doAction) {            
            $scope.dataLoading = true;
            // doAction = 'login | register'
            AuthenticationService.LoginRegister($scope.user, doAction, function(response) {
                //To be executed after Authentication
                $scope.dataLoading = false;
                if (!response.error) {
                    AuthenticationService.SetCredentials(response);                    
                    $location.path('/timezones');
                    //toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            });
        };                

  }
]);   

appControllers.controller('timezonesCtrl', ['$rootScope', '$scope', 'srvTimeZones', 'CardService', '$firebaseObject', '$firebaseArray',
  function($rootScope, $scope, srvTimeZones, CardService, $firebaseObject, $firebaseArray) {
    // Get the TimeZones list
    // Custom Web Service
    //$rootScope.$digest();

    $scope.tzObject = srvTimeZones.query();
    
    // Firebase for TimeZones DB
    //var ref = new Firebase("https://dazzling-inferno-7826.firebaseio.com/timezonesDB/");
    //$scope.tzObject = $firebaseArray(ref);

    // Firebase for Cards DB
    //var ref = new Firebase("https://dazzling-inferno-7826.firebaseio.com/test/");
    //$scope.okTimeZones = $firebaseArray(ref);
    
    CardService.GetUserCards(function(response) {
      if (!response.error) {
        $scope.okTimeZones = angular.copy(response.cards);        
        toastr.success(response.message);
        console.log(response);        
      } else {
        $scope.okTimeZonesWS = {};
        toastr.error(response.message);        
      }
    });

    $scope.btnDisabled = true;    

    // Default values
    var UTCoffset = (new Date().getTimezoneOffset()) / 60 *-1;      
    var timeZone = {'offset':UTCoffset, 'text': '', 'value': ''};
    $scope.myTimeZone = timeZone;

    // Executed when a new value is selected on the list
    $scope.tzlistChange = function() {        
      $scope.btnDisabled = false;
      //$scope.myTimeZone.tzId = $scope.tzId;
      //console.log('Select');
    };    

    // Executed when the Add button is pressed
    $scope.tzAdd = function() {
      //$scope.myTimeZone.tzId = tzId;      

      // With Firebase we use $add, not push      
      //$scope.okTimeZones.$add(angular.copy($scope.myTimeZone));
      
      CardService.CreateCard($scope.myTimeZone, function(response) {
        if (!response.error) {
          $scope.myTimeZone.id = response.id;
          $scope.okTimeZones.push(angular.copy($scope.myTimeZone));          
          console.log($scope.okTimeZones);
          toastr.success(response.message);          
        } else {
          toastr.error(response.message);
        }        
      });
    }

    // Executed when any Update button (on the Timezone Cards) is pressed
    $scope.tzUpdate = function($index) {                  
      CardService.UpdateCard($scope.okTimeZones[$index], function(response) {
        if (!response.error) {          
          toastr.success(response.message);
        } else {
          toastr.error(response.message);
        }
      });
    }

    // Executed when any Remove button (on the Timezone Cards) is pressed
    $scope.tzRemove = function($index) {                  

      // With Firebase we use $remove, not splice
      //$scope.okTimeZones.$remove($scope.okTimeZones[$index]);

      CardService.DeleteCard($scope.okTimeZones[$index], function(response) {
        if (!response.error) {
          $scope.okTimeZones.splice($index, 1);    
          console.log($scope.okTimeZones);
          toastr.success(response.message);
        } else {
          toastr.error(response.message);
        }
      });
    }

  }])
  
  // Creates a ticking clock with an Offset parameter, to reflect the time difference
  .directive('timerClockWithOffset', ['$interval', 'dateFilter', function($interval, dateFilter) {

    // Substracts an amount of minutes from a given Date. It also adjusts to the browsers' TimeZone
    function dateOffset(startdate, offset) {  
      var endDate = new Date();  
      var UTCoffset = (new Date().getTimezoneOffset());
      endDate.setMinutes(startdate.getMinutes() + (offset * 60) + UTCoffset);
      return endDate;
    }

    function link(scope, element, attrs) {
      var timeoutId, offset;

      // Takes its value from the attribute format-style
      var format = scope.$eval(attrs.formatStyle);

      // Updates the content of the element, in this case, the time adjusted by the offset, in the asked format
      function updateTime() {        
        element.text(dateFilter(dateOffset(new Date(), offset), format));        
      }

      // Watches for changes in the given attribute: I.E. timer-clock-with-offset="myTimeZone.offset"
      scope.$watch(attrs.timerClockWithOffset, function(value) {
        offset = value;
        updateTime();
      });

      // Cancels the timer when the element is destroyed to prevent memory leaks
      element.on('$destroy', function() {$interval.cancel(timeoutId);});     
      // Creates the Timer
      timeoutId = $interval(function() {updateTime();}, 1000);
    }

    return {
      link: link
    };
  }])
  
  // Enables the use of the HTML template defined in 'templateUrl'
  // In the template scope, the object 'cardTimeZone' receives what is assigned to 'time-zone-card'  
  .directive('timeZoneCard', [function() {
    return {      
      transclude: true,
      scope: {        
        cardTimeZone: '=timeZoneCard'
      },
      templateUrl: 'partials/tzcard.html'
    };
  }]);