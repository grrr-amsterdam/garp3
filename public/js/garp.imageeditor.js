Ext.ns('Garp');

Garp.ImageEditor = Ext.extend(Ext.Panel,{

//
//	id: 'imageEditor',
//
	layout:'fit',
	border: false,
	bodyStyle: 'backgroundColor: #888',

	updateOutline: function(){
		var crop = Ext.get(this.crop);
		var img = Ext.get(this.img);
		
		var x = img.getX() - crop.getX();
		var y = img.getY() - crop.getY();
	//	var w = crop.getWidth();
	//	var h = crop.getHeight();
		
		//var aspectRatio = 9/16;
		
		//crop.setHeight(w * aspectRatio);
		
		var tl = x + 'px ' + y + 'px';
		crop.setStyle({'background-position':tl});
		this.dd.constrainTo(this.img);
		return true;
	},
	
	setupEditor: function(){
		var scope = this;
		var i = new Image();
		Ext.get(i).on({
			'load': function(){
				this.img = Ext.DomHelper.append(scope.body, {
					tag: 'img',
					src: i.src,
					cls: 'imageeditor-subject',
					width: i.width,
					height: i.height
				});
				
				Ext.get(this.img).center(this.body);
				
				this.crop = Ext.DomHelper.insertAfter(this.img, {
					tag: 'div',
					cls: 'imageeditor-cropoutline',
					children: [{
						'tag': 'div',
						cls: 'imageeditor-cropoutline-mq',
						children: [{
							tag: 'div',
							cls: 'left'
						}, {
							tag: 'div',
							cls: 'right'
						}, {
							tag: 'div',
							cls: 'top'
						}, {
							tag: 'div',
							cls: 'bottom'
						}]
					}],
					style: {
						width: i.width + 'px',
						height: i.height + 'px',
						background: 'url(' + i.src + ')',
						'background-repeat': 'no-repeat'
					}
				});
				
				this.resizer = new Ext.Resizable(this.crop, {
					handles: 'all',
					transparent: true,
					constrainTo: this.img,
					dynamic: false,
					width: 200,
					height: 200 * (9/16),
					preserveRatio: true,
					
					listeners: {
						'resize' :this.updateOutline.createDelegate(this)
					}
				});
				
				Ext.get(this.crop).center(this.body);
				this.dd = Ext.get(this.crop).initDD(null,null,{
					onDrag: this.updateOutline.createDelegate(this)
				});
				this.dd.constrainTo(this.img);
				
				var cropEl = Ext.get(this.crop);
				var img = Ext.get(this.img);
				cropEl.setTop(img.getTop(true) + 10),
				cropEl.setLeft(img.getLeft(true) + 30),
				
				this.updateOutline();
				
			},
			'error': function(){
				//@TODO: implement
			},
			scope: this
		},this);
		 
		i.src = this.image;
	},
	
	initComponent: function(){
		
		this.addEvents('done');
		
		this.buttonAlign = 'left';
		this.buttons = [{
			xtype: 'box',
			html: __('Drag the selection, or drag the corners to resize the selection.')
		}, '->' ,{
			text: __('Ok'),
			scope: this,
			handler: function(){
				this.fireEvent('done', [this.image]);
			}
		}];

		Garp.ImageEditor.superclass.initComponent.call(this);
		this.on('afterrender', this.setupEditor.createDelegate(this));
	}
});