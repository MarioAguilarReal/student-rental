<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Institution;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use Illuminate\Support\Str;

/**
 * HU-03.01 · RN-02: email domains identify the institution, so a domain can belong to only one.
 */
class SaveInstitution
{
    /**
     * @param  list<string>  $emailDomains
     */
    public function handle(
        ?Institution $institution,
        string $name,
        ?string $acronym,
        array $emailDomains,
        bool $isActive,
    ): Institution {
        $domains = array_values(collect($emailDomains)
            ->map(fn (string $domain): string => Str::of($domain)->lower()->trim()->ltrim('@')->toString())
            ->filter()
            ->unique()
            ->all());

        $this->ensureDomainsAreNotTaken($domains, $institution);

        $institution ??= new Institution;
        $institution->fill([
            'name' => $name,
            'acronym' => $acronym,
            'email_domains' => $domains,
            'is_active' => $isActive,
        ])->save();

        return $institution;
    }

    /**
     * @param  list<string>  $domains
     */
    private function ensureDomainsAreNotTaken(array $domains, ?Institution $current): void
    {
        $owner = Institution::query()
            ->when($current, fn ($query) => $query->whereKeyNot($current->id))
            ->get()
            ->first(fn (Institution $other): bool => array_intersect($domains, $other->email_domains) !== []);

        if ($owner) {
            throw new BusinessRuleViolation(
                'RN-02',
                __('The domain :domain already belongs to :institution.', [
                    'domain' => implode(', ', array_intersect($domains, $owner->email_domains)),
                    'institution' => $owner->name,
                ]),
                'email_domains',
            );
        }
    }
}
