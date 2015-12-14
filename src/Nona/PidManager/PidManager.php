<?php

namespace Nona\PidManager;

/**
 * Process ID manager.
 *
 * This class helps manage the creation, and deletion of a process id file (pid). This is useful when wanting to ensure
 * that a script isn't already running and prevent it from executing again.
 *
 * Eg: script executes every 1 minute via a cronjob. The script takes 5 minutes to execute. When the cron tried to
 *     execute it again on minute 2, it won't be able to as there is a pid file which will prevent it from being
 *     executed again.
 *
 * @package Nona
 */
class PidManager
{
    private $pidFilename;
    private $pid;
    private $basePath;
    private $isThrowExceptions;

    /**
     * Default constructor
     *
     * @param string $pidFilename name of pid file
     * @param string $basePath path to pid file. defaults to current directory
     * @param bool $isThrowExceptions Throws exceptions when true.
     */
    public function __construct($pidFilename, $basePath = './', $isThrowExceptions = true)
    {
        $this->pidFilename = $pidFilename;
        $this->basePath = $basePath;
        $this->isThrowExceptions = $isThrowExceptions;
        // build the pid path
        $this->pid = $basePath . '/' . $pidFilename;
    }

    /**
     * Check to see if the script is locked for processing.
     *
     * This method will do some cleanup too. If the pid is greater than X min old, then the file will be removed and
     * will return an unlocked status. This is to help prevent an issue where the process gets locked and 'unlock' was
     * never called. This could be from an unhandled exception during execution. Normally best practice to wrap the code
     * you are locking in a try {} finally {} block and execute unlock() in finally {}. This will help ensure the file
     * is always cleaned up.
     *
     * @return boolean returns true if locked
     */
    public function isLocked()
    {
        return (boolean) $this->_getLockProcessId();
    }

    /**
     * Lock the process by writing a pid file.
     *
     * @return bool
     * @throws \Exception
     */
    public function lock()
    {
        if (!$this->isLocked()) {
            file_put_contents($this->pid, getmypid());

            return true;
        } else {
            return $this->_handleError('Lock file (' . $this->pid . ') exists already. Unable to lock');
        }
    }

    /**
     * Unlock the process by removing the pid file.
     *
     * @throws \Exception
     */
    public function unlock()
    {
        if ($this->isLocked()) {
            unlink($this->pid);

            return true;
        } else {
            return $this->_handleError('Lock file does not exist. Unable to unlock');
        }
    }

    /**
     * Check to see if the current lock is owned by the current process
     *
     * Note: This method does not work on Windows as it requires /proc to get the pid()
     *
     * @throws \Exception thrown when running on windows.
     * @return bool returns true if owner of current lock
     */
    public function isLockOwner() {
        if ($this->_isWindows()) {
            throw new \Exception('Unable to use isLockOwner() on a Windows system as it requires /proc');
        }

        return ($this->_getLockProcessId() === getmypid());
    }


    /**
     * Get the pid from the lock file if it is present
     *
     * @return int
     */
    private function _getLockProcessId() {
        $pid = 0;

        if (file_exists($this->pid)) {
            $pidFileContents = file_get_contents($this->pid);
            $pid = (int) $pidFileContents;
        }

        return $pid;
    }

    /**
     * Helper function to either throw an exception or return false based on the isThrowExceptions boolean.
     *
     * @param string $message Exception message
     * @return bool returns false
     * @throws \Exception
     */
    private function _handleError($message)
    {
        if ($this->isThrowExceptions) {
            throw new \Exception($message);
        } else {
            return false;
        }
    }

    /**
     * Check to see if we are running on windows
     */
    private function _isWindows()
    {
        return (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
    }
}