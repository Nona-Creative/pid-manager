# PID Manager
PID manager to lock php file execution.

## Installation
Install the latest version

``` composer require nona-creative/pid-manager ```

## Basic Usage

``` php
<?php

$pidManager = new \Nona\PidManager('test.lock', './');

if (!$pidManager->isLocked()) {
    try {
        $pidManager->lock();

        // Do you processing here
        // ...
    } finally {
        $pidManager->unlock();
    }
}

```
