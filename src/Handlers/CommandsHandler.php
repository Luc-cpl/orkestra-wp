<?php

namespace OrkestraWP\Handlers;

use Orkestra\Handlers\CommandsHandler as CoreCommandsHandler;
use Orkestra\Interfaces\ConfigurationInterface;
use Orkestra\Interfaces\HandlerInterface;
use Symfony\Component\Console\Application;

use WP_CLI;

class CommandsHandler extends CoreCommandsHandler implements HandlerInterface
{
    public function __construct(
        protected ConfigurationInterface $config,
        protected Application $console,
    ) {
    }

    /**
     * Handle the current request.
     * This should be called to handle the current request from the provider.
     */
    public function handle(): void
    {
        WP_CLI::add_command('maestro', $this->wpCLI(...));
    }

    /**
     * Run Application Maestro handler
     *
     * [<command>]
     * : The command to run. Run "list" to see available commands.
     * 
     * [<subcommand>]
     * : Some optional subcommand to run.
     *
     * [--plugin=<name>]
     * : The plugin to run the command. By default, the command will run in Maestro application root directory.
     *
     * [--theme=<name>]
     * : The theme to run the command. By default, the command will run in Maestro application root directory.
     *
     * [--<field>=<value>]
     * : Any other parameter that will be passed to the command.
     * 
     * [<other>]
     * : Any other parameter that will be passed to the command.
     */
    protected function wpCLI(array $args, array $flags)
    {
        $directories = [
            'plugin' => WP_PLUGIN_DIR,
            'theme' => get_theme_root(),
        ];

        chdir($this->config->get('root'));

        foreach ($directories as $flag => $root) {
            if (isset($flags[$flag])) {
                $dir = $root . '/' . $flags[$flag];
                if (!file_exists($dir)) {
                    WP_CLI::error(ucfirst($flag) . ' not found');
                }

                chdir(__DIR__ . '/../' . $flags[$flag]);

                $flagKey = '--' . $flag . '=';
                foreach ($_SERVER['argv'] as $key => $value) {
                    if (str_starts_with($value, $flagKey)) {
                        unset($_SERVER['argv'][$key]);
                    }
                }
            }
        }

        // Remove the maestro command name from the arguments
        unset($_SERVER['argv'][1]);

        $this->console->run();
    }
}
