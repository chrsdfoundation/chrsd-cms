<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Documents extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-folder-open';

    protected static ?string $navigationGroup = 'Documents';

    protected static ?string $slug = 'documents';

    protected static ?string $clusterBreadcrumb = 'Documents';

    protected static ?int $navigationSort = 20;
}
