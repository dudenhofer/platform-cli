<?php

namespace CommerceGuys\Platform\Cli\Command;

use CommerceGuys\Guzzle\Plugin\Oauth2\Oauth2Plugin;
use CommerceGuys\Guzzle\Plugin\Oauth2\GrantType\PasswordCredentials;
use CommerceGuys\Guzzle\Plugin\Oauth2\GrantType\RefreshToken;
use Guzzle\Service\Client;
use Guzzle\Service\Description\ServiceDescription;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;

class EnvironmentCommand extends PlatformCommand
{

    protected $project;
    protected $environment;
    protected $environments;

    protected function validateInput(InputInterface $input, OutputInterface $output)
    {
        // Allow the project to be specified explicitly via --project.
        $projectId = $input->getOption('project');
        if (!empty($projectId)) {
            $projects = $this->getProjects();
            if (!isset($projects[$projectId])) {
                $output->writeln("<error>Specified project not found.</error>");
                return;
            }
            $this->project = $projects[$projectId];
        }
        else {
            // Autodetect the project if the user is in a project directory.
            $this->project = $this->getCurrentProject();
            if (!$this->project) {
                $output->writeln("<error>Could not determine the current project.</error>");
                $output->writeln("<error>Specify it manually using --project or go to a project directory.</error>");
                return;
            }
        }

        $options = $this->getDefinition()->getOptions();
        if (isset($options['environment'])) {
            // Allow the environment to be specified explicitly via --environment.
            $environmentId = $input->getOption('environment');
            if (!empty($environmentId)) {
                $environments = $this->getEnvironments($this->project);
                if (!isset($environments[$environmentId])) {
                    $output->writeln("<error>Specified environment not found.</error>");
                    return;
                }
                $this->environment = $environments[$environmentId];
            }
            else {
                // Autodetect the environment if the user is in a project directory.
                $this->environment = $this->getCurrentEnvironment($this->project);
                if (!$this->environment) {
                    $output->writeln("<error>Could not determine the current environment.</error>");
                    $output->writeln("<error>Specify it manually using --environment or go to a project directory.</error>");
                    return;
                }
            }
        }

        return TRUE;
    }

    /**
     * Get the current environment if the user is in a project directory.
     *
     * @param array $project The current project.
     *
     * @return array|null The current environment
     */
    protected function getCurrentEnvironment($project)
    {
        $environment = null;
        $projectRoot = $this->getProjectRoot();
        if ($projectRoot) {
            $repositoryDir = $projectRoot . '/repository';
            $remote = shell_exec("cd $repositoryDir && git rev-parse --abbrev-ref --symbolic-full-name @{u}");
            if (strpos($remote, '/') !== FALSE) {
                $remoteParts = explode('/', trim($remote));
                $potentialEnvironmentId = $remoteParts[1];
                $environments = $this->getEnvironments($project);
                if (isset($environments[$potentialEnvironmentId])) {
                    $environment = $environments[$potentialEnvironmentId];
                }
            }
        }

        return $environment;
    }
}
