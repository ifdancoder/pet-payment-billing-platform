<?php

namespace App\Infrastructure\Merchant\Adapters\Persistence\Mappers;

use App\Domain\Merchant\Merchant;
use App\Domain\Merchant\ValueObjects\MerchantId;
use App\Domain\Merchant\ValueObjects\MerchantName;
use App\Domain\Merchant\ValueObjects\MerchantStatus;
use App\Infrastructure\Merchant\Adapters\Persistence\Models\MerchantModel;

final class MerchantMapper
{
    public function toDomain(MerchantModel $model): Merchant
    {
        return Merchant::reconstitute(
            MerchantId::fromString($model->id),
            MerchantName::fromString($model->name),
            MerchantStatus::from($model->status),
        );
    }

    public function toModel(Merchant $merchant, ?MerchantModel $model = null): MerchantModel
    {
        $model ??= new MerchantModel;

        $model->id = $merchant->id()->toString();
        $model->name = $merchant->name()->toString();
        $model->status = $merchant->status()->value;

        return $model;
    }
}
