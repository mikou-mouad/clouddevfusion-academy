<?php

namespace App\Data;

final class IntranetData
{
    public static function admins(): array
    {
        return [
            [
                'id' => 99,
                'firstName' => 'Admin',
                'lastName' => 'CloudDev',
                'email' => 'admin@clouddev.local',
                'password' => 'admin123',
            ],
        ];
    }

    public static function trainers(): array
    {
        return [];
    }

    public static function students(): array
    {
        return [];
    }

    public static function formations(): array
    {
        return [];
    }

    public static function enrollments(): array
    {
        return [];
    }

    public static function classGroups(): array
    {
        return [];
    }

    public static function classEnrollments(): array
    {
        return [];
    }

    public static function attendanceRecords(): array
    {
        return [];
    }
}
