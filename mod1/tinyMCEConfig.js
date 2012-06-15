function myCustomCleanup(type, value) {
	switch (type) {
		case 'insert_to_editor':
			value = value.replace("\n", '<br />');
			break;
	}
	return value;
}

tinyMCE.init({
	height: '100',

	mode: 'textareas',
	entity_encoding: 'raw',
	forced_root_block: false,
	convert_newlines_to_brs: true,
	cleanup_callback: 'myCustomCleanup',

	theme: 'advanced',
	theme_advanced_buttons1: 'bold,italic,underline,strikethrough,separator,forecolor,backcolor,charmap,separator,bullist,numlist,separator,code,fullscreen,spellchecker',
	theme_advanced_buttons2: '',
	theme_advanced_buttons3: '',
	theme_advanced_toolbar_location: 'top',
	theme_advanced_toolbar_align: 'left',
	theme_advanced_path: false,
	theme_advanced_statusbar_location: 'bottom',
	theme_advanced_resizing: true,
	theme_advanced_resize_horizontal: false,
	plugins: 'fullscreen,spellchecker'
});
