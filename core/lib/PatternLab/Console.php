<?php

/*!
 * Pattern Lab Console Class - v0.7.12
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Handles the set-up of the console commands, options, and documentation
 * Heavily influenced by the symfony/console output format
 *
 */

namespace PatternLab;

class Console {
	
	private $optionsShort = "h";
	private $optionsLong  = array("help");
	private $options      = array();
	private $commands     = array();
	private $self;
	
	/**
	* Set-up a default var
	*/
	public function __construct() {
		$this->self = $_SERVER["PHP_SELF"];
	}
	
	/**
	* Get the arguments that have been passed to the script via the commmand line
	*/
	public function getArguments() {
		if (php_sapi_name() != 'cli') {
			print "The builder script can only be run from the command line.\n";
			exit;
		}
		$this->options = getopt($this->optionsShort,$this->optionsLong);
	}
	
	/**
	* See if a particular command was passed to the script via the command line. Can either be the short or long version
	* @param  {String}       list of arguments to check
	*
	* @return {Boolean}      if the command has been passed to the script via the command line
	*/
	public function findCommand($args) {
		$args = explode("|",$args);
		foreach ($args as $arg) {
			if (isset($this->options[$arg])) {
				return true;
			}
		}
		return false;
	}
	
	/**
	* Return the command that was given in the command line arguments
	*
	* @return {String}      the command. passes false if no command was found
	*/
	public function getCommand() {
		foreach ($this->commands as $command => $attributes) {
			if (isset($this->options[$command]) || isset($this->options[$attributes["commandLong"]])) {
				return $command;
			}
		}
		return false;
	}
	
	/**
	* Set-up the command so it can be used from the command line
	* @param  {String}       the single character version of the command
	* @param  {String}       the long version of the command
	* @param  {String}       the description to be used in the "available commands" section of writeHelp()
	* @param  {String}       the description to be used in the "help" section of writeHelpCommand()
	*/
	public function setCommand($short,$long,$desc,$help) {
		$this->optionsShort .= $short;
		$this->optionsLong[] = $long;
		$this->commands[$short] = array("commandShort" => $short, "commandLong" => $long, "commandLongLength" => strlen($long), "commandDesc" => $desc, "commandHelp" => $help, "commandOptions" => array());
	}
	
	/**
	* See if a particular option was passed to the script via the command line. Can either be the short or long version
	* @param  {String}      list of arguments to check
	*
	* @return {Boolean}      if the command has been passed to the script via the command line
	*/
	public function findCommandOption($args) {
		$args = explode("|",$args);
		foreach ($args as $arg) {
			if (isset($this->options[$arg])) {
				return true;
			}
		}
		return false;
	}
	
	/**
	* Set-up an option for a given command so it can be used from the command line
	* @param  {String}       the single character of the command that this option is related to
	* @param  {String}       the single character version of the option
	* @param  {String}       the long version of the option
	* @param  {String}       the description to be used in the "available options" section of writeHelpCommand()
	* @param  {String}       the sample to be used in the "sample" section of writeHelpCommand()
	*/
	public function setCommandOption($command,$short,$long,$desc,$sample) {
		if (strpos($this->optionsShort,$short) === false) {
			$this->optionsShort .= $short;
		}
		if (!in_array($long,$this->optionsLong)) {
			$this->optionsLong[] = $long;
		}
		$this->commands[$command]["commandOptions"][$short] = array("optionShort" => $short, "optionLong" => $long, "optionLongLength" => strlen($long), "optionDesc" => $desc, "optionSample" => $sample);
	}
	
	/**
	* Write out the generic help
	*/
	public function writeHelp() {
		
		/*
		
		The generic help follows this format:
		
		Pattern Lab Console Options
		
		Usage:
		  php core/console command [options]
		
		Available commands:
		  --build   (-b)    Build Pattern Lab
		  --watch   (-w)    Build Pattern Lab and watch for changes and rebuild as necessary
		  --version (-v)    Display the version number
		  --help    (-h)    Display this help message.
		
		*/
		
		// find length of longest command
		$lengthLong = 0;
		foreach ($this->commands as $command => $attributes) {
			$lengthLong = ($attributes["commandLongLength"] > $lengthLong) ? $attributes["commandLongLength"] : $lengthLong;
		}
		
		// write out the generic usage info
		$this->writeLine("Pattern Lab Console Options",true);
		$this->writeLine("Usage:");
		$this->writeLine("  php ".$this->self." command [options]",true);
		$this->writeLine("Available commands:");
		
		// write out the commands
		foreach ($this->commands as $command => $attributes) {
			$spacer = $this->getSpacer($lengthLong,$attributes["commandLongLength"]);
			$this->writeLine("  --".$attributes["commandLong"].$spacer."(-".$attributes["commandShort"].")    ".$attributes["commandDesc"]);
		}
		
		$this->writeLine("");
		
	}
	
	/**
	* Write out the command-specific help
	* @param  {String}       the single character of the command that this option is related to
	*/
	public function writeHelpCommand($command = "") {
		
		/*
		
		The command help follows this format:
		
		Build Command Options
		
		Usage:
		  php core/console --build [--patternsonly|-p] [--nocache|-n] [--enablecss|-c]
		
		Available options:
		  --patternsonly (-p)    Build only the patterns. Does NOT clean public/.
		  --nocache      (-n)    Set the cacheBuster value to 0.
		  --enablecss    (-c)    Generate CSS for each pattern. Resource intensive.
		  --help         (-h)    Display this help message.
		
		Help:
		 The build command builds an entire site a single time. It compiles the patterns and moves content from source/ into public/
		
		 Samples:
		
		   To run and generate the CSS for each pattern:
		     php core/console build -c
		
		   To build only the patterns and not move other files from source/ to public/
		     php core/console build -p
		
		   To turn off the cacheBuster
		     php core/console build -n
		*/
		
		// if given an empty command or the command doesn't exist in the lists give the generic help
		if (empty($command)) {
			$this->writeHelp();
			return;
		}
		
		$commandShort      = $this->commands[$command]["commandShort"];
		$commandLong       = $this->commands[$command]["commandLong"];
		$commandHelp       = $this->commands[$command]["commandHelp"];
		$commandOptions    = $this->commands[$command]["commandOptions"];
		
		$commandLongUC = ucfirst($commandLong);
		
		// write out the option list and get the longest item
		$optionList = "";
		$lengthLong = 0;
		foreach ($commandOptions as $option => $attributes) {
			$optionList .= "[--".$attributes["optionLong"]."|-".$attributes["optionShort"]."] ";
			$lengthLong = ($attributes["optionLongLength"] > $lengthLong) ? $attributes["optionLongLength"] : $lengthLong;
		}
		
		// write out the generic usage info
		$this->writeLine($commandLongUC." Command Options",true);
		$this->writeLine("Usage:");
		$this->writeLine("  php ".$this->self." --".$commandLong."|-".$commandShort." ".$optionList,true);
		
		// write out the available options
		if (count($commandOptions) > 0) {
			$this->writeLine("Available options:");
			foreach ($commandOptions as $option => $attributes) {
				$spacer = $this->getSpacer($lengthLong,$attributes["optionLongLength"]);
				$this->writeLine("  --".$attributes["optionLong"].$spacer."(-".$attributes["optionShort"].")    ".$attributes["optionDesc"]);
			}
			$this->writeLine("");
		}
		
		$this->writeLine("Help:");
		$this->writeLine("  ".$commandHelp,true);
		
		// write out the samples
		if (count($commandOptions) > 0) {
			$this->writeLine("  Samples:",true);
			foreach ($commandOptions as $option => $attributes) {
				$this->writeLine("   ".$attributes["optionSample"]);
				$this->writeLine("     php ".$this->self." --".$commandLong." --".$attributes["optionLong"]);
				$this->writeLine("     php ".$this->self." -".$commandShort." -".$attributes["optionShort"],true);
			}
		}
		
	}
	
	/**
	* Write out a line of the help
	* @param  {Boolean}       handle double-break
	*/
	protected function writeLine($line,$doubleBreak = false) {
		$break = ($doubleBreak) ? "\n\n" : "\n";
		print $line.$break;
	}
	
	/**
	* Make sure the space is properly set between long command options and short command options
	* @param  {Integer}       the longest length of the command's options
	* @param  {Integer}       the character length of the given option
	*/
	protected function getSpacer($lengthLong,$itemLongLength) {
		$i            = 0;
		$spacer       = " ";
		$spacerLength = $lengthLong - $itemLongLength;
		while ($i < $spacerLength) {
			$spacer .= " ";
			$i++;
		}
		return $spacer;
	}
	
}
