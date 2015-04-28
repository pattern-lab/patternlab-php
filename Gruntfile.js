module.exports = function(grunt) {

  // Project configuration.
  grunt.initConfig({
    // Build Pattern Lab
    shell: {
      patternlab: {
        command: "php core/builder.php -g"
      },
    },
  });

  // Load the plugin that provides the "uglify" task.
  grunt.loadNpmTasks('grunt-shell');

  // Default task(s).
  grunt.registerTask('default', ['shell']);

};
