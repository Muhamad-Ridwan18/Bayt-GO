<?php

namespace App\Enums;

enum EmergencyReportStatus: string
{
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Resolved = 'resolved';

    public function label(): string
    {
        return __('emergency.report_status.'.$this->value);
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Submitted, self::UnderReview, self::Verified], true);
    }
}
