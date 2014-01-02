/**
 * garp.config.js
 * 
 * Defines config properties and functions ("globals")
 */

Ext.ns('Garp');

/**
 * Locale
 */
if(typeof Garp.locale === 'undefined') Garp.locale = {};

function __(str) {
	if (typeof Garp.locale[str] !== 'undefined')
		return Garp.locale[str];
	return str;
}

/**
 * Defaults & shortcuts
 */
Ext.applyIf(Garp,{
	pageSize: 25,
	localUser: {}, // the logged in User
	confirmMsg: __('Changes will get lost. Continue?')
});

Ext.apply(Ext.Ajax,{
	timeout: 600000
});
