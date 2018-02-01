'use strict';

module.exports = function(grunt) {
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    // the install require check
    required: {
      libs: {
        options: {
          // npm install missing modules
          install: true
        },
        // Search for require() in all js files in the src folder
        src: ['src/*.js']
      }
    },
    // Uglify JS
    uglify: {
      options: {
        mangle: true,
        compress: {
          drop_console: true
        }
      },
      all: {
        files: [{
          expand: true,
          cwd: 'assets/js/',
          src: ['*.js', '!*.min.js', '!assets/js/ext/*.js'],
          dest: 'assets/js/',
          ext: '.min.js'
        }]
      }
    },

    // Compile Sass
    sass: {
      dist: {
        options: {
          style: 'nested',
          unixNewlines: true
        },
        expand: true,
        cwd: 'assets/scss',
        src: ['*.scss'],
        dest: 'assets/css/',
        ext: '.css'
      },
      dev: {
        options: {
          style: 'nested',
          lineNumbers: true,
          unixNewlines: true
        },
        expand: true,
        cwd: 'assets/scss',
        src: ['*.scss'],
        dest: 'assets/css/',
        ext: '.css'
      },
    },

    // Minify CSS
    cssmin: {
      minify: {
        expand: true,
        cwd: 'assets/css',
        src: ['*.css', '!*.min.css'],
        dest: 'assets/css/',
        ext: '.min.css'
      }
    },

    // Watch for changes during development
    watch: {
      options: {
        livereload: true
      },
      styles: {
        files: ['assets/scss/*.scss','assets/scss/**/*.scss'],
        tasks: ['sass:dist','cssmin']
      },
      scripts: {
        files: ['assets/js/*.js', '!assets/js/*.min.js', '!assets/js/ext/*.js'],
        tasks: ['uglify']
      },
    }
  });

  // Load plugins
  grunt.loadNpmTasks('grunt-required');
  grunt.loadNpmTasks('grunt-check-modules');
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-contrib-compress');
  grunt.loadNpmTasks('grunt-contrib-clean');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-sass');
  grunt.loadNpmTasks('grunt-contrib-cssmin');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-replace');

  // Tasks
  grunt.registerTask('getstuff', ['required']);

  // Do our install check
  grunt.registerTask('default', ['check-modules']);

  // Compile SASS and minify assets
  grunt.registerTask('pre-commit', ['sass:dist', 'cssmin', 'uglify:all']);

};
