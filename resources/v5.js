console.log('mika', 'v5')

function collapseAll() {
	document.querySelectorAll('[data-action="collapse"]').forEach(el => {
		$(el).activate()
	})
	$('.field .ni_blocks .ni_block').each(function () {
		$(this).data('block').collapse()
	})
}

function expandAll() {
	document.querySelectorAll('[data-action="expand"]').forEach(el => {
		$(el).activate()
	})
	$('.field .ni_blocks .ni_block').each(function () {
		$(this).data('block').expand()
	})
}
