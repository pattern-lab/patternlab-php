# Code Modifications

## Guidelines

All code modifications must following [PSR-0][], [PSR-1][], and [PSR-2][] as
outlined on the [PHP Framework Interop Group][php-fig].

An .editorconfig file is included to help setup your environment if your IDE supports
[EditorConfig][].

## Procedure

* Fork the repository and create a topic branch from where you want to base your work.
    * This is usually the master branch.
    * To quickly create a topic branch based on master; `git branch
      my_contribution master` then checkout the new branch with `git
      checkout my_contribution`.  Please avoid working directly on the
      `master` branch.
* Make commits of logical units.
* Check for unnecessary whitespace with `git diff --check` before committing.
* Make sure your commit messages are in the proper format.
    * If your commit messages do not follow this format, please do a
      `git rebase -i master` to reword your commit messages.

````
    Subject Line Describing Your Changes

    The body of your commit message should describe the behavior without your
    changes, why this is a problem, and how your changes fix the problem when
    applied.
````

* Make sure you have added the necessary tests for your changes.
* Run all the tests to assure nothing else was accidentally broken.

[PSR-0]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md
[PSR-1]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
[PSR-2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[php-fig]: http://www.php-fig.org
[EditorConfig]: http://editorconfig.org/

