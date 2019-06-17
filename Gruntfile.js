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
                    'node_modules/sweetalert/dist/sweetalert.min.js',
                    'src/js/uikit-icon.js',
                    'src/js/backend.js',
                ],
                dest: 'bundle/js/backend-bundle.js'
            },
            //
            frontend: {
                src: [
                    'src/js/uikit.js',
                    'src/js/frontend.js',
                ],
                dest: 'bundle/js/frontend-bundle.js'
            },
        },

        watch: {
            concat_js: {
                files: ['src/js/*.js'],
                tasks: ['concat']
            },
        },
    });

    // Load the plugin that provides the "uglify" task.
    // grunt.loadNpmTasks('grunt-contrib-uglify');

    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.registerTask('default');

};
