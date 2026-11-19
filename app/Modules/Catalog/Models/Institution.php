<?php

namespace App\Modules\Catalog\Models;

use Database\Factories\InstitutionFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string|null $acronym
 * @property list<string> $email_domains
 * @property bool $is_active
 */
#[UseFactory(InstitutionFactory::class)]
class Institution extends Model
{
    /** @use HasFactory<InstitutionFactory> */
    use HasFactory;

    protected $fillable = ['name', 'acronym', 'email_domains', 'is_active'];

    protected function casts(): array
    {
        return [
            'email_domains' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Campus, $this>
     */
    public function campuses(): HasMany
    {
        return $this->hasMany(Campus::class)->orderBy('name');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Active institution whose domain matches the email, including subdomains:
     * alumno@estudiantes.uni.mx matches "uni.mx" (RN-02).
     */
    public static function matchingEmail(string $email): ?self
    {
        $emailDomain = Str::lower(Str::after($email, '@'));

        return self::query()->active()->get()->first(
            fn (self $institution): bool => collect($institution->email_domains)->contains(
                fn (string $domain): bool => $emailDomain === $domain || Str::endsWith($emailDomain, '.'.$domain),
            ),
        );
    }

    public function displayName(): string
    {
        return $this->acronym ? "{$this->name} ({$this->acronym})" : $this->name;
    }
}
