<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalUsers = User::count();
        $totalOrders = Order::count();
        $pendingOrders = Order::whereIn('status', ['pending', 'processing', 'auto-processing'])->count();
        $completedRevenue = Order::where('status', 'completed')->sum('amount');
        $totalProducts = Product::where('status', 1)->count();

        return [
            Stat::make('Total Revenue', '৳' . number_format($completedRevenue, 2))
                ->description('Completed topup sales')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Total Orders', number_format($totalOrders))
                ->description("{$pendingOrders} pending/processing")
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color($pendingOrders > 0 ? 'warning' : 'info'),

            Stat::make('Active Users', number_format($totalUsers))
                ->description('Registered accounts')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Live Products', number_format($totalProducts))
                ->description('Available in store')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('success'),
        ];
    }
}
