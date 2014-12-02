Ext.ns('Garp');

Garp.MapField = Ext.extend(Ext.form.FieldSet, {

	/**
	 * Defaults:
	 */
	latFieldname: 'location_lat',
	longFieldname: 'location_long',
	addressRef: 'location_address',
	fieldLabel: __('Location'),
	
	anchor: -30,
	layout: 'hbox',
	msgTarget: 'under',
	style: 'padding: 0;',
	
	/**
	 * updates the address textual representation of the lat/long combination
	 */
	updateAddress: function(){
		var owner = this.refOwner.refOwner;
		var lat = owner.getForm().findField(this.latFieldname);
		var lng = owner.getForm().findField(this.longFieldname);
		var address = owner.formcontent[this.addressRef];
		
		function updateAddress(){
			var geocoder = new google.maps.Geocoder();
			if (lat.getValue() && lng.getValue()) {
				//address.update(__('Searching location...'));
				
				geocoder.geocode({
					'latLng': new google.maps.LatLng(lat.getValue(), lng.getValue())
				}, function(results, status){
					if (status == google.maps.GeocoderStatus.OK) {
						if (results[0]) {
							address.update(Garp.renderers.geocodeAddressRenderer(results[0]));
						} else {
							address.update(__('Location set, but unknown'));
						}
					} else if (status == google.maps.GeocoderStatus.ZERO_RESULTS) {
						address.update(__('Location set, but unknown'));
					} else {
						address.update(__('Unknown error occurred.'));
					}
				});
			} else {
				address.update(__('No location specified'));
			}
		}
		if (typeof google == 'undefined') {
			Garp.lazyLoad('http://maps.googleapis.com/maps/api/js?sensor=false', updateAddress);
		} else {
			updateAddress();
		}
		lat.on('change', updateAddress);
	},
	
	initComponent: function(ct){
		this.items = [{
			xtype: 'button',
			iconCls: 'icon-map',
			text: __('Map'),
			flex: 0,
			margins: '0 20 0 0',
			handler: function(){
				new Garp.MapWindow({
					fieldRef: this.ownerCt,
					'lat': this.latFieldname,
					'long': this.longFieldname
				}).show();
			},
			scope: this
		}, {
			name: this.latFieldname,
			fieldLabel: __('Location lat'),
			disabled: false,
			hidden: true,
			allowBlank: true,
			xtype: 'textfield'
		}, {
			name: this.longFieldname,
			fieldLabel: __('Location long'),
			disabled: false,
			hidden: true,
			allowBlank: true,
			xtype: 'textfield'
		}, {
			xtype: 'box',
			ref: '../../' + this.addressRef,
			flex: 0,
			margins: '4 20 0 0'
		}];
		Garp.MapField.superclass.initComponent.call(this, ct);
		this.on('show', this.updateAddress, this);
	}
});

Ext.reg('mapfield', Garp.MapField);