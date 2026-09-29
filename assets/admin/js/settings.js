/* Document Engine settings: image picker, CSS editor, unsaved-changes bar. */
(function ($) {
	var i18n = window.DocumentEngineSettings || {};

	function initImages() {
		$(document).on('click', '[data-dengine-image] [data-action]', function (event) {
			event.preventDefault();
			var $wrap = $(this).closest('[data-dengine-image]');
			var $input = $wrap.find('input[type=hidden]');
			var $preview = $wrap.find('.dengine-a-image__preview');
			var $choose = $wrap.find('[data-action=choose]');
			var $remove = $wrap.find('[data-action=remove]');

			if ($(this).data('action') === 'remove') {
				$input.val('0').trigger('change');
				$preview.attr('hidden', true).empty();
				$remove.attr('hidden', true);
				$choose.text(i18n.choose || 'Choose image');
				return;
			}
			var frame = wp.media({ title: i18n.choose || 'Choose image', library: { type: 'image' }, multiple: false });
			frame.on('select', function () {
				var image = frame.state().get('selection').first().toJSON();
				var url = image.sizes && image.sizes.medium ? image.sizes.medium.url : image.url;
				$input.val(image.id).trigger('change');
				$preview.removeAttr('hidden').html($('<img alt="">').attr('src', url));
				$remove.removeAttr('hidden');
				$choose.text(i18n.replace || 'Replace image');
			});
			frame.open();
		});
	}

	function initCode() {
		if (!window.wp || !wp.codeEditor || !i18n.codeEditor) {
			return;
		}
		$('textarea[data-code=css]').each(function () {
			var textarea = this;
			var editor = wp.codeEditor.initialize(textarea, i18n.codeEditor);
			editor.codemirror.on('change', function () {
				editor.codemirror.save();
				$(textarea).trigger('change');
			});
		});
	}

	function initDirty() {
		var $form = $('[data-dengine-settings]');
		var $bar = $form.find('.dengine-a-savebar');
		var $status = $bar.find('[data-dengine-status]');
		var dirty = false;
		if (!$form.length) {
			return;
		}
		$status.text(i18n.saved || '');
		$form.on('change input', ':input', function () {
			if (!dirty) {
				dirty = true;
				$bar.addClass('is-dirty');
				$status.text(i18n.unsaved || 'You have unsaved changes');
			}
		});
		$form.on('submit', function () {
			dirty = false;
			$form.find('button[name=save]').attr('disabled', true).text(i18n.saving || 'Saving…');
			// Disabled buttons are not submitted; keep the "save" flag.
			$('<input type="hidden" name="save" value="1">').appendTo($form);
		});
		$(window).on('beforeunload', function () {
			if (dirty) {
				return i18n.unsaved || true;
			}
		});
	}

	$(function () {
		initImages();
		initCode();
		initDirty();
	});
})(jQuery);
