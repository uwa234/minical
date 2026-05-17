<?php if (!defined('BASEPATH')) {
    exit("No direct script access allowed");
}

/**
 * Class Migrate
 *
 * To use migrations see https://ellislab.com/codeigniter/user-guide/libraries/migration.html
 *
 * tl;dr
 *
 * 1) In the folder /application/migrations create a new file with the filename 002_some_name
 * one version up, then adjust up and own logic
 *
 * 2) In the application/config/migration set the new migration number (2 in this case)
 *
 * 3a) To migrate to latest version
 * Open console - php index.php migrate
 *
 * 3b) To migrate to specific version
 * Open console - php index.php migrate ver 1
 *
 * Web access is limited to the installer (MIGRATION_REQUEST=1) and requires MIGRATION_SECRET when set.
 *
 * @property CI_Migration migration
 */
class Migrate extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();

        $is_cli = $this->input->is_cli_request();
        $is_install_request = isset($_GET['MIGRATION_REQUEST']) && $_GET['MIGRATION_REQUEST'];
        $method = $this->router->fetch_method();

        if (!$is_cli) {
            if (!$is_install_request) {
                show_error('Migrations can only be run from the command line or the installer.', 403);
            }

            if ($method !== 'index') {
                show_error('This migration action is only available via CLI.', 403);
            }

            $migration_secret = getenv('MIGRATION_SECRET');
            if ($migration_secret) {
                $provided = isset($_GET['migration_secret']) ? $_GET['migration_secret'] : '';
                if (!hash_equals($migration_secret, $provided)) {
                    show_error('Invalid migration credentials.', 403);
                }
            }
        }

        $this->load->library('migration');
    }

    public function index()
    {
        try {
            $result = $this->migration->latest();
            if ($result === false) {
                $error = $this->migration->error_string();
                show_error($error ? $error : 'Migration failed.', 500);
            }
            printf("\n\n Migrated successfully \n\n");
        } catch (Throwable $e) {
            show_error($e->getMessage(), 500);
        }

    }

    public function ver($ver)
    {
        try {
            $result = $this->migration->version($ver);
            if ($result === false) {
                $error = $this->migration->error_string();
                show_error($error ? $error : 'Migration failed.', 500);
            }
            printf("\n\n Migrated successfully \n\n");
        } catch (Throwable $e) {
            show_error($e->getMessage(), 500);
        }
    }

    public function generate_migrations()
    {
        $this->load->library('ci_migrations_generator/Sqltoci');

        $this->sqltoci->generate();
    }
}
