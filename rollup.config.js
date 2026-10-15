import fs from 'node:fs'
import terser from '@rollup/plugin-terser'

const asset = 'src/web/assets/collapse'

/** The stylesheet ships as-is; there is not enough of it to warrant a CSS pipeline. */
const copyCss = () => ({
	name: 'copy-css',
	writeBundle() {
		fs.mkdirSync(`${asset}/dist`, { recursive: true })
		fs.copyFileSync(`${asset}/src/collapse.css`, `${asset}/dist/collapse.css`)
	},
})

export default {
	input: `${asset}/src/index.js`,
	output: {
		file: `${asset}/dist/collapse.js`,
		format: 'iife',
		name: 'BlockCollapseBundle',
		banner: '/*! Block Collapse — built from src/web/assets/collapse/src; edit that, not this. */',
	},
	plugins: [terser({ format: { comments: /Block Collapse/ } }), copyCss()],
}
