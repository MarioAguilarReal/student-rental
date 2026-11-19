<?php

namespace App\Modules\Search;

use App\Modules\Shared\Providers\ModuleServiceProvider;

/**
 * Read-only module: the public catalog over Properties, Pricing and Catalog. It never writes.
 */
final class SearchServiceProvider extends ModuleServiceProvider {}
