<?php
declare(strict_types=1);

namespace App\DTOs;

final class ProjectDTO
{
    public static function fromRow(array $row): array
    {
        // Mirrors existing API shape (keeps file_info as array)
        return $row;
    }
} 