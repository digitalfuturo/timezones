'use strict';

/* Services */

var appServices = angular.module('appServices', ['ngResource', 'firebase']);

appServices.factory('AuthenticationService', ['$http', '$cookieStore', '$rootScope', '$timeout', 
    function($http, $cookieStore, $rootScope, $timeout) {
        var service = {};
        var baseURL = 'http://www.digitalento.com/timezones/v1/';
        service.LoginRegister = LoginRegister;
        service.SetCredentials = SetCredentials;
        service.ClearCredentials = ClearCredentials;
 
        return service;
 
        function LoginRegister(user, doAction, callback) {
 			var url;
            var dataToSend;

            if (doAction == 'login') {
                url = baseURL + 'login';
                dataToSend = {email: user.email, password: user.password};
            } else if (doAction == 'register') {
                url = baseURL + 'register';
                dataToSend = {name: user.name, email: user.email, password: user.password};
            };

            restService($http, 'POST', url, dataToSend, callback); 			
        }  
 
        function SetCredentials(response) {
            $rootScope.globals = {
                currentUser: {
                    name: response.name,
                    email: response.email,
                    authdata: response.apiKey
                }
            };            
 
            $http.defaults.headers.common['Authorization'] = response.apiKey;
            $cookieStore.put('globals', $rootScope.globals);
        }
 
        function ClearCredentials() {
        	console.log('ClearCredentials');
            $rootScope.globals = {};
            $cookieStore.remove('globals');
            $http.defaults.headers.common.Authorization = '';
        }
}]);

appServices.factory('CardService', ['$http', '$cookieStore', '$rootScope', '$timeout', 
    function($http, $cookieStore, $rootScope, $timeout) {
        var service = {};
        var baseURL = 'http://www.digitalento.com/timezones/v1/';
        service.CreateCard = CreateCard;
        service.UpdateCard = UpdateCard;
        service.DeleteCard = DeleteCard;
        service.GetUserCards = GetUserCards;
 
        return service;
 
        function CreateCard(timeZone, callback) {
            var url;
            var dataToSend;

            url = baseURL + 'createcard';
            dataToSend = {tzid: timeZone.tz_id, value: timeZone.value};            
            restService($http, 'POST', url, dataToSend, callback);          
        }

        function UpdateCard(timeZone, callback) {
            var url;
            var dataToSend;

            url = baseURL + 'updatecard';
            console.log(timeZone);
            dataToSend = {id: timeZone.id, tzid: timeZone.tz_id, value: timeZone.value};            
            restService($http, 'POST', url, dataToSend, callback);          
        }

        function DeleteCard(timeZone, callback) {
            var url;
            var dataToSend;

            url = baseURL + 'deletecard';
            dataToSend = {id: timeZone.id};            
            restService($http, 'POST', url, dataToSend, callback);                      
        }

        function GetUserCards(callback) {
            var url;
            var dataToSend;

            url = baseURL + 'cards';
            dataToSend = {};            
            restService($http, 'GET', url, dataToSend, callback);          
        }
         
}]);

function restService(http, method, url, dataToSend, callback) {    
    http({
        method: method,
        url: url,
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        transformRequest: function(obj) {
            var str = [];
            for(var p in obj)
            str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
            return str.join("&");
        },
        data: dataToSend
    }).success(function (response) {
            callback(response);
        });    
}

appServices.factory('srvTimeZones', ['$resource',
  function($resource){    
    return $resource('http://www.digitalento.com/timezones/v1/alltimezones', {}, {
          query: {method:'GET',isArray:false}
      });
}]);

appServices.factory('UserService', ['$http',
    function($http) {
        var service = {};
 
        service.GetAll = GetAll;
        service.GetById = GetById;
        service.GetByUsername = GetByUsername;
        service.Create = Create;
        service.Update = Update;
        service.Delete = Delete;
 
        return service;
 
        function GetAll() {
            return $http.get('/api/users').then(handleSuccess, handleError('Error getting all users'));
        }
 
        function GetById(id) {
            return $http.get('/api/users/' + id).then(handleSuccess, handleError('Error getting user by id'));
        }
 
        function GetByUsername(username) {
            return $http.get('/api/users/' + username).then(handleSuccess, handleError('Error getting user by username'));
        }
 
        function Create(user) {
            return $http.post('/api/users', user).then(handleSuccess, handleError('Error creating user'));
        }
 
        function Update(user) {
            return $http.put('/api/users/' + user.id, user).then(handleSuccess, handleError('Error updating user'));
        }
 
        function Delete(id) {
            return $http.delete('/api/users/' + id).then(handleSuccess, handleError('Error deleting user'));
        }
 
        // private functions
 
        function handleSuccess(data) {
            return data;
        }
 
        function handleError(error) {
            return function () {
                return { success: false, message: error };
            };
        }
    }
]);