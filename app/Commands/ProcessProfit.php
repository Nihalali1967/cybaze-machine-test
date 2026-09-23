<?php

namespace App\Commands;

use App\Libraries\ProfitProcessor;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Process profit periods for all due investments.
 *
 * This command automates the daily profit processing that would otherwise
 * require manual intervention via the admin panel. It can be scheduled via
 * cron (Linux) or Task Scheduler (Windows) to run automatically.
 *
 * Usage:
 *   php spark profits:process
 *   php spark profits:process --date=2026-09-30
 *   php spark profits:process --dry-run
 *   php spark profits:process --verbose
 */
class ProcessProfit extends BaseCommand
{
    /**
     * The group the command is lumped under
     * when listing commands.
     */
    protected $group = 'Investment';

    /**
     * The Command's name.
     */
    protected $name = 'profits:process';

    /**
     * The Command's short description.
     */
    protected $description = 'Process profit periods for all due investments.';

    /**
     * The Command's usage.
     */
    protected $usage = 'profits:process [--date=YYYY-MM-DD] [--dry-run] [--verbose]';

    /**
     * The Command's arguments.
     */
    protected $arguments = [];

    /**
     * The Command's options.
     */
    protected $options = [
        '--date' => 'Process profits up to this date (default: today)',
        '--dry-run' => 'Preview what would be processed without writing to database',
        '--verbose' => 'Show detailed output including individual investment details',
    ];

    /**
     * Run the command.
     */
    public function run(array $params)
    {
        // Use CLI::getOption() for options, but also check params array
        $date = CLI::getOption('date');
        if (empty($date)) {
            // Check if date is passed as first positional argument
            $date = $params[0] ?? date('Y-m-d');
        }

        $dryRun = CLI::getOption('dry-run') !== null || in_array('--dry-run', $params, true);
        $verbose = CLI::getOption('verbose') !== null || in_array('--verbose', $params, true);

        // Validate date
        if (strtotime($date) === false) {
            CLI::error('Invalid date format. Use YYYY-MM-DD.');
            return EXIT_ERROR;
        }

        if ($dryRun) {
            CLI::write('DRY RUN MODE - No changes will be written to database', 'yellow');
        }

        CLI::write("Processing profits up to: {$date}", 'cyan');
        CLI::write(str_repeat('-', 50));

        try {
            $processor = new ProfitProcessor();

            if ($dryRun) {
                $report = $processor->preview($date);
            } else {
                $report = $processor->process($date);
            }

            $this->displayReport($report, $verbose);

            // Exit with success if periods were created, warning if nothing to do
            if ($report['periods_created'] > 0) {
                return EXIT_SUCCESS;
            } elseif ($report['periods_skipped'] > 0) {
                CLI::write('All periods already processed for this date.', 'yellow');
                return EXIT_SUCCESS;
            } else {
                CLI::write('No investments due for processing on this date.', 'yellow');
                return EXIT_SUCCESS;
            }
        } catch (\Exception $e) {
            CLI::error('Error processing profits: ' . $e->getMessage());
            return EXIT_ERROR;
        }
    }

    /**
     * Display the processing report.
     *
     * @param array<string, mixed> $report
     */
    protected function displayReport(array $report, bool $verbose): void
    {
        CLI::write("Investments scanned: {$report['investments_scanned']}", 'white');
        CLI::write("Periods created: {$report['periods_created']}", $report['periods_created'] > 0 ? 'green' : 'white');
        CLI::write("Periods skipped: {$report['periods_skipped']}", 'white');
        CLI::write("Total profit amount: ₹" . number_format($report['total_amount'], 2), 'white');
        CLI::write("Run ID: {$report['run_id']}", 'white');
        CLI::write(str_repeat('-', 50));

        if ($verbose && ! empty($report['details'])) {
            foreach ($report['details'] as $detail) {
                CLI::write("\nInvestment #{$detail['investment_id']}: {$detail['customer_name']}", 'cyan');
                CLI::write("  Package: {$detail['package_name']}");
                CLI::write("  Investment: ₹" . number_format($detail['investment_amount'], 2));
                CLI::write("  Withdrawal type: {$detail['withdrawal_type']}");
                CLI::write("  Periods created: {$detail['created']}");
                CLI::write("  Periods skipped: {$detail['skipped']}");
                CLI::write("  Amount: ₹" . number_format($detail['amount'], 2));
                CLI::write("  Next profit date: {$detail['next_profit_date']}");
                CLI::write("  Status: {$detail['status']}");

                if (! empty($detail['periods'])) {
                    CLI::write("  Periods:");
                    foreach ($detail['periods'] as $period) {
                        $state = $period['state'] === 'created' ? 'created' : 'skipped';
                        CLI::write("    - {$period['due_date']} ({$period['period_key']}): ₹" . number_format($period['amount'], 2) . " [{$state}]");
                    }
                }
            }
        }
    }
}
