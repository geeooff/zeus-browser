/// <binding BeforeBuild='build' Clean='clean' />
'use strict';

const   del = require('del'),
        gulp = require('gulp'),
        autoprefixer = require('gulp-autoprefixer'),
        cleanCss = require('gulp-clean-css'),
        concat = require('gulp-concat'),
        rename = require('gulp-rename'),
        sass = require('gulp-sass')(require('sass')),
        sourcemaps = require('gulp-sourcemaps'),
        uglify = require('gulp-uglify'),
        bootstrapPackage = require('./node_modules/bootstrap/package.json');

const src = {
	sass: [
		'dl-res/css/main.scss'
	],
	css: [
		'dl-res/css/main.css'
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
		'dl-res/js/*.map',
		'dl-res/js/jquery.*.js',
		'dl-res/js/dataTables.*.js'
	],
	fonts: [
		'dl-res/fonts/**/*'
	]
};

const autoprefixerOptions = {
    browsers: bootstrapPackage.browserslist
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

// js tasks
//

function jsClean() {
    return del(cleanDest.js);
}

function jsCopy() {
	return gulp.src(copies.js)
		.pipe(gulp.dest(dest.js));
}

function jsMinifyBootstrap() {
	return gulp.src(src.bootstrapjs)
		.pipe(concat(dest.bootstrapjs))
		.pipe(uglify(uglifyOptions))
		.pipe(gulp.dest(dest.js));
}

function jsMinifyMain() {
	return gulp.src(src.js)
		.pipe(sourcemaps.init(mapsInitOptions))
		.pipe(uglify(uglifyOptions))
		.pipe(rename(renameMinOptions))
		.pipe(sourcemaps.write(dest.maps, mapsWriteOptions))
		.pipe(gulp.dest(dest.js));
}

const jsTask = gulp.series(
	jsMinifyBootstrap,
	jsMinifyMain
);

function jsWatch() {
    return gulp.watch(src.js, jsTask);
}

// css tasks
//

function cssClean() {
    return del(cleanDest.css);
}

function cssTranspile() {
    return gulp.src(src.sass)
        .pipe(sourcemaps.init(mapsInitOptions))
        .pipe(sass(sassOptions).on('error', sass.logError))
        .pipe(sourcemaps.write('.', mapsWriteOptions))
        .pipe(gulp.dest(dest.css));
}

function cssMinify() {
    return gulp.src(src.css)
        .pipe(sourcemaps.init(mapsInitOptions))
        .pipe(autoprefixer(autoprefixerOptions))
        .pipe(cleanCss(cleanCssOptions))
        .pipe(rename(renameMinOptions))
        .pipe(sourcemaps.write('.', mapsWriteOptions))
        .pipe(gulp.dest(dest.css));
}

function cssTranspileWatch() {
    return gulp.watch(src.sass, cssTranspile);
}

function cssMinifyWatch() {
    return gulp.watch(src.css, cssMinify);
}

// fonts tasks
//

function fontsClean() {
	return del(cleanDest.fonts);
}

function fontsCopy() {
	return gulp.src(copies.fonts)
		.pipe(gulp.dest(dest.fonts));
}

// public tasks
//

exports.clean = gulp.parallel(cssClean, jsClean, fontsClean);

exports.build = gulp.series(
	gulp.parallel(
		jsCopy,
		fontsCopy
	),
    gulp.parallel(
        jsTask,
        gulp.series(
            cssTranspile,
            cssMinify
        )
    )
);

exports.watch = function() {
    cssTranspileWatch();
    cssMinifyWatch();
    jsWatch();
};

exports.default = this.build;
