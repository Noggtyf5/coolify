<?php

namespace App\Jobs;

use App\Models\Application;
use App\Models\ApplicationPreview;
use App\Notifications\Application\StatusChanged;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ApplicationContainerStatusJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $containerName;

    public function __construct(
        public Application $application,
        public int $pullRequestId = 0)
    {
        $this->containerName = generateApplicationContainerName($application->uuid, $pullRequestId);
    }

    public function uniqueId(): string
    {
        return $this->containerName;
    }

    public function handle(): void
    {
        try {
            $status = getApplicationContainerStatus(application: $this->application);
            if ($this->application->status === 'running' && $status !== 'running') {
                // $this->application->environment->project->team->notify(new StatusChanged($this->application));
            }

            if ($this->pullRequestId !== 0) {
                $preview = ApplicationPreview::findPreviewByApplicationAndPullId($this->application->id, $this->pullRequestId);
                $preview->status = $status;
                $preview->save();
            } else {
                $this->application->status = $status;
                $this->application->save();
            }
        } catch (\Exception $th) {
            ray($th->getMessage());
            throw $th;
        }
    }
}
