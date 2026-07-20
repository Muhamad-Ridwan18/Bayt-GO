<?php

namespace App\ViewModels\Layanan;

use App\Models\MuthowifProfile;
use App\Models\MuthowifService;
use Carbon\Carbon;
use Illuminate\Support\ViewErrorBag;

final class BookingPanelData
{
    /**
     * @param  array{can_submit: bool, reason: string|null, start: ?string, end: ?string}  $intent
     * @param  array{min: int, max: int}  $gBounds
     * @param  array{min: int, max: int}  $pBounds
     * @param  list<string>  $oldAddOnIds
     * @param  array<string, mixed>  $bookingFormConfig
     */
    public function __construct(
        public readonly array $intent,
        public readonly ?string $rangeLabel,
        public readonly ?string $tripRangeDisplay,
        public readonly array $gBounds,
        public readonly array $pBounds,
        public readonly string $selectedService,
        public readonly int $defaultPilgrim,
        public readonly bool $oldWithSameHotel,
        public readonly bool $oldWithTransport,
        public readonly array $oldAddOnIds,
        public readonly int $initialBookingStep,
        public readonly string $profileUrl,
        public readonly ?string $tripRangeLabel,
        public readonly string $changeDatesUrl,
        public readonly bool $canSubmit,
        public readonly array $bookingFormConfig,
    ) {}

    /**
     * @param  array{can_submit: bool, reason: string|null, start: ?string, end: ?string}  $intent
     */
    public static function make(
        MuthowifProfile $profile,
        ?MuthowifService $group,
        ?MuthowifService $private,
        array $intent,
        string $startDate,
        string $endDate,
        ?string $profileUrl = null,
        ?string $searchRangeLabel = null,
        ?string $indexedUrl = null,
        bool $canSubmit = false,
    ): self {
        $rangeLabel = null;
        $tripRangeDisplay = null;
        if ($intent['start'] && $intent['end']) {
            try {
                $rangeLabel = Carbon::parse($intent['start'])->format('d/m/Y').' – '.Carbon::parse($intent['end'])->format('d/m/Y');
                $tripRangeDisplay = Carbon::parse($intent['start'])->translatedFormat('d M Y').' – '.Carbon::parse($intent['end'])->translatedFormat('d M Y');
            } catch (\Throwable) {
                $rangeLabel = null;
                $tripRangeDisplay = null;
            }
        }

        $gBounds = self::pilgrimBounds($group);
        $pBounds = self::pilgrimBounds($private);

        $hintSvcRaw = request()->query('service_type');
        $hintSvc = is_string($hintSvcRaw) ? $hintSvcRaw : '';
        $serviceFromQuery = in_array($hintSvc, ['group', 'private'], true) ? $hintSvc : null;
        if ($serviceFromQuery === 'group' && $group) {
            $defaultService = 'group';
        } elseif ($serviceFromQuery === 'private' && $private) {
            $defaultService = 'private';
        } else {
            $defaultService = $group ? 'group' : 'private';
        }
        $selectedService = (string) old('service_type', $defaultService);

        $boundsForSelected = $selectedService === 'private' ? $pBounds : $gBounds;
        $pilgrimRaw = request()->query('pilgrim_count');
        $pilgrimFromQuery = is_numeric($pilgrimRaw) ? (int) $pilgrimRaw : null;
        if ($pilgrimFromQuery !== null) {
            $pilgrimFromQuery = max($boundsForSelected['min'], min($boundsForSelected['max'], $pilgrimFromQuery));
        }
        $defaultPilgrim = (int) old('pilgrim_count', $pilgrimFromQuery ?? (($selectedService === 'private') ? $pBounds['min'] : $gBounds['min']));

        $oldWithSameHotel = (bool) old('with_same_hotel', false);
        $oldWithTransport = (bool) old('with_transport', false);
        $oldAddOnIds = collect(old('add_on_ids', []))->map(fn ($id) => (string) $id)->all();

        $docErrorFields = ['ticket_outbound', 'ticket_return', 'passport', 'itinerary', 'visa'];
        $errors = app('view')->shared('errors') ?? new ViewErrorBag;
        $initialBookingStep = $errors->hasAny($docErrorFields) ? 2 : 1;

        $profileUrl ??= route('layanan.show', $profile);
        $tripRangeLabel = $rangeLabel ?? $searchRangeLabel;
        $changeDatesUrl = $indexedUrl ?? route('layanan.index', array_filter([
            'start_date' => $startDate !== '' ? $startDate : null,
            'end_date' => $endDate !== '' ? $endDate : null,
        ], fn ($v) => filled($v)));

        $bookingFormConfig = [
            'initialStep' => $initialBookingStep,
            'serviceType' => $selectedService,
            'pilgrimCount' => $defaultPilgrim,
            'bounds' => [
                'group' => ['min' => (int) $gBounds['min'], 'max' => (int) $gBounds['max']],
                'private' => ['min' => (int) $pBounds['min'], 'max' => (int) $pBounds['max']],
            ],
            'labels' => [
                'group' => __('marketplace.panel.group_label'),
                'private' => __('marketplace.panel.private_label'),
                'people' => __('common.people'),
                'docsCount' => __('marketplace.panel.review_docs_count'),
            ],
            'tempUploadUrl' => route('bookings.documents.temp'),
            'docs' => [
                'ticket_outbound' => self::docFieldState('ticket_outbound'),
                'ticket_return' => self::docFieldState('ticket_return'),
                'passport' => self::docFieldState('passport'),
                'itinerary' => self::docFieldState('itinerary'),
                'visa' => self::docFieldState('visa'),
            ],
            'docLabels' => [
                'docUploading' => __('marketplace.panel.doc_uploading'),
                'docUploaded' => __('marketplace.panel.doc_uploaded'),
            ],
            'messages' => [
                'serviceRequired' => __('marketplace.panel.step_service_required'),
                'uploadPending' => __('marketplace.panel.step_upload_pending'),
                'docRequired' => __('marketplace.panel.step_doc_required'),
                'docUploadFailed' => __('marketplace.panel.doc_upload_failed'),
                'docUploadTimeout' => __('marketplace.panel.doc_upload_timeout'),
            ],
        ];

        return new self(
            intent: $intent,
            rangeLabel: $rangeLabel,
            tripRangeDisplay: $tripRangeDisplay,
            gBounds: $gBounds,
            pBounds: $pBounds,
            selectedService: $selectedService,
            defaultPilgrim: $defaultPilgrim,
            oldWithSameHotel: $oldWithSameHotel,
            oldWithTransport: $oldWithTransport,
            oldAddOnIds: $oldAddOnIds,
            initialBookingStep: $initialBookingStep,
            profileUrl: $profileUrl,
            tripRangeLabel: $tripRangeLabel,
            changeDatesUrl: $changeDatesUrl,
            canSubmit: $canSubmit,
            bookingFormConfig: $bookingFormConfig,
        );
    }

    /** @return array{min: int, max: int} */
    private static function pilgrimBounds(?MuthowifService $service): array
    {
        if (! $service) {
            return ['min' => 1, 'max' => 50];
        }
        $min = $service->min_pilgrims !== null ? (int) $service->min_pilgrims : 1;
        $max = $service->max_pilgrims !== null ? (int) $service->max_pilgrims : 50;
        $min = max(1, $min);
        if ($max < $min) {
            $max = $min;
        }

        return ['min' => $min, 'max' => $max];
    }

    /** @return array{path: string, name: string, uploading: bool, error: string} */
    private static function docFieldState(string $field): array
    {
        $path = old("temp_{$field}_path", session("temp_{$field}_path"));
        $name = old("temp_{$field}_name", session("temp_{$field}_name"));

        return [
            'path' => is_string($path) && $path !== '' ? $path : '',
            'name' => is_string($name) && $name !== '' ? $name : '',
            'uploading' => false,
            'error' => '',
        ];
    }
}
