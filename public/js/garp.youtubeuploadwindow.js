var onYouTubeIframeAPIReady; // = that's YouTube being ugly here!

Garp.YouTubeUploadWindow = Ext.extend(Ext.Window,{

	modal: true,
	title: __('YouTube Upload'),
	width: 610,
	height: 435,
	resizable: false,
	html: '<div id="widget"></div>',
	scriptTagId : 'youtubeuploadwindow',
	
	_lm: new Ext.LoadMask(Ext.getBody(), {
		msg: __('Waiting for YouTube. This might take a while&hellip;')
	}),
	
	initComponent: function(arg){
		this.addEvents(['uploadcomplete']);
		Garp.YouTubeUploadWindow.superclass.initComponent.call(this, arg);
		if(document.getElementById(this.scriptTagId)){
			Ext.select('#' + this.scriptTagId).remove();
		}
	},
	
	afterRender: function(arg){
		Garp.YouTubeUploadWindow.superclass.afterRender.call(this, arg);		
		this.on('beforeclose',  function(){
			this._lm.hide();
		}, this);
		
		this._lm.show();
		
		// load YT api and shizzle:
		var tag = document.createElement('script');
		tag.src = "//www.youtube.com/iframe_api?noCache=" + new Date().getTime();
		tag.id = this.scriptTagId;
		var s = document.getElementsByTagName('script')[0];
		s.parentNode.insertBefore(tag, s);
		
		var widget;
		var scope = this;
		onYouTubeIframeAPIReady = function(){
			widget = new YT.UploadWidget('widget', {
				webcamOnly: false,
				width: 600,
				events: {
					'onApiReady': function(){
						scope._lm.hide();
					},
					'onUploadSuccess': function(){
						scope._lm.show();
					},
					'onProcessingComplete': function(event){
						scope._lm.hide();
						scope.fireEvent('uploadcomplete', event);
					}
				}
			});
			scope.el.select('iframe').first().show();
		};
	}
});