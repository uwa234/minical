var summernoteToolbar = [
	['style', ['bold', 'italic', 'underline', 'clear']],
	['para', ['ul', 'ol', 'paragraph']],
	['insert', ['link']],
	['view', ['fullscreen', 'codeview']]
];

function getRichTextDescription(textareaId, container) {
	var $textarea = $('#' + textareaId);
	if ($textarea.length && $textarea.next('.note-editor').length) {
		return $textarea.summernote('code') || '';
	}
	if (container && container.length) {
		var textarea = container.find('textarea[name="description"], textarea').first();
		return textarea.length ? textarea.val() : '';
	}
	return $textarea.length ? ($textarea.val() || '') : '';
}

function initSummernote(textareaId) {
	if (typeof $.fn.summernote === 'undefined') {
		return;
	}
	var $textarea = $('#' + textareaId);
	if (!$textarea.length || $textarea.next('.note-editor').length) {
		return;
	}
	$textarea.summernote({
		height: 150,
		dialogsInBody: true,
		toolbar: summernoteToolbar
	});
}

function destroySummernote(textareaId) {
	var $textarea = $('#' + textareaId);
	if ($textarea.length && $textarea.next('.note-editor').length) {
		$textarea.summernote('destroy');
	}
}
