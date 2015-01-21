
CKEDITOR.plugins.add( 'garpctrlenter', {
	init: function( editor ) {

		editor.addCommand( 'ctrlEnter', {
			exec: function( editor ) {
				Garp.ctrlEnter();
			}
		} );

		editor.setKeystroke( [
			[ CKEDITOR.CTRL + 13, 'ctrlEnter' ]
		] );
	}
} );
