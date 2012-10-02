/**
 * 
 */
Garp.MapWindow = Ext.extend(Ext.Window,{
	
	width: 800,
	height: 440,
	modal: true,
	iconCls: 'icon-map',
	maximizable: true,
	frame: true,
	title: __('Map'),
	buttonAlign: 'left',
	
	/**
	 * @cfg lat
	 * contains the name of the latitude field
	 */
	'lat': null,
	/**
	 * @cfg long
	 * contains the name of the longitude field
	 */
	'long': null,
	/**
	 * @cfg long
	 * contains the ref to the address box
	 */
	'address': null,
	
	/**
	 * @cfg fieldRef
	 * pointer to common container for both location fields
	 */
	fieldRef: null,
	
	// Private variables //
	map: null,
	pointer: null,
	latlng: null,
	
	/**
	 * @function buildPointer
	 * builds the dragable pointer, and add's an event listener on it's drag event
	 */
	buildPointer: function(){
		this.pointer = new google.maps.Marker({
			position: this.latlng,
			animation: google.maps.Animation.DROP,
			draggable: true
		});
		var scope = this;
		google.maps.event.addListener(this.pointer, 'dragend', function(e){
			scope.latlng = e.latLng;
			scope.map.setCenter(e.latLng);
		});
		this.pointer.setMap(this.map);

	},
	
	/**
	 * @function addLocation
	 * adds the pointer (= the location) to the map
	 */
	addLocation: function(){
		this.latlng = this.map.getCenter();
		this.buildPointer();
		this.pointer.setMap(this.map);
		this.addLocationBtn.hide();
		this.removeLocationBtn.show();
	},
	
	/**
	 * @function removeLocation
	 * removes the location & pointer from the map
	 */
	removeLocation: function(){
		if (this.pointer) {
			this.pointer.setMap(null); // removes it
		}
		this.addLocationBtn.show();
		this.removeLocationBtn.hide();
	},
	
	/**
	 * @function drawMap
	 */
	drawMap: function(){
		this.map = new google.maps.Map(this.body.dom, {
			mapTypeId: google.maps.MapTypeId.ROADMAP,
			zoom: 11 // @TODO: should this be overridable?
		});
		if (this.pointer) {
			this.map.setCenter(this.latlng);
			this.pointer.setMap(this.map);
			this.removeLocationBtn.show();
			this.addLocationBtn.hide();
		} else {
			this.addLocationBtn.show();
			this.removeLocationBtn.hide();
		
			var lat = this.fieldRef.find('name', this['lat'])[0].getValue();
			var lng = this.fieldRef.find('name', this['long'])[0].getValue();
			if (lat && lng) {
				this.latlng = new google.maps.LatLng(lat, lng);
				this.map.setCenter(this.latlng);
				this.buildPointer();
			} else {
				this.map.setCenter(new google.maps.LatLng(52.3650012, 5.0692639)); // @TODO: (see this.map comment above)
			}
		}
	},
	
	// Init: //
	initComponent: function(){
		// <script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
		
		
		this.on({
			'afterrender': {
				scope: this,
				fn: function(){
					if (typeof google == 'undefined') {
						Garp.lazyLoad('http://maps.googleapis.com/maps/api/js?sensor=false', this.drawMap.createDelegate(this));
					} else {
						this.drawMap();
					}
				}
			}
		}, {
			'resize': {
				fn: function(){
					if (this.map) {
						google.maps.event.trigger(this.map, 'resize');
					}
				}
			}
		});
		
		Ext.apply(this, {
			buttons: [{
				text: __('Add Location'),
				ref: '../addLocationBtn',
				hidden: true,
				handler: this.addLocation.createDelegate(this)
			}, {
				text: __('Remove Location'),
				ref: '../removeLocationBtn',
				hidden: true,
				handler: this.removeLocation.createDelegate(this)
			}, {
				text: __('Address Lookup'),
				iconCls: 'icon-search',
				scope: this,
				handler: function(){
					var scope = this;
					Ext.Msg.prompt(__('Address Lookup'), __('Please enter the address to lookup:'), function(btn, query){
						if (btn == 'ok' && query) {
							var geocoder = new google.maps.Geocoder();
							geocoder.geocode({
								address: query
							}, function(resp, status){
								if (status == 'OK' && resp.length) {
									scope.latlng = resp[0].geometry.location;
									scope.buildPointer();
									scope.drawMap();
									scope.addLocationBtn.hide();
									scope.removeLocationBtn.show();
								} else {
									Ext.Msg.alert(scope.title, __('Address not found'));
								}
							});
						}
					});
				}
			}, '->', {
				text: __('Ok'),
				scope: this,
				handler: function(){
					
					this.fieldRef.find('name', this['lat'])[0].setValue(this.latlng.lat());
					this.fieldRef.find('name', this['long'])[0].setValue(this.latlng.lng());
					this.fieldRef.find('name', this['lat'])[0].fireEvent('change');

					this.close();
				}
			}, {
				text: __('Cancel'),
				scope: this,
				handler: this.close
			}]
		});
		
		Garp.MapWindow.superclass.initComponent.call(this);
	}
});