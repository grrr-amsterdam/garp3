/** EXTENDED MODEL **/
(function() {
	if (!('Document' in Garp.dataTypes)) {
		return;
	}
	Garp.dataTypes.Document.on('init', function(){

		this.addListener('loaddata', function(rec, formPanel) {
      if (rec.data.id && rec.data.filename) {
        formPanel.getForm().findField('filename').disable();
      } else {
        formPanel.getForm().findField('filename').enable();
      }
    });
	});
})();
