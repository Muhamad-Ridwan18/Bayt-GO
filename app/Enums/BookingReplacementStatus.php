<?php

namespace App\Enums;

enum BookingReplacementStatus: string
{
    /** Muthowif diundang admin, belum konfirmasi. */
    case AwaitingMuthowifConfirm = 'awaiting_muthowif_confirm';
    /** Muthowif melamar / sudah konfirmasi — menunggu persetujuan admin. */
    case PendingAdminApproval = 'pending_admin_approval';
    /** Disetujui admin — masuk pool pilihan jamaah (setelah choice dibuka). */
    case ApprovedForCustomer = 'approved_for_customer';
    /** @deprecated Legacy — gunakan ApprovedForCustomer + customer_choice_opened_at */
    case OfferedToCustomer = 'offered_to_customer';
    case AcceptedByCustomer = 'accepted_by_customer';
    case RejectedByCustomer = 'rejected_by_customer';
    case NotSelected = 'not_selected';
    case DeclinedByMuthowif = 'declined_by_muthowif';
    case RejectedByAdmin = 'rejected_by_admin';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('incidents.replacement_status.'.$this->value);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::AcceptedByCustomer,
            self::RejectedByCustomer,
            self::NotSelected,
            self::DeclinedByMuthowif,
            self::RejectedByAdmin,
            self::Cancelled,
        ], true);
    }

    /**
     * @return list<self>
     */
    public static function customerSelectable(): array
    {
        return [self::ApprovedForCustomer, self::OfferedToCustomer];
    }
}
