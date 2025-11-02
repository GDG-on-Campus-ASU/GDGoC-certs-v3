<?php

namespace App\Jobs;

use App\Models\Certificate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class ProcessCertificateRow implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId,
        public array $row,
        public string $issuerName,
        public string $orgName
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Generate unique ID
        $uniqueId = Str::uuid()->toString();

        // Create certificate
        Certificate::create([
            'user_id' => $this->userId,
            'unique_id' => $uniqueId,
            'recipient_name' => $this->row['recipient_name'],
            'recipient_email' => $this->row['recipient_email'] ?? null,
            'state' => $this->row['state'],
            'event_type' => $this->row['event_type'],
            'event_title' => $this->row['event_title'],
            'issue_date' => $this->row['issue_date'],
            'issuer_name' => $this->issuerName,
            'org_name' => $this->orgName,
        ]);
    }
}
