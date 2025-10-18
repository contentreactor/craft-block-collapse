console.log('pera', 'v4')

function collapseAll() {
	$('.matrix-field .blocks .matrixblock').each(function () {
		$(this).data('block').collapse()
	})
	$('.field .ni_blocks .ni_block').each(function () {
		$(this).data('block').collapse()
	})
}

function expandAll() {
	$('.matrix-field .blocks .matrixblock').each(function () {
		$(this).data('block').expand()
	})
	$('.field .ni_blocks .ni_block').each(function () {
		$(this).data('block').expand()
	})
}
