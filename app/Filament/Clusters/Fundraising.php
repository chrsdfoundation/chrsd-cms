<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Fundraising extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Fundraising';

    protected static ?string $slug = 'fundraising';

    protected static ?string $clusterBreadcrumb = 'Fundraising';

    protected static ?int $navigationSort = 18;

    protected static ?string $navigationLabel = 'Fundraising';
}
