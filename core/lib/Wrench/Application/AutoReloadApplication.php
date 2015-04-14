<?php

/*!
 * Content Sync Broadcaster, v0.1
 *
 * Copyright (c) 2013-2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Continuously pushes data from latest-change.txt to attached browsers.
 * latest-change.txt is modified by the watch feature of Pattern Lab. Attached
 * browsers should refresh when they see the data change.
 *
 */

namespace Wrench\Application;

use Wrench\Application\Application;
use Wrench\Application\NamedApplication;

class AutoReloadApplication extends Application {
	
	protected $newlines         = false;
	protected $clients          = array();
	protected $savedTimestamp   = null;
	protected $c                = false;
	protected $filename         = "";
	
	/**
	* Set the saved timestamp. If the latest-change file doesn't exist simply use the current time as the saved time
	*/
	public function __construct($newlines, $config) {
		
		$this->newlines = $newlines;
		$this->filename = __DIR__."/../../../../".$config['publicDir']."/latest-change.txt";

		if (file_exists($this->filename)) {
			$this->savedTimestamp = file_get_contents($this->filename);
		} else {
			$this->savedTimestamp = time();
		}
		
	}
	
	/**
	* When a client connects add it to the list of connected clients
	*/
	public function onConnect($client) {
		$id = $client->getId();
		$this->clients[$id] = $client;
	}
	
	/**
	* When a client disconnects remove it from the list of connected clients
	*/
	public function onDisconnect($client) {
		$id = $client->getId();
		unset($this->clients[$id]);
	}
	
	/**
	* Dead function in this instance
	*/
	public function onData($data, $client) {
		// function not in use
	}
	
	/**
	* Sends out a message once a second to all connected clients containing the contents of latest-change.txt
	*/
	public function onUpdate() {
		
		if (file_exists($this->filename)) {
			$readTimestamp = file_get_contents($this->filename);
			if ($readTimestamp != $this->savedTimestamp) {
				print "pattern lab updated. alerting connected browsers...\n";
				foreach ($this->clients as $sendto) {
					$sendto->send($readTimestamp);
				}
				$this->savedTimestamp = $readTimestamp;
			} else {
				if ($this->newlines) print "\n";
			}
		}
		
	}

}
