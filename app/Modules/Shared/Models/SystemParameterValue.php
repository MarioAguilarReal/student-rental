<?php

namespace App\Modules\Shared\Models;

use App\Modules\Shared\Enums\SystemParameter;
use Illuminate\Database\Eloquent\Model;

/**
 * HU-03.05: a value the Super Admin changed. Parameters without a row use their default.
 *
 * @property string $key
 * @property string $value
 * @property int|null $updated_by
 */
class SystemParameterValue extends Model
{
    protected $table = 'system_parameters';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    public function parameter(): ?SystemParameter
    {
        return SystemParameter::tryFrom($this->key);
    }
}
