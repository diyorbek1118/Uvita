<?php

declare(strict_types=1);

namespace Modules\Listing\Domain\Entities;

use Modules\Listing\Domain\Enums\ListingStatus;

final class Listing
{
    public private(set) string $title;
    public private(set) ?string $description;
    public private(set) int $price;
    public private(set) float $quantity;
    public private(set) string $unit;
    public private(set) ?array $images;
    public private(set) ?string $video;
    public private(set) ?string $region;
    public private(set) ?string $district;
    public private(set) ?string $address;
    public private(set) ?float $lat;
    public private(set) ?float $lng;
    public private(set) ?array $details;
    public private(set) ?array $contacts;
    public private(set) ?\DateTimeImmutable $expiresAt;
    public private(set) ListingStatus $status;
    public private(set) ?string $rejectionReason;

    public function __construct(
        public readonly ?int $id,
        public readonly int  $sellerId,
        public readonly ?int $categoryId,
        string               $title,
        ?string              $description,
        int                  $price,
        float                $quantity,
        string               $unit,
        ?array               $images,
        ?string              $video,
        ?string              $region,
        ?string              $district,
        ?string              $address,
        ?float               $lat,
        ?float               $lng,
        ?array               $details,
        ?array               $contacts,
        ?\DateTimeImmutable  $expiresAt       = null,
        ListingStatus        $status          = ListingStatus::PENDING,
        ?string              $rejectionReason = null,
    ) {
        $this->title           = $title;
        $this->description     = $description;
        $this->price           = $price;
        $this->quantity        = $quantity;
        $this->unit            = $unit;
        $this->images          = $images;
        $this->video           = $video;
        $this->region          = $region;
        $this->district        = $district;
        $this->address         = $address;
        $this->lat             = $lat !== null ? (float) $lat : null;
        $this->lng             = $lng !== null ? (float) $lng : null;
        $this->details         = $details;
        $this->contacts        = $contacts;
        $this->expiresAt       = $expiresAt;
        $this->status          = $status;
        $this->rejectionReason = $rejectionReason;
    }

    public function update(
        ?int     $categoryId,
        string   $title,
        ?string  $description,
        int      $price,
        float    $quantity,
        string   $unit,
        ?array   $images,
        ?string  $video,
        ?string  $region,
        ?string  $district,
        ?string  $address,
        ?float   $lat,
        ?float   $lng,
        ?array   $details,
        ?array   $contacts,
    ): void {
        $this->categoryId = $categoryId;
        $this->title      = $title;
        $this->description = $description;
        $this->price      = $price;
        $this->quantity   = $quantity;
        $this->unit       = $unit;
        $this->images     = $images;
        $this->video      = $video;
        $this->region     = $region;
        $this->district   = $district;
        $this->address    = $address;
        $this->lat        = $lat !== null ? (float) $lat : null;
        $this->lng        = $lng !== null ? (float) $lng : null;
        $this->details    = $details;
        $this->contacts   = $contacts;
        // Hozircha moderatsiyasiz — tahrirlangach ham e'lon faol qoladi (keyinroq admin tasdiqlash qo'shiladi)
        $this->status          = ListingStatus::ACTIVE;
        $this->rejectionReason = null;
    }

    public function approve(): void
    {
        $this->status = ListingStatus::ACTIVE;
    }

    public function reject(string $reason): void
    {
        $this->status          = ListingStatus::REJECTED;
        $this->rejectionReason = $reason;
    }

    public function markSold(): void
    {
        $this->status = ListingStatus::SOLD;
    }
}
