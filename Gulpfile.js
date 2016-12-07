'use strict';

var autoprefixer = require('autoprefixer'),
    cleancss = require('postcss-clean'),
    del = require('del'),
    gulp = require('gulp'),
    gulpSequence = require('gulp-sequence'),
    merge = require('merge-stream'),
    postcss = require('gulp-postcss'),
    rename = require('gulp-rename'),
	sass = require('gulp-sass'),
	sourcemaps = require('gulp-sourcemaps'),
	uglify = require('gulp-uglify')

var src = {
    sass: [
        'dl-res/css/main.scss'
    ],
    js: [
        'dl-res/js/main.js'
    ]
};
var dest = {
    css: 'tests/css',
    js: 'tests/js'
}

var autoprefixerOptions = {
	browsers: [
		'last 1 version',
        '> 10%',
        'not ie <= 11'
	]
};

var sassOptions = {
	indentType: 'tab',
	indentWidth: 1,
	linefeed: 'crlf'
};

var mapsInitOptions = {
	loadMaps: true
};

var mapsWriteOptions = {
	includeContent: false
};

var uglifyOptions = {
	compress: {
		drop_console: false
	},
	output: {
		quote_keys: true
	}
};

var cleanCssOptions = {
	keepSpecialComments: 0
};

var renameMinOptions = {
	suffix: '.min'
};

var watcherOnChange = function (event) {
	console.log('Watcher: ' + event.path + ' (' + event.type + ')');
};

//var clearReadOnlyFlag = function (stream, file) {
//	if ((file.stat.mode & 146) === 0) {
//		file.stat.mode = file.stat.mode | 146;
//	}
//	return stream;
//};

//var errorHandler = function (err) {
//	if (err) console.log(err);
//};

// TODO copy task
// TODO src/dist folders logic

gulp.task('default', ['css', 'js']);

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
        .pipe(sourcemaps.write('.', mapsWriteOptions))
        .pipe(gulp.dest(dest.css));
});

gulp.task('js', function(){
    return gulp.src(src.js)
        .pipe(sourcemaps.init(mapsInitOptions))
        .pipe(uglify(uglifyOptions))
        .pipe(rename(renameMinOptions))
        .pipe(sourcemaps.write('.', mapsWriteOptions))
        .pipe(gulp.dest(dest.js));
});

gulp.task('watch', function(){
    gulp.watch(src.sass, ['css']).on('change', watcherOnChange);
    gulp.watch(src.js, ['js']).on('change', watcherOnChange);
});