<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Queries;

use Modules\Admin\Domain\ValueObjects\SettingKey;

final readonly class GetSettingByKeyQuery
{
    public function __construct(
        public SettingKey $key,
    ) {}
}
