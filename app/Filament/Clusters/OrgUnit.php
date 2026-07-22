<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class OrgUnit extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Organization';

    protected static ?string $slug = 'org';

    protected static ?string $clusterBreadcrumb = 'Organization';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Organization';
}
