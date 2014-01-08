/**
 * garp.config.js
 * 
 * Defines config properties and functions ("globals")
 */

Ext.ns('Garp');

Garp.SMALLSCREENWIDTH = 640;

/**
 * Locale
 */
if (typeof Garp.locale === 'undefined') {
	Garp.locale = {};
}

function __(str, vars){
	if (arguments.length > 1) {
		var args = [].slice.call(arguments);
		args.unshift(typeof Garp.locale[str] !== 'undefined' ? Garp.locale[str] : str);
		return String.format.apply(String, args);
	}
	if (typeof Garp.locale[str] !== 'undefined') {
		return Garp.locale[str];
	}
	return str;
}
	
/**
 * Defaults & shortcuts
 */
var maxItems = Math.floor((window.innerHeight - 100 - 34) / 20) - 1; // grid starts 100px from top, ends 34px from bottom
Ext.applyIf(Garp,{
	pageSize: maxItems * 2, // double the pageSize to let the scrollbar show
	localUser: {}, // the logged in User
	confirmMsg: __('Changes will get lost. Continue?')
});

Ext.apply(Ext.Ajax,{
	timeout: 0
});
