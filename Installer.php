<?php
/**
 * Created by PhpStorm.
 * User: AlexanderC <alexanderc@pycoding.biz>
 * Date: 4/22/15
 * Time: 14:39
 */

use Composer\Installer\PackageEvent as Event;
use Composer\IO\IOInterface;

/**
 * Class Installer
 */
class Installer 
{
    /**
     * @var bool
     */
    protected static $verbose;

    /**
     * @var string
     */
    protected static $buildPath;

    /**
     * @var IOInterface
     */
    protected static $io;

    /**
     * @param Event $event
     */
    public static function install(Event $event)
    {
        static::init($event);

        static::msg('Installing daemonizer.');

        static::msg('Configuring...');
        passthru(sprintf('sh configure --prefix=%s', escapeshellarg(static::$buildPath)));

        static::msg('Making...');
        passthru('make');

        static::msg('Installing...');
        passthru(sprintf('export DESTDIR=%s && make -j4 install', escapeshellarg(static::$buildPath)));

        $lastCommitHash = static::getLastCommitHash();
        static::msg(sprintf('Creating build lock %s...', $lastCommitHash));
        passthru(sprintf('touch %s', escapeshellarg(static::getLockFile($lastCommitHash))));
    }

    /**
     * @param Event $event
     */
    public static function update(Event $event)
    {
        static::msg('Checking for daemonizer build lock...');

        $lastCommitHash = static::getLastCommitHash();
        $lockFile = static::getLockFile($lastCommitHash);

        if(!$lockFile) {
            static::msg(sprintf('Missing build lock %s...', $lastCommitHash));
            static::install($event);
        } else {
            static::msg(sprintf('Up to date. Skipping...', $lastCommitHash));
        }
    }

    /**
     * @param string $commitHash
     * @return string
     */
    protected static function getLockFile($commitHash)
    {
        return sprintf('%s/%s.build.lock', static::$buildPath, $commitHash);
    }

    /**
     * @param string $text
     */
    protected static function msg($text)
    {
        if (static::$verbose) {
            static::$io->write($text);
        }
    }

    /**
     * @param string $text
     */
    protected static function error($text)
    {
        if (static::$verbose) {
            static::$io->writeError($text);
        }
    }

    /**
     * @return string
     */
    protected static function getLastCommitHash()
    {
        return shell_exec('git log -1 --format="%H"');
    }

    /**
     * @param Event $event
     */
    protected static function init(Event $event)
    {
        static::$io = $event->getIO();
        static::$verbose = static::$io->isVerbose();
        static::$buildPath = __DIR__;
    }
}