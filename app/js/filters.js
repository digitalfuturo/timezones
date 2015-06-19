'use strict';

/* Filters */

angular.module('appFilters', []).filter('checkmark', function() {
  return function(input) {
    return input ? '\u2713' : '\u2718';
  };
});

angular.module('appFilters', []).filter('reverse', function() {
  return function(items) {
    return items.slice().reverse();
  };
});