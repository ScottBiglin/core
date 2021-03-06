<?php

namespace App\Console\Commands\VisitTransfer;

use App\Console\Commands\Command;
use App\Models\Statistic;
use App\Models\VisitTransfer\Application;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Cache;
use Carbon\Carbon;

class VisitTransferStatistics extends Command
{
    /**
     * The console command signature.
     *
     * The name of the command, along with any expected arguments.
     *
     * @var string
     */
    protected $signature = 'visittransfer:statistics:daily
                            {startPeriod? : The period to start generating statistics from (inclusive), defaulted to yesterday.}
                            {endPeriod? : The period to stop generating statistics on (inclusive), defaulted to yesterday.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate statistics for the given time frame.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $currentPeriod = $this->getStartPeriod();
        $this->log('Start Period: '.$currentPeriod->toDateString());

        while ($currentPeriod->lte($this->getEndPeriod())) {
            $this->log('=========== START OF CYCLE '.$currentPeriod->toDateString().' ===========');

            $this->addTotalApplicationCount($currentPeriod);

            $this->addOpenApplicationsCount($currentPeriod);

            $this->addRejectedApplicationCount($currentPeriod);

            $this->addNewApplicationCount($currentPeriod);

            $this->log('============ END OF CYCLE '.$currentPeriod->toDateString().'  ===========');

            $currentPeriod = $currentPeriod->addDay();
        }

        $this->log('Emptying cache... ');
        Cache::forget('visittransfer::statistics');
        Cache::forget('visittransfer::statistics.graph');
        $this->log('Done!');

        $this->log('Completed');
    }

    /**
     * Add statistics for the total number of applications in the system.
     *
     * @param $currentPeriod
     */
    private function addTotalApplicationCount($currentPeriod)
    {
        $this->log('Counting total applications');

        try {
            $count = Application::where('created_at', '<=', $currentPeriod->toDateString().' 23:59:59')
                                            ->count();

            Statistic::setStatistic($currentPeriod->toDateString(), 'visittransfer::applications.total', $count);

            $this->log('Done.  '.$count.' total applications');
        } catch (\Exception $e) {
            $this->log('Error: '.$e->getMessage());
            Bugsnag::notifyException($e);
        }
    }

    /**
     * Add statistics for the total number of accepted applications in the system.
     *
     * @param $currentPeriod
     */
    private function addOpenApplicationsCount($currentPeriod)
    {
        $this->log('Counting open applications');

        try {
            $count = Application::where('created_at', '<=', $currentPeriod->toDateString().' 23:59:59')
                                ->statusIn(Application::$APPLICATION_IS_CONSIDERED_OPEN)->count();

            Statistic::setStatistic($currentPeriod->toDateString(), 'visittransfer::applications.open', $count);

            $this->log('Done.  '.$count.' open applications');
        } catch (\Exception $e) {
            $this->log('Error: '.$e->getMessage());
            Bugsnag::notifyException($e);
        }
    }

    /**
     * Add statistics for the total number of rejected applications in the system.
     *
     * @param $currentPeriod
     */
    private function addRejectedApplicationCount($currentPeriod)
    {
        $this->log('Counting rejected applications');

        try {
            $count = Application::where('created_at', '<=', $currentPeriod->toDateString().' 23:59:59')
                                ->statusIn(Application::$APPLICATION_IS_CONSIDERED_CLOSED)->count();

            Statistic::setStatistic($currentPeriod->toDateString(), 'visittransfer::applications.closed', $count);

            $this->log('Done. '.$count.' rejected applications');
        } catch (\Exception $e) {
            $this->log('Error: '.$e->getMessage());
            Bugsnag::notifyException($e);
        }
    }

    /**
     * Add statistics for the total number of rejected applications in the system.
     *
     * @param $currentPeriod
     */
    private function addNewApplicationCount($currentPeriod)
    {
        $this->log('Counting new applications for given day');

        try {
            $count = Application::where('created_at', 'LIKE', $currentPeriod->toDateString().' %')->count();

            Statistic::setStatistic($currentPeriod->toDateString(), 'visittransfer::applications.new', $count);

            $this->log('Done. '.$count.' new applications');
        } catch (\Exception $e) {
            $this->log('Error: '.$e->getMessage());
            Bugsnag::notifyException($e);
        }
    }

    /**
     * Get the start period from the arguments passed.
     *
     * This will also validate those arguments.
     *
     * @return Carbon
     */
    private function getStartPeriod()
    {
        try {
            $startPeriod = Carbon::parse($this->argument('startPeriod'), 'UTC');
        } catch (\Exception $e) {
            $this->log('Error: '.$e->getMessage());
            Bugsnag::notifyException($e);
        }

        if ($startPeriod->isFuture()) {
            $startPeriod = Carbon::parse('yesterday', 'UTC');
        }

        return $startPeriod;
    }

    /**
     * Get the end period from the arguments passed.
     *
     * This will also validate those arguments.
     *
     * @return Carbon
     */
    private function getEndPeriod()
    {
        try {
            $endPeriod = Carbon::parse($this->argument('endPeriod'), 'UTC');
        } catch (\Exception $e) {
            $this->log('Error: '.$e->getMessage());
            Bugsnag::notifyException($e);
        }

        if ($endPeriod->isFuture()) {
            $endPeriod = Carbon::parse('yesterday', 'UTC');
        }

        if ($endPeriod->lt($this->getStartPeriod())) {
            $endPeriod = $this->getStartPeriod();
        }

        return $endPeriod;
    }
}
