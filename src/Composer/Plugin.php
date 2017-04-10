<?php

namespace XiNG\OpenBiz\Config\Composer;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use Composer\Script\Event;
use Composer\Util\ProcessExecutor;

/**
 * Class Plugin
 *
 * @package XiNG\OpenBiz\Config\Composer
 */
class Plugin implements PluginInterface, EventSubscriberInterface
{

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var
     */
    protected $eventDispatcher;

    /**
     * @var ProcessExecutor
     */
    protected $executor;

    /**
     * @var string
     */
    protected $OenBizPackageName = 'xing/openbiz-config';

    /**
     * @var PackageInterface
     */
    protected $OpenBizPackage;

    /**
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->eventDispatcher = $composer->getEventDispatcher();
        $this->io = $io;
        // Set timeout 3000.
        ProcessExecutor::setTimeout(3000);
        $this->executor = new ProcessExecutor($this->io);
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     */
    public static function getSubscribedEvents()
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL => "onPostPackageEvent",
            PackageEvents::POST_PACKAGE_UPDATE => "onPostPackageEvent",
            ScriptEvents::POST_UPDATE_CMD => 'onPostCmdEvent',
        ];
    }

    /**
     * Execute update after update command has been executed, if applicable.
     *
     * @param PackageEvent $event
     */
    public function onPostPackageEvent(PackageEvent $event)
    {
        $package = $this->getOpenBizPackage($event->getOperation());
        if ($package) {
            // By explicitly setting the package, the onPostCmdEvent() will
            // process the update automatically.
            $this->OpenBizPackage = $package;
        }
    }

    /**
     * @param Event $event
     */
    public function onPostCmdEvent(Event $event)
    {
        // Deploy profile libraries.
        \XiNG\OpenBiz\Deploy\DeployLibraries::deployLibraries($event);

        // Only install the template files if xing/openbiz-config was installed.
        if (isset($this->OpenBizPackage)) {
            $version = $this->OpenBizPackage->getVersion();
            $this->executeUpdate($version);
        }
    }

    /**
     * @param $operation
     *
     * @return null
     */
    protected function getOpenBizPackage($operation)
    {
        if ($operation instanceof InstallOperation) {
            $package = $operation->getPackage();
        } elseif ($operation instanceof UpdateOperation) {
            $package = $operation->getTargetPackage();
        }
        if (isset($package) && $package instanceof PackageInterface && $package->getName() == $this->OenBizPackageName) {
            return $package;
        }

        return null;
    }

    /**
     * @param $version
     */
    protected function executeUpdate($version)
    {
        $this->io->write('<comment>Skipping update of templated files</comment>');
    }

}
