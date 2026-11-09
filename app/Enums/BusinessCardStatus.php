<?php

namespace App\Enums;

/** Workflow state of an executive business card. */
enum BusinessCardStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Approved = 'approved';
    case SentToPress = 'sent_to_press';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::InReview => 'In review',
            self::Approved => 'Approved',
            self::SentToPress => 'Sent to press',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::InReview => 'warning',
            self::Approved => 'success',
            self::SentToPress => 'info',
        };
    }
}
