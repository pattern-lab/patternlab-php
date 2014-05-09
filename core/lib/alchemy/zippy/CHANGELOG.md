CHANGELOG
---------

* 0.2.0 (04-04-2014)

  * Fix the use of "teleporter" for local files
  * Fix adding a new file using tar adapter ( --append option )
  * Allow all adapters to be instantiated even if they are not supported
  * Move support detection logic in distinct classes
  * Add support for archives relative path
  * Use Symfony Process working directory instead of changing working directory
  * Archive in context when a single resource is added

* 0.1.1 (04-12-2013)

  * Throw exception in case chdir failed
  * Use guzzle stream download to handle large files without large memory usage

* 0.1.0 (11-03-2013)

  * First stable version.
  * Support for GNUtar, BSDtar, Zip, PHPZip.
