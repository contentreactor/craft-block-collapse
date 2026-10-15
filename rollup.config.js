import fs from 'node:fs'
import path from 'node:path'
import CleanCSS from 'clean-css'
import terser from '@rollup/plugin-terser'

// One bundle per directory under assets/, following the Developion asset bundling guidelines:
// assets/<Name>/src/js/app.js → public/<Name>/dist/js/app.js, stylesheets → public/<Name>/dist/css.
//
// `--environment MODE:dev` leaves the bundle readable and writes a sourcemap; every other run
// minifies, which is what the committed output in public/ is built with.
const dev = process.env.MODE === 'dev'
const assets = fs
	.readdirSync('assets', { withFileTypes: true })
	.filter(entry => entry.isDirectory())
	.map(entry => entry.name)

/**
 * There is not enough CSS here to warrant a pipeline — each stylesheet goes to dist as it was
 * written, minified with clean-css unless this is a dev build.
 */
const buildCss = name => ({
	name: 'build-css',
	writeBundle() {
		const from = `assets/${name}/src/css`

		if (!fs.existsSync(from)) {
			return
		}

		const to = `public/${name}/dist/css`
		fs.mkdirSync(to, { recursive: true })

		for (const file of fs.readdirSync(from).filter(file => file.endsWith('.css'))) {
			const source = fs.readFileSync(path.join(from, file), 'utf8')

			if (dev) {
				fs.writeFileSync(path.join(to, file), source)
				continue
			}

			// clean-css's default level, as gulp-clean-css runs it in the Developion pipeline:
			// level 2's cross-rule merging saves four bytes here and isn't worth the surprises
			const output = new CleanCSS().minify(source)

			if (output.errors.length) {
				this.error(`${file}: ${output.errors.join(' ')}`)
			}

			for (const warning of output.warnings) {
				this.warn(`${file}: ${warning}`)
			}

			fs.writeFileSync(path.join(to, file), `${output.styles}\n`)
		}
	},
})

/** A production build shouldn't leave the previous dev build's sourcemap lying around. */
const dropStaleSourcemap = name => ({
	name: 'drop-stale-sourcemap',
	writeBundle() {
		const map = `public/${name}/dist/js/app.js.map`

		if (!dev && fs.existsSync(map)) {
			fs.rmSync(map)
		}
	},
})

export default assets.map(name => ({
	input: `assets/${name}/src/js/app.js`,
	output: {
		file: `public/${name}/dist/js/app.js`,
		format: 'iife',
		name: `${name}Bundle`,
		banner: `/*! Block Collapse — built from assets/${name}/src; edit that, not this. */`,
		sourcemap: dev,
	},
	plugins: [
		dev ? null : terser({ format: { comments: /Block Collapse/ } }),
		buildCss(name),
		dropStaleSourcemap(name),
	].filter(Boolean),
}))
