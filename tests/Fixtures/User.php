<?php

namespace JeffersonGoncalves\CcpaRequests\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\Ccpa\Attributes\PersonalData;
use JeffersonGoncalves\Ccpa\Concerns\InteractsWithPersonalData;
use JeffersonGoncalves\Ccpa\Contracts\HasPersonalData;
use JeffersonGoncalves\Ccpa\Enums\AnonymizeStrategy;

#[PersonalData('name', anonymize: AnonymizeStrategy::Mask)]
#[PersonalData('email', anonymize: AnonymizeStrategy::Hash)]
#[PersonalData('tax_id', anonymize: AnonymizeStrategy::Keep)]
class User extends Model implements HasPersonalData
{
    use InteractsWithPersonalData;

    protected $guarded = [];

    public $timestamps = false;
}
