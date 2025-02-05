<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\WeeklyTimeReportNotification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class WeeklyReportsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $endTime = Carbon::now()->endOfDay();
        $startTime = Carbon::now()->subDays(7)->startOfDay();

        User::query()
            ->leftJoin('time_trackings', 'users.id', 'time_trackings.user_id')
            ->whereBetween('time_trackings.start_time', [$startTime, $endTime])
            ->whereNotNull('time_trackings.end_time')
            ->selectRaw('users.id, users.email, SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(time_trackings.end_time , time_trackings.start_time)))) AS totalTimeInCurrentWeek')
            ->groupBy('users.id')
            ->get()->each(function (User $user) {
                $user->notify(new WeeklyTimeReportNotification($user->totalTimeInCurrentWeek));
            });
    }
}
