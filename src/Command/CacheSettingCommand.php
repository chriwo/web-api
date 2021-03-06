<?php
declare(strict_types=1);

namespace Myracloud\WebApi\Command;

use Myracloud\WebApi\Endpoint\AbstractEndpoint;
use Myracloud\WebApi\Endpoint\CacheSetting;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CacheSettingCommand
 *
 * @package Myracloud\API\Command
 */
class CacheSettingCommand extends AbstractCrudCommand
{

    static $matchingTypes = [
        CacheSetting::MATCHING_TYPE_PREFIX,
        CacheSetting::MATCHING_TYPE_EXACT,
        CacheSetting::MATCHING_TYPE_SUFFIX,
    ];

    /**
     *
     */
    protected function configure()
    {
        $this->setName('myracloud:api:cacheSetting');
        $this->addOption('path', 'p', InputOption::VALUE_REQUIRED, 'Path to match against', null);
        $this->addOption('ttl', null, InputOption::VALUE_REQUIRED, 'time to live', null);
        $this->addOption('type', null, InputOption::VALUE_REQUIRED, 'Type of match (' . implode(',', self::$matchingTypes) . ')', null);
        $this->setDescription('CacheSetting allows you to define/modify Cache rules.');
        $this->setHelp(<<<'TAG'
Only passing fqdn without additional options will display all known settings.

<fg=yellow>Example Listing all Cache Settings:</>
bin/console myracloud:api:cacheSetting <fqdn>

<fg=yellow>Example creating a setting for a Caching TTL of 1200 seconds for all resources under the page root:</>
bin/console myracloud:api:cacheSetting <fqdn> -o create --path / --ttl 1200 --type prefix

<fg=yellow>Example Deleting a existing Cache Setting:</>
bin/console myracloud:api:cacheSetting <fqdn> -o delete --id <id-from-list>
TAG
        );
        parent::configure();
    }


    /**
     * @param array           $options
     * @param OutputInterface $output
     */
    protected function OpCreate(array $options, OutputInterface $output)
    {
        if (empty($options['path'])) {
            throw new \RuntimeException('You need to define a path to match via --path');
        }
        if (empty($options['ttl'])) {
            throw new \RuntimeException('You need to define a time to live via --ttl');
        }
        if (empty($options['type'])) {
            throw new \RuntimeException('You need to define Matching type via --type');
        } elseif (!in_array($options['type'], self::$matchingTypes)) {
            throw new \RuntimeException('--type has to be one of ' . implode(',', self::$matchingTypes));
        }
        $endpoint = $this->getEndpoint();
        $return   = $endpoint->create($options['fqdn'], $options['path'], $options['ttl'], $options['type']);
        $this->handleTableReturn($return, $output);
    }

    /**
     * @return AbstractEndpoint
     */
    protected function getEndpoint(): AbstractEndpoint
    {
        return $this->webapi->getCacheSettingsEndpoint();
    }

    /**
     * @param                 $data
     * @param OutputInterface $output
     */
    protected function writeTable($data, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders(['Id', 'Created', 'Modified', 'Path', 'ttl', 'not found ttl', 'Type', 'Enforce', 'Sort']);

        foreach ($data as $item) {
            $table->addRow([
                array_key_exists('id', $item) ? $item['id'] : null,
                $item['created'],
                $item['modified'],
                $item['path'],
                $item['ttl'],
                $item['notFoundTtl'],
                $item['type'],
                $item['enforce'] ?: 0,
                $item['sort'],
            ]);
        }
        $table->render();
    }

    /**
     * @param array           $options
     * @param OutputInterface $output
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function OpUpdate(array $options, OutputInterface $output)
    {
        /** @var CacheSetting $endpoint */
        $endpoint = $this->getEndpoint();
        $existing = $this->findById($options);

        if (empty($options['path'])) {
            $options['path'] = $existing['path'];
        }
        if (empty($options['ttl'])) {
            $options['ttl'] = $existing['ttl'];
        }

        if (empty($options['type'])) {
            $options['type'] = $existing['type'];
        }
        if (!in_array($options['type'], self::$matchingTypes)) {
            throw new \RuntimeException('--type has to be one of ' . implode(',', self::$matchingTypes));
        }

        $return = $endpoint->update($options['fqdn'], $existing['id'], new \DateTime($existing['modified']), $options['path'], $options['ttl'], $options['type']);
        $this->handleTableReturn($return, $output);
    }
}