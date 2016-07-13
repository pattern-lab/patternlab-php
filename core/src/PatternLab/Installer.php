<?php

/*!
 * Installer Class
 *
 * Copyright (c) 2016 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * References the InstallerUtil class that is included in pattern-lab/core
 *
 */

namespace PatternLab;

use \Composer\Script\Event;
use \Composer\Installer\PackageEvent;
use \PatternLab\InstallerUtil;

class Installer {
  
  protected static $installerInfo = array("projectInstall" => false, "packagesRemove" => false, "suggestedStarterKits" => array(), "configOverrides" => array(), "patternLabPackages" => array());
  
  /**
   * Get any config overrides that may exist for the edition
   * @param  {Object}     a script event object from composer
   */
  public static function getConfigOverrides(Event $event) {
    
    $extra = $event->getComposer()->getPackage()->getExtra();
    if (isset($extra["patternlab"]) && isset($extra["patternlab"]["config"]) && is_array($extra["patternlab"]["config"])) {
      self::$installerInfo["configOverrides"] = $extra["patternlab"]["config"];
    }
    
  }
  
  /**
   * Get the package info from each patternlab-* package's composer.json
   * @param  {String}     the type of event fired during the composer install
   * @param  {Object}     a script event object from composer
   */
  public static function getPackageInfo($type, $event) {
    
    $package      = ($type == "update") ? $event->getOperation()->getTargetPackage() : $event->getOperation()->getPackage();
    $packageType  = $package->getType();
    $packageExtra = $package->getExtra();
    $packageInfo  = array();
    
    // make sure we're only evaluating pattern lab packages
    if (strpos($packageType,"patternlab-") !== false) {
      
      $packageInfo["name"]     = $package->getName();
      $packageInfo["type"]     = $packageType;
      $packageInfo["pathBase"] = $event->getComposer()->getInstallationManager()->getInstallPath($package);
      $packageInfo["pathDist"] = $packageInfo["pathBase"].DIRECTORY_SEPARATOR."dist".DIRECTORY_SEPARATOR;
      $packageInfo["extra"]    = (isset($packageExtra["patternlab"])) ? $packageExtra["patternlab"] : array();
      
      self::$installerInfo["packages"][] = $packageInfo;
      
    }
    
  }
  
  /**
   * Get the suggested starter kits from the root package composer.json
   * @param  {Object}     a script event object from composer
   */
  public static function getSuggestedStarterKits(Event $event) {
    
    $extra = $event->getComposer()->getPackage()->getExtra();
    if (isset($extra["patternlab"]) && isset($extra["patternlab"]["starterKitSuggestions"]) && is_array($extra["patternlab"]["starterKitSuggestions"])) {
      self::$installerInfo["suggestedStarterKits"] = $extra["patternlab"]["starterKitSuggestions"];
    }
    
  }
  
  /**
   * Run the centralized postInstallCmd
   * @param  {Object}     a script event object from composer
   */
  public static function postInstallCmd(Event $event) {
    
    InstallerUtil::postInstallCmd(self::$installerInfo, $event);
    
  }
  
  /**
   * Run the centralized postUpdateCmd
   * @param  {Object}     a script event object from composer
   */
  public static function postUpdateCmd(Event $event) {
    
    InstallerUtil::postUpdateCmd(self::$installerInfo, $event);
    
  }
  
  /**
   * Clean-up when a package is removed
   * @param  {Object}     a script event object from composer
   */
  public static function postPackageInstall(PackageEvent $event) {
    
    self::getPackageInfo("install", $event);
    
  }
  
  /**
   * Clean-up when a package is removed
   * @param  {Object}     a script event object from composer
   */
  public static function postPackageUpdate(PackageEvent $event) {
    
    self::getPackageInfo("update", $event);
    
  }
  
  /**
   * Clean-up when a package is removed
   * @param  {Object}     a script event object from composer
   */
  public static function prePackageUninstall(PackageEvent $event) {
    
    // make sure the postUpdateCmd doesnt actually do anything
    self::setPackagesRemove();
    
    // get the basic package info
    $package      = $event->getOperation()->getPackage();
    $packageType  = $package->getType();
    $packageInfo  = array();
    
    // make sure we're only evaluating pattern lab packages. remove attributes related to them.
    if (strpos($packageType,"patternlab-") !== false) {
      
      $packageInfo["name"]     = $package->getName();
      $packageInfo["type"]     = $packageType;
      $packageInfo["pathBase"] = $event->getComposer()->getInstallationManager()->getInstallPath($package);
      
      InstallerUtil::packageRemove($packageInfo);
      
    }
    
  }
  
  /**
   * Set the packages remove boolean to true
   */
  public static function setPackagesRemove() {
    
    self::$installerInfo["packagesRemove"] = true;
    
  }
  
  /**
   * Set the project install boolean to true
   * @param  {Object}     a script event object from composer
   */
  public static function setProjectInstall(Event $event) {
    
    self::$installerInfo["projectInstall"] = true;
    
  }
  
}
