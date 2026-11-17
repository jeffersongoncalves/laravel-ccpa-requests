<?php

namespace JeffersonGoncalves\CcpaRequests\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\Ccpa\Attributes\PersonalData;
use JeffersonGoncalves\Ccpa\Concerns\InteractsWithPersonalData;
use JeffersonGoncalves\Ccpa\Contracts\HasPersonalData;
use JeffersonGoncalves\CcpaRequests\Contracts\ResolvesConsumer;

#[PersonalData('shipping_address')]
class Order extends Model implements HasPersonalData, ResolvesConsumer
{
    use InteractsWithPersonalData;

    protected $guarded = [];

    public $timestamps = false;

    public static function consumerQuery(Model $subject): Builder
    {
        return static::query()->where('user_id', $subject->getKey());
    }
}
