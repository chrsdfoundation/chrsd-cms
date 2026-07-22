<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Crm extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'CRM';

    protected static ?string $slug = 'crm';

    protected static ?string $clusterBreadcrumb = 'CRM';

    protected static ?int $navigationSort = 15;

    protected static ?string $navigationLabel = 'CRM';
}
