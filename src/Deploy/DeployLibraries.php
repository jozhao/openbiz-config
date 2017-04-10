<?php

namespace XiNG\OpenBiz\Deploy;

use Composer\Script\Event;
use Composer\Util\ProcessExecutor;
use Drupal\Component\Serialization\Yaml;

class DeployLibraries
{

    /**
     * @param Event $event
     */
    public static function deployLibraries(Event $event)
    {
        $extra = $event->getComposer()->getPackage()->getExtra();
        if (isset($extra['installer-paths'])) {
            foreach ($extra['installer-paths'] as $path => $criteria) {
                if (array_intersect(['drupal/openbiz', 'type:drupal-profile'], $criteria)) {
                    $profile = $path;
                }
            }
            if (isset($profile)) {
                $profile = str_replace('{$name}', 'openbiz', $profile);
                $executor = new ProcessExecutor($event->getIO());
                $output = null;
                $executor->execute('npm run install-libraries', $output, $profile);
            }
        }
    }

}
