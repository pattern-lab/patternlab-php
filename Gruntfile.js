module.exports = function(grunt) {

  // Project configuration.
  grunt.initConfig({
    // Build Pattern Lab
    shell: {
      'patternlab': {
        command: "php core/builder.php -g"
      },
      'patternlab-public': {
        command: "mkdir public"
      },
      'patternlab-styleguide': {
        command: "cp -rf core/styleguide public/styleguide"
      }
    },
  });

  // Load the plugin that provides the "uglify" task.
  grunt.loadNpmTasks('grunt-shell');

  // Default task(s).
  grunt.registerTask('default', ['shell:patternlab']);

  // Init Pattern Lab
  grunt.registerTask('init', ['shell:patternlab-public', 'shell:patternlab-styleguide'])
};
