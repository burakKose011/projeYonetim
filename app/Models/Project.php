<?php
declare(strict_types=1);

namespace App\Models;

final class Project
{
    public function __construct(
        public ?int $id,
        public string $title,
        public ?string $subject,
        public ?string $description,
        public ?string $keywords,
        public ?string $project_manager,
        public ?string $responsible_person,
        public ?string $start_date,
        public ?string $end_date,
        public ?int $duration_days,
        public string $status,
        /** @var array<string, mixed>|null */
        public ?array $file_info,
        public int $created_by,
        public ?string $created_at,
        public ?string $updated_at,
        public ?string $approved_at,
        public ?int $approved_by,
        public ?string $rejected_at,
        public ?int $rejected_by,
        public ?string $rejection_reason,
    ) {}
} 