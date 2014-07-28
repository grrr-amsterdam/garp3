CKEDITOR.plugins.add('garpimages', {
	init: function(editor) {
		// Open an image dialog
		editor.addCommand('garpimageDialog', {
			exec: function(editor, data) {
				var command = this;

				var existingImage = {};
				if (data && data.existingImage) {
					existingImage = data.existingImage;
				}
				var elementToReplace;
				if (data && data.existingElement) {
					elementToReplace = data.existingElement;
				}

				// Open Extjs image picker
				var win = new Garp.ImagePickerWindow(existingImage);
				win.on('select', function(data) {
					// Insert image element
					var tpl = Garp.imageTpl;

					var imgHtml = tpl.apply({
						path: data.src,
						width: data.template.get('w'),
						height: data.template.get('h'),
						align: data.align,
						caption: data.caption || false
					});
					var element = CKEDITOR.dom.element.createFromHtml(imgHtml);

					element.setAttribute('contenteditable', false);

					// Insert or replace
					if (elementToReplace) {
						element.replace(elementToReplace);
					} else {
						editor.insertElement(element);
					}

					// Return to CKEditor
					editor.fire('afterCommandExec', {
						name: command.name,
						command: command
					});
				}, this);
				win.show();

			},
			async: true
		});

		// Open a dialog and replace the element with the results
		function editExistingImage(element) {
			if (!element) {
				return;
			}

			// Retrieve values from existing html
			var path = element.findOne('img') ? element.findOne('img').getAttribute('src') : element.getAttribute('src');
			path = path.split('/');
			if (path[path.length - 1] === '') {
				path.splice(path.length - 1, 1); // remove last ,if it's a trailing slash
			}
			var fileId = path[path.length - 1];
			var tplName = path[path.length - 2];
			var caption = element.findOne('figcaption') ? element.findOne('figcaption').getText() : null;
			var align = element.getStyle("float");

			// Exec with parameters from existing image
			editor.getCommand('garpimageDialog').exec({
				existingImage: {
					imgGridQuery: {
						id: fileId
					},
					cropTemplateName: tplName,
					captionValue: caption,
					alignValue: align == 'none' ? '' : align
				},
				existingElement: element
			});
		}

		// Get a valid image element from a valid image element or its children
		function getGarpImageElement(element) {
			var parent = element.getParent();
			if (element.getName() !== "figure" && parent.getName() === "figure") {
				element = element.getParent();
			}
			if (element.getName() !== "figure" && element.getName() !== "img") {
				return;
			}
			return element;
		}


		// Add remove option to right clicked 'images'
		// The last element to be right clicked
		var rightclickedElement;

		// Removing the image from the editor after right click
		editor.addCommand('garpimageRemove', {
			exec: function(editor) {
				rightclickedElement.remove();
			}
		});
		// Editing an image after rightclick
		editor.addCommand('garpimageEdit', {
			exec: function(editor) {
				editExistingImage(rightclickedElement);
			}
		});

		// Open dialog on doubleclick
		editor.on('doubleclick', function(evt) {
			var element = getGarpImageElement(evt.data.element);
			editExistingImage(element);
		});

		// Add insert button to top bar
		editor.ui.addButton('Garpimage', {
			label: 'Insert Image',
			command: 'garpimageDialog',
			icon: this.path + 'picture.png',
			toolbar: 'insert'
		});

		// Define the menu options
		editor.addMenuGroup('garp');
		editor.addMenuItem('GarpimageRemove', {
			label: 'Remove Image',
			command: 'garpimageRemove',
			icon: this.path + 'picture.png',
			group: 'garp'
		});
		editor.addMenuItem('GarpimageEdit', {
			label: 'Edit Image',
			command: 'garpimageEdit',
			icon: this.path + 'picture.png',
			group: 'garp'
		});

		// Show option when right clicking on an image
		editor.contextMenu.addListener(function(element, selection) {
			rightclickedElement = getGarpImageElement(element);
			if (!rightclickedElement) {
				return;
			}
			return {
				"GarpimageRemove": CKEDITOR.TRISTATE_ON,
				"GarpimageEdit": CKEDITOR.TRISTATE_ON
			};
		});
	}
});
