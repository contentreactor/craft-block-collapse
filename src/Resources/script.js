document.addEventListener('DOMContentLoaded', function () {
	const tooltipWrapper = document.createElement('div')
	tooltipWrapper.id = 'matrix-toggle-tooltip'
	const tooltip = document.createElement('span')
	tooltip.className = 'small-tooltip'
	tooltipWrapper.appendChild(tooltip)
	document.body.appendChild(tooltipWrapper)

	let showTimeout = null
	let hideTimeout = null

	document.querySelectorAll('#matrix-toggle [data-tooltip]').forEach(el => {
		el.addEventListener('mouseenter', function () {
			clearTimeout(hideTimeout)
			clearTimeout(showTimeout)
			tooltipWrapper.style.display = 'none'
			tooltip.textContent = this.dataset.tooltip
			const rect = this.getBoundingClientRect()
			tooltipWrapper.style.left = rect.left + (rect.width / 2) + window.scrollX + 'px'
			tooltipWrapper.style.top = rect.bottom + 5 + window.scrollY + 'px'
			showTimeout = setTimeout(() => {
				tooltipWrapper.style.display = 'block'
			}, 500)
		})

		el.addEventListener('mouseleave', function () {
			clearTimeout(showTimeout)
			hideTimeout = setTimeout(() => {
				tooltipWrapper.style.display = 'none'
			}, 250)
		})
	})
})

document.addEventListener('click', e => {
	if (!e.target.closest('#collapse-all')) return
	collapseAll()
})

document.addEventListener('click', e => {
	if (!e.target.closest('#expand-all')) return
	expandAll()
})

document.addEventListener('keyup', e => {
	if (!e.ctrlKey) return

	if (e.code === 'Comma') collapseAll()
	if (e.code === 'Period') expandAll()
})
