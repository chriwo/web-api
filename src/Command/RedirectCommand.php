<?php
declare(strict_types=1);

namespace Myracloud\WebApi\Command;


use Myracloud\WebApi\Endpoint\AbstractEndpoint;
use Myracloud\WebApi\Endpoint\Redirect;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RedirectCommand extends AbstractCrudCommand
{

    static $redirTypes = [
        AbstractEndpoint::REDIRECT_TYPE_REDIRECT,
        AbstractEndpoint::REDIRECT_TYPE_PERMANENT,
    ];

    static $matchTypes = [
        AbstractEndpoint::MATCHING_TYPE_SUFFIX,
        AbstractEndpoint::MATCHING_TYPE_PREFIX,
        AbstractEndpoint::MATCHING_TYPE_EXACT,
    ];

    /**
     *
     */
    protected function configure()
    {
        $this->setName('myracloud:api:redirect');

        $this->addOption('source', null, InputOption::VALUE_REQUIRED, 'Source path', null);
        $this->addOption('dest', null, InputOption::VALUE_REQUIRED, 'destination path', null);

        $this->addOption('type', null, InputOption::VALUE_REQUIRED, 'Type of redirect (' . implode(',', self::$redirTypes) . ')', AbstractEndpoint::REDIRECT_TYPE_REDIRECT);
        $this->addOption('matchtype', null, InputOption::VALUE_REQUIRED, 'Type of substring matching (' . implode(',', self::$matchTypes) . ')', AbstractEndpoint::MATCHING_TYPE_PREFIX);


        $this->setDescription('Redirect commands allow you to edit Url Redirects.');
        $this->setHelp(sprintf(<<<'TAG'
Only passing fqdn without additional options will list all Redirect entries.

<fg=yellow>Example Listing all Redirect entries:</>
bin/console myracloud:api:redirect <fqdn>

<fg=yellow>Example creating a new Redirect entry:</>
bin/console myracloud:api:redirect <fqdn> -o create --source <pattern-to-match> --dest <target-url>  --type <redirection-type> --matchtype <type>

<fg=yellow>Example updating a existing Redirect entry:</>
bin/console myracloud:api:redirect <fqdn> -o update --id <id-from-list> --source <pattern-to-match> --dest <target-url> --type <redirection-type> --matchtype <matchtype>

<redirection-type> can be %s
<matchtype> can be %s

<fg=yellow>Example deleting a existing Redirect entry:</>
bin/console myracloud:api:redirect -o delete --id <id-from-list>
TAG
                , implode(',', self::$redirTypes), implode(',', self::$matchTypes))
        );
        parent::configure();
    }

    /**
     * @param array           $options
     * @param OutputInterface $output
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function OpCreate(array $options, OutputInterface $output)
    {
        /** @var Redirect $endpoint */
        $endpoint = $this->getEndpoint();

        if (empty($options['source'])) {
            throw new \RuntimeException('You need to define source path via --source');
        }
        if (empty($options['dest'])) {
            throw new \RuntimeException('You need to define destination path via --dest');
        }
        if (empty($options['type'])) {
            throw new \RuntimeException('You need to define Matching type via --type');
        } elseif (!in_array($options['type'], self::$redirTypes)) {
            throw new \RuntimeException('--type has to be one of ' . implode(',', self::$redirTypes));
        }

        if (empty($options['matchtype'])) {
            throw new \RuntimeException('You need to define Matching type via --matchtype');
        } elseif (!in_array($options['matchtype'], self::$matchTypes)) {
            throw new \RuntimeException('--matchtype has to be one of ' . implode(',', self::$matchTypes));
        }
        $return = $endpoint->create(
            $options['fqdn'],
            $options['source'],
            $options['dest'],
            $options['type'],
            $options['matchtype'],
            false
        );
        $this->handleTableReturn($return, $output);
    }

    /**
     * @return AbstractEndpoint
     */
    protected function getEndpoint(): AbstractEndpoint
    {
        return $this->webapi->getRedirectEndpoint();
    }

    /**
     * @param                 $data
     * @param OutputInterface $output
     */
    protected function writeTable($data, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders([
            'Id',
            'Created',
            'Modified',
            'Source',
            'Destination',
            'Type',
            'Subdomain',
            'MatchType',
        ]);

        foreach ($data as $item) {
            $table->addRow([
                array_key_exists('id', $item) ? $item['id'] : null,
                $item['created'],
                $item['modified'],
                @$item['source'],
                @$item['destination'],
                @$item['type'],
                $item['subDomainName'],
                $item['matchingType'],
            ]);
        }
        $table->render();
    }

    /**
     * @param array           $options
     * @param OutputInterface $output
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function OpUpdate(array $options, OutputInterface $output)
    {
        /** @var Redirect $endpoint */
        $endpoint = $this->getEndpoint();
        $existing = $this->findById($options);

        if (empty($options['source'])) {
            $options['source'] = $existing['source'];
        }
        if (empty($options['dest'])) {
            $options['dest'] = $existing['destination'];
        }
        if (empty($options['type'])) {
            $options['type'] = $existing['type'];
        }
        if (!in_array($options['type'], self::$redirTypes)) {
            throw new \RuntimeException('--type has to be one of ' . implode(',', self::$redirTypes));
        }

        if (empty($options['matchtype'])) {
            $options['matchtype'] = $existing['matchtype'];
        }

        if (!in_array($options['matchtype'], self::$matchTypes)) {
            throw new \RuntimeException('--matchtype has to be one of ' . implode(',', self::$matchTypes));
        }

        $return = $endpoint->update(
            $options['fqdn'],
            $options['id'],
            new \DateTime($existing['modified']),
            $options['source'],
            $options['dest'],
            $options['type'],
            $options['matchtype'],
            false
        );

        $this->handleTableReturn($return, $output);
    }
}