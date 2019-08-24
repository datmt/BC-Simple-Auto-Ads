module.exports = function(grunt) {

    require('load-grunt-tasks')(grunt);
    // Project configuration.
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        concat: {
            options: {
                sourceMap: true,
                mangle: true,
                compress: true
            },
            backend: {
                src: [
                    'src/js/uikit.js',
                    'src/js/lightcase.js',
                    'src/js/sweetalert-dev.js',
                    'src/js/uikit-icon.js',
                    'src/js/backend.js',
                ],
                dest: 'bundle/js/backend-bundle.js'
            },
            frontend: {
                src: [
                    'src/js/uikit.js',
                    'src/js/frontend.js',
                ],
                dest: 'bundle/js/frontend-bundle.js'
            },
        },
        uglify: {
            options: {
                compress: true,
                mangle: true,
                sourceMap: true
            },
            dist: {
                files: {
                    'bundle/js/frontend-bundle.min.js': 'bundle/js/frontend-bundle.js',
                    'bundle/js/backend-bundle.min.js': 'bundle/js/backend-bundle.js',

                }
            }
        },

        watch: {
            concat_js: {
                files: ['src/js/*.js'],
                tasks: ['concat', 'uglify']
            },
        },
    });

    // Load the plugin that provides the "uglify" task.
    // grunt.loadNpmTasks('grunt-contrib-uglify');

    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-uglify-es');
    grunt.registerTask('default');

};
