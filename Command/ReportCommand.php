<?php

namespace Staffim\RollbarBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Andrew Fureev <a.fureev@staffim.ru>
 */
class ReportCommand extends ContainerAwareCommand
{
    private $deploy_api_url = 'https://api.rollbar.com/api/1/deploy/';
    private $timeout = 3;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('staffim_rollbar:report:deploy')
            ->setDescription('Tracking Deploys to the rollbar (see more https://rollbar.com/docs/deploys_other)')
            ->addArgument('revision', InputArgument::REQUIRED, 'Revision number/sha being deployed. If using git, use the full sha')
            ->addOption('comment', 'c', InputOption::VALUE_OPTIONAL, 'Deploy comment (e.g. what is being deployed)')
            ->addOption('local_username', null, InputOption::VALUE_OPTIONAL, 'User who deployed')
            ->addOption('rollbar_username', null, InputOption::VALUE_OPTIONAL, 'Rollbar username of the user who deployed');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $data = [
                'access_token' => $this->getContainer()->getParameter('staffim_rollbar.rollbar.access_token'),
                'environment' => $this->getContainer()->getParameter('staffim_rollbar.rollbar.environment'),
                'revision' => $input->getArgument('revision'),
                'comment' => $input->getOption('comment'),
                'local_username' => $input->getOption('local_username'),
                'rollbar_username' => $input->getOption('rollbar_username'),
            ];
            $result = $this->make_api_call($data);
        } catch (\RuntimeException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return 1;
        }

        $output->writeln($result);
        return 0;
    }

    private function make_api_call($post_data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->deploy_api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Rollbar-Access-Token: ' . $post_data['access_token']));

        $result = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status_code != 200) {
            $result = sprintf(
                '<error>Got unexpected status code (%s) from Rollbar API. Output: %s</error>',
                $status_code,
                $result
            );
        } else {
            $result = sprintf('<info>Success</info>', $result);
        }

        return $result;
    }
}
