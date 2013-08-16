$(function() {
	var flashMsg = new Garp.FlashMessage({
		parseCookie: true,
		afterShow: function(fm){
			fm.elm.bind('click', Garp.createDelegate(fm.close, fm));
		}
	});
});
