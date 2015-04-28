module.exports = function(grunt) {

  // Project configuration.
  grunt.initConfig({





    clean: {
      css: ['source/css/'],
      js: ['source/js/'],
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





    autoprefixer: {
      options: {
        browsers: ['last 2 version', '> 5% in CH', 'IE >= 8', 'Firefox >= 31', 'Firefox ESR'],
        map: true
      },
      /**
       * Autoprefix the CSS
       */
      css: {
        src: 'source/css/style.css',
        dest: 'source/css/style.css'
      },
      'css-min': {
        src: 'source/css/style.min.css',
        dest: 'source/css/style.min.css'
      },

    },





    jshint: {
      beforeconcat: ['Gruntfile.js', 'source/_js/**/*.js'],
      afterconcat: ['source/js/**/*.js']
    },





    uglify: {
      scripts: {
        files: {
        'source/js/script.min.js': [
          'source/_js/fitvids.js',
          'source/_js/init.js',
          'source/_js/jquery-2.0.0b2.js',
          'source/_js/modernizr.js'
          ]
      }
      }
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
  grunt.loadNpmTasks('grunt-autoprefixer');
  grunt.loadNpmTasks('grunt-contrib-jshint');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-gh-pages');

  // Init Pattern Lab
  grunt.registerTask('init', 'Init Pattern lab',['shell:patternlab-public', 'shell:patternlab-styleguide'])

  // Build stylesheets
  grunt.registerTask('css', 'Build stylesheets', function() {
    grunt.task.run('clean:css');
    grunt.task.run('sass');
    grunt.task.run('autoprefixer');
  });

  // Build stylesheets
  grunt.registerTask('js', 'Build javascripts', function() {
    grunt.task.run('clean:js');
    grunt.task.run('uglify');
    //grunt.task.run('autoprefixer');
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
