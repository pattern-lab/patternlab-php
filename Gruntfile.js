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
    'gh-pages': {
      options: {
        base: 'public'
      },
      src: ['**']
    }
  });

  // Load the plugins
  grunt.loadNpmTasks('grunt-shell');
  grunt.loadNpmTasks('grunt-gh-pages');

  // Default task(s).
  grunt.registerTask('default', 'build Pattern Lab', ['shell:patternlab']);

  // Init Pattern Lab
  grunt.registerTask('init', 'Init Pattern lab',['shell:patternlab-public', 'shell:patternlab-styleguide'])

  // Init Pattern Lab
  grunt.registerTask('deploy', 'Deploy Pattern Lab on gh-pages',['gh-pages'])
};
