module.exports = function(grunt) {

  // Project configuration.
  grunt.initConfig({





    clean: {
      css: ['source/css/'],
    },





    sass: {
      style: {
        options: {
          //style: 'compressed'
          style: 'nested'
        },
        files: {
          'source/css/style.css': 'source/_sass/style.scss'
        }
      },
      'style-min': {
        options: {
          style: 'compressed'
        },
        files: {
          'source/css/style.min.css': 'source/_sass/style.scss'
        }
      },
    },





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
  grunt.loadNpmTasks('grunt-contrib-clean');
  grunt.loadNpmTasks('grunt-contrib-sass');
  grunt.loadNpmTasks('grunt-gh-pages');

  // Init Pattern Lab
  grunt.registerTask('init', 'Init Pattern lab',['shell:patternlab-public', 'shell:patternlab-styleguide'])

  // Build stylesheets
  grunt.registerTask('css', 'Build stylesheets', function() {
    grunt.task.run('clean:css');
    grunt.task.run('sass');
  });

  // Default task(s).
  grunt.registerTask('default', 'build Pattern Lab', function() {
    grunt.log.subhead("Build everything !".magenta);
    grunt.task.run('css');
    grunt.task.run('shell_patternlab');
  });

  // Init Pattern Lab
  grunt.registerTask('deploy', 'Deploy Pattern Lab on gh-pages',['gh-pages'])
};
