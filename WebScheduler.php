<?php

declare(strict_types=1);

namespace WebScheduler;

use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\Finder\Finder;
use Thelia\Install\Database;
use Thelia\Module\BaseModule;

class WebScheduler extends BaseModule
{
    public const DOMAIN_NAME = 'webscheduler';

    public const ROUTER_NAME = 'router.webscheduler';

    public const OUTPUT_MAX_BYTES = 65_536;

    public function postActivation(?ConnectionInterface $con = null): void
    {
        if ($this->getConfigValue('is_initialized', false)) {
            return;
        }

        $sqlFile = __DIR__.'/Config/TheliaMain.sql';

        if (!is_file($sqlFile)) {
            throw new \RuntimeException(sprintf(
                'Missing %s. Run "php Thelia module:generate:sql WebScheduler" first, or ship the generated SQL with the module.',
                $sqlFile,
            ));
        }

        (new Database($con))->insertSql(null, [$sqlFile]);

        $this->setConfigValue('is_initialized', true);
    }

    public function update($currentVersion, $newVersion, ?ConnectionInterface $con = null): void
    {
        $updateDir = __DIR__.DS.'Config'.DS.'update';

        if (!is_dir($updateDir)) {
            return;
        }

        $finder = Finder::create()
            ->name('*.sql')
            ->depth(0)
            ->sortByName()
            ->in($updateDir);

        $database = new Database($con);

        foreach ($finder as $file) {
            if (version_compare($currentVersion, $file->getBasename('.sql'), '<')) {
                $database->insertSql(null, [$file->getPathname()]);
            }
        }
    }

    public static function configureServices(ServicesConfigurator $servicesConfigurator): void
    {
        $servicesConfigurator->load(self::getModuleCode().'\\', __DIR__)
            ->exclude([
                THELIA_MODULE_DIR.self::getModuleCode().'/I18n/*',
                THELIA_MODULE_DIR.self::getModuleCode().'/Model/*',
                THELIA_MODULE_DIR.self::getModuleCode().'/Enum/*',
            ])
            ->autowire(true)
            ->autoconfigure(true);
    }
}
