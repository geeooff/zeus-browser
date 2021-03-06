'use strict';

const autoprefixer = require('autoprefixer'),
	cleancss = require('postcss-clean'),
	concat = require('gulp-concat'),
	del = require('del'),
	gulp = require('gulp'),
	gulpSequence = require('gulp-sequence'),
	merge = require('merge-stream'),
	postcss = require('gulp-postcss'),
	rename = require('gulp-rename'),
	sass = require('gulp-sass'),
	sourcemaps = require('gulp-sourcemaps'),
	uglify = require('gulp-uglify')

const src = {
	sass: [
		'dl-res/css/main.scss'
	],
	bootstrapjs: [
		'node_modules/bootstrap/js/dist/util.js',
		'node_modules/bootstrap/js/dist/alert.js',
		'node_modules/bootstrap/js/dist/button.js',
		//'node_modules/bootstrap/js/dist/carousel.js',
		//'node_modules/bootstrap/js/dist/collapse.js',
		'node_modules/bootstrap/js/dist/dropdown.js',
		//'node_modules/bootstrap/js/dist/index.js',
		'node_modules/bootstrap/js/dist/modal.js',
		//'node_modules/bootstrap/js/dist/popover.js'
		//'node_modules/bootstrap/js/dist/scrollspy.js',
		//'node_modules/bootstrap/js/dist/tab.js',
		'node_modules/bootstrap/js/dist/tooltip.js',
	],
	js: [
		'dl-res/js/main.js'
	]
};
const dest = {
	css: 'dl-res/css/',
	js: 'dl-res/js/',
	bootstrapjs: 'bootstrap.min.js',
	fonts: 'dl-res/fonts/',
	maps: '.'
};
const copies = {
	js: [
		'node_modules/jquery/dist/jquery.min.js',
		'node_modules/popper.js/dist/popper.min.js',
		//'node_modules/bootstrap/dist/js/bootstrap.min.js',
		'node_modules/datatables.net/js/jquery.dataTables.js',
		'node_modules/datatables.net-bs4/js/dataTables.bootstrap4.js',
	],
	fonts: [
		'node_modules/font-awesome/fonts/**'
	]
};
const cleanDest = {
	css: [
		'dl-res/css/*.css',
		'dl-res/css/*.map'
	],
	js: [
		'dl-res/js/*.min.js',
		'dl-res/js/*.map'
	],
	fonts: [
		'dl-res/fonts/**/*'
	]
};

const autoprefixerOptions = {
	browsers: [
		'last 1 version',
		'> 10%',
		'not ie <= 11'
	]
};

const sassOptions = {
	indentType: 'tab',
	indentWidth: 1,
	linefeed: 'crlf'
};

const mapsInitOptions = {
	loadMaps: true
};

const mapsWriteOptions = {
	includeContent: false
};

const uglifyOptions = {
	compress: {
		drop_console: false
	},
	output: {
		quote_keys: true
	}
};

const cleanCssOptions = {
	keepSpecialComments: 0
};

const renameMinOptions = {
	suffix: '.min'
};

const watcherOnChange = function (event) {
	console.log('Watcher: ' + event.path + ' (' + event.type + ')');
};

//const clearReadOnlyFlag = function (stream, file) {
//	if ((file.stat.mode & 146) === 0) {
//		file.stat.mode = file.stat.mode | 146;
//	}
//	return stream;
//};

//const errorHandler = function (err) {
//	if (err) console.log(err);
//};

gulp.task('default', ['copy', 'css', 'js']);

gulp.task('clean', function(){
	return del([].concat(cleanDest.css, cleanDest.js, cleanDest.fonts));
});

gulp.task('copy', function(){
	var jsCopy = gulp.src(copies.js)
		.pipe(gulp.dest(dest.js));
	var fontsCopy = gulp.src(copies.fonts)
		.pipe(gulp.dest(dest.fonts));
	return merge(jsCopy, fontsCopy);
});

gulp.task('css', function(){
	return gulp.src(src.sass)
		.pipe(sass(sassOptions).on('error', sass.logError))
		.pipe(gulp.dest(dest.css))
		.pipe(sourcemaps.init(mapsInitOptions))
		.pipe(postcss([
			autoprefixer(autoprefixerOptions),
			cleancss(cleanCssOptions)
		]))
		.pipe(rename(renameMinOptions))
		.pipe(sourcemaps.write(dest.maps, mapsWriteOptions))
		.pipe(gulp.dest(dest.css));
});

gulp.task('js', ['js:bootstrap', 'js:main']);

gulp.task('js:bootstrap', function(){
	return gulp.src(src.bootstrapjs)
		.pipe(concat(dest.bootstrapjs))
		.pipe(uglify(uglifyOptions))
		.pipe(gulp.dest(dest.js));
});

gulp.task('js:main', function(){
	return gulp.src(src.js)
		.pipe(sourcemaps.init(mapsInitOptions))
		.pipe(uglify(uglifyOptions))
		.pipe(rename(renameMinOptions))
		.pipe(sourcemaps.write(dest.maps, mapsWriteOptions))
		.pipe(gulp.dest(dest.js));
});

gulp.task('watch', function(){
	gulp.watch(src.sass, ['css']).on('change', watcherOnChange);
	gulp.watch(src.js, ['js:main']).on('change', watcherOnChange);
});