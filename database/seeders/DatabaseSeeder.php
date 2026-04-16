<?php

namespace Database\Seeders;

use App\Models\Investment;
use App\Models\InvestmentTrade;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            [
                'balance' => 325.75,
                'name' => 'Andrea Garcia',
                'username' => 'andrea',
                'password' => 'andrea123',
                'phone_number' => '600100101',
            ],
            [
                'balance' => 480.20,
                'name' => 'Carlos Martinez',
                'username' => 'carlos',
                'password' => 'carlos123',
                'phone_number' => '600100102',
            ],
            [
                'balance' => 615.90,
                'name' => 'Lucia Fernandez',
                'username' => 'lucia',
                'password' => 'lucia123',
                'phone_number' => '600100103',
            ],
            [
                'balance' => 740.40,
                'name' => 'David Romero',
                'username' => 'david',
                'password' => 'david123',
                'phone_number' => '600100104',
            ],
            [
                'balance' => 890.10,
                'name' => 'Paula Navarro',
                'username' => 'paula',
                'password' => 'paula123',
                'phone_number' => '600100105',
            ],
            [
                'balance' => 1050.55,
                'name' => 'Miguel Torres',
                'username' => 'miguel',
                'password' => 'miguel123',
                'phone_number' => '600100106',
            ],
        ];

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                ['username' => $user['username']],
                $user
            );
        }

        $stocks = [
            ['ticker' => 'AAPL', 'name' => 'Apple Inc.', 'current_price' => 198.40],
            ['ticker' => 'MSFT', 'name' => 'Microsoft Corp.', 'current_price' => 420.15],
            ['ticker' => 'NVDA', 'name' => 'NVIDIA Corp.', 'current_price' => 874.90],
            ['ticker' => 'TSLA', 'name' => 'Tesla Inc.', 'current_price' => 173.60],
        ];

        foreach ($stocks as $stock) {
            Stock::query()->updateOrCreate(
                ['ticker' => $stock['ticker']],
                $stock
            );
        }

        $investmentSeeds = [
            ['username' => 'andrea', 'ticker' => 'AAPL', 'quantity' => 3, 'buy_price' => 182.30],
            ['username' => 'carlos', 'ticker' => 'MSFT', 'quantity' => 2, 'buy_price' => 398.00],
            ['username' => 'carlos', 'ticker' => 'AAPL', 'quantity' => 4, 'buy_price' => 176.80],
            ['username' => 'carlos', 'ticker' => 'TSLA', 'quantity' => 6, 'buy_price' => 159.40],
            ['username' => 'carlos', 'ticker' => 'NVDA', 'quantity' => 1, 'buy_price' => 801.20],
            ['username' => 'lucia', 'ticker' => 'TSLA', 'quantity' => 5, 'buy_price' => 165.20],
            ['username' => 'paula', 'ticker' => 'NVDA', 'quantity' => 1, 'buy_price' => 812.45],
        ];

        foreach ($investmentSeeds as $seed) {
            $user = User::query()->where('username', $seed['username'])->first();
            $stock = Stock::query()->where('ticker', $seed['ticker'])->first();

            if (!$user || !$stock) {
                continue;
            }

            $investment = Investment::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'stock_id' => $stock->id,
                    'is_sold' => false,
                ],
                [
                    'quantity' => $seed['quantity'],
                    'buy_price' => $seed['buy_price'],
                    'sell_price' => null,
                ]
            );

            InvestmentTrade::query()->updateOrCreate(
                [
                    'investment_id' => $investment->id,
                    'type' => 'buy',
                ],
                [
                    'date' => now('UTC'),
                ]
            );
        }
    }
}
