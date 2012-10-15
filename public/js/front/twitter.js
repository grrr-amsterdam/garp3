/**
 * @class relativeDate
 *        &
 * @class Twitter
 * 
 * @package Garp
 */

/**
 * Garp relative Date
 * Returns the date or time difference in HRF™ (Human Readable Format)
 */
Garp.relativeDate = function(oldest, newest){
	if (typeof oldest.getTime != 'function') {
		oldest = new Date(oldest + '');
	}
	if (typeof newest.getTime != 'function') {
		newest = new Date(newest + '');
	}
	
	var elapsed = Math.abs(oldest.getTime() - newest.getTime()); // milliseconds
	
	if(isNaN(elapsed)){
		return '';
	}
	
	elapsed = elapsed / 60000; // minutes
	var result = '';
	
	switch (true) {
		case (elapsed < 1):
			result = __('less than a minute');
			break;
			
		case (elapsed < (60)):
			var minutes = Math.round(elapsed);
			result = minutes + ' ' + (minutes == 1 ? __('minute') : __('minutes'));
			break;
			
		case (elapsed < (60 * 24)):
		
			var hours = Math.round(elapsed / 60);
			result = hours + ' ' + (hours == 1 ? __('hour') : __('hours'));
			break;
			
		case (elapsed < (60 * 24 * 7)):
			var days = Math.round(elapsed / (60 * 24));
			result = days + ' ' + (days == 1 ? __('day') : __('days'));
			break;
			
		case (elapsed < (60 * 24 * 7 * 30)):
			var weeks = Math.round(elapsed / (60 * 24 * 7));
			result = weeks + ' ' + (weeks == 1 ? __('week') : __('weeks'));
			break;
			
		case (elapsed < (60 * 24 * 7 * 30 * 12)):
			var months = Math.round(elapsed / (60 * 24 * 7 * 30));
			result = months + ' ' + (months == 1 ? __('month') : __('months'));
			break;
			
		default:
			var years = Math.round(elapsed / (60 * 24 * 7 * 30 * 365));
			result = years + ' ' + (years == 1 ? __('year') : __('years'));
			break;
			
	}
	return result;
};

/***
 * Class Twitter
 * @param {Object} config
 * 
 * example usage:
 * var twitter = new Garp.Twitter({
 *		elm: $('#tweets'),
 *		afterFetch: function(result){
 *			this.elm.prepend(result);
 *		},
 *		beforeFetch: function(){
 *			this.elm.empty();
 *		}
 *	});
 *	twitter.search('garp');
 */
Garp.Twitter = function(config){

	// Default config: //
	Garp.apply(this, {
		query: '',
		resultsPerPage: 25, // max 100
		resultsAsArray: false,
		searchTpl: '<img src="${1}" alt="${2}">${2}: ${3}<hr>',
		listTpl: '<img src="${1}" alt="${2}">${2}: ${3}<hr>',
		beforeFetch: jQuery.noop,
		afterFetch: jQuery.noop,
		onError: jQuery.noop // Gets called when no results found or an error occurred
	});
	
	// Override config: //
	Garp.apply(this, config);
	
	/**
	 * Searches Twitter for query and caches the query string for later re-use
	 * @param {query} (optional) query
	 */
	this.search = function(query){
		if (query) {
			this.query = query;
		} else {
			query = this.query;
		}
		query = encodeURIComponent(query);
		var scope = this;
		scope.beforeFetch.call(this);
		$.getJSON(Garp.format('http://search.twitter.com/search.json?q=${1}&rpp=${2}&callback=?', query, this.resultsPerPage), function(response){
			scope.parseResponse.call(scope, response);
		});
	};
	
	
	/**
	 * Gets Twitter Lists
	 * @param {String} user
	 * @param {String} listId
	 */
	this.getList = function(user, listId){
		var scope = this;
		scope.beforeFetch.call(this);
		$.getJSON(Garp.format('http://api.twitter.com/1/${1}/lists/${2}/statuses.json?callback=?', user, listId), function(response){
			scope.parseResponse.call(scope, response);
		});
	};
	
	// private //
	this.parseResponse = function(response){
		var item, result = [];
		if (response) {
			if (response.results) { // search results
				for (item in response.results) {
					if (response.results[item].text) {
						result.push(Garp.format(this.searchTpl, response.results[item].profile_image_url, response.results[item].from_user, response.results[item].text.replace(new RegExp('(http://[^ ]+)', "g"), '<a target="_blank" href="$1">$1</a>'), Garp.relativeDate(response.results[item].created_at, new Date()), response.results[item].from_user));
					}
				}
			} else { // list results
				var c = 1;
				for (item in response) {
					c++;
					if (response[item].text) {
						result.push(Garp.format(this.listTpl, response[item].user.profile_image_url, response[item].user.name, response[item].text.replace(new RegExp('(http://[^ ]+)', "g"), '<a href="$1">$1</a>'), Garp.relativeDate(response[item].created_at, new Date()), response[item].user.screen_name));
					}
					if(c > this.resultsPerPage){
						break;
					}
				}
			}
			if (result.length) {
				this.afterFetch(this.resultsAsArray ? result : result.join(''));
			} else {
				this.onError(response);
			}
		} else {
			this.onError(response);
		}
	};
	
	return this;
};