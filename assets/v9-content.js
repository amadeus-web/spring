if (typeof($) === 'undefined') $ = jQuery.noConflict();

$(document).ready(function() {
	$('textarea.autofit').on('change, input', textAreaAutoHeight).trigger('input');

	function textAreaAutoHeight(ev) {
		this.style.height = '1px';
		this.style.height = this.scrollHeight + 'px';
	}

	if ($('body.has-gemini-ai').length) {
		let numbers = $('a.prompt-number');
		if (!numbers.length) {
			const prompts = $('.prompt');
			prompts.each(function(ix, el){
				ix += 1;
				$(el).prepend($('<a name="prompt-' + ix + '" class="prompt-number"></a>'));
			});
			numbers = $('a.prompt-number');
		}

		if (numbers.length) {
			numbers.each(function(ix, el) {
				ix = $(el).attr('name').replace('prompt-', '');
				$(el).closest('.prompt').prepend($('<div class="prompt-button">Prompt <a href="#prompt-' + ix + '">#' + ix + '</a></div>'));
			});
		}
	}
});
