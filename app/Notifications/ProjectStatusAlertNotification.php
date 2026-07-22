<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\MetricsSnapshot;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectStatusAlertNotification extends Notification
{
    use Queueable;

    protected $project;
    protected $snapshot;
    protected $oldStatus;
    protected $isRecovery;

    /**
     * Create a new notification instance.
     */
    public function __construct(Project $project, MetricsSnapshot $snapshot, string $oldStatus, bool $isRecovery = false)
    {
        $this->project = $project;
        $this->snapshot = $snapshot;
        $this->oldStatus = $oldStatus;
        $this->isRecovery = $isRecovery;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $statusEmoji = [
            'healthy' => '🟢',
            'warning' => '🟡',
            'critical' => '🔴',
            'unreachable' => '❌',
        ];

        $emoji = $statusEmoji[$this->snapshot->health_status] ?? 'ℹ️';

        if ($this->isRecovery) {
            $subject = "✅ RECOVERED: {$this->project->name} is Healthy";
            $greeting = "Project {$this->project->name} has recovered!";
        } else {
            $subject = "🚨 ALERT: {$this->project->name} is {$this->snapshot->health_status}";
            $greeting = "Project {$this->project->name} health status has degraded!";
        }

        $mailMessage = (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line("Project status changed from **{$this->oldStatus}** to **{$this->snapshot->health_status}** {$emoji}.")
            ->line("--- Details ---")
            ->line("• **HTTP Status**: " . ($this->snapshot->http_status ?: 'N/A'))
            ->line("• **Requests Count**: {$this->snapshot->requests_count}")
            ->line("• **Errors Count**: {$this->snapshot->errors_count}")
            ->line("• **Error Rate**: " . number_format($this->snapshot->error_rate, 2) . "%")
            ->line("• **Response Time**: " . ($this->snapshot->avg_response_time_ms ?: 'N/A') . " ms")
            ->line("• **Checked At**: {$this->snapshot->checked_at->toDateTimeString()}");

        if ($this->snapshot->error_message) {
            $mailMessage->line("• **Error Message**: {$this->snapshot->error_message}");
        }

        if ($this->project->live_url) {
            $mailMessage->action('View Live Site', $this->project->live_url);
        }

        return $mailMessage;
    }
}
