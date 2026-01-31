<?php

namespace Database\Seeders;

use App\Models\AccountType;
use Illuminate\Database\Seeder;

class AccountTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => AccountType::ASSET,
                'normal_balance' => AccountType::DEBIT,
                'sort_order' => 1,
            ],
            [
                'name' => AccountType::LIABILITY,
                'normal_balance' => AccountType::CREDIT,
                'sort_order' => 2,
            ],
            [
                'name' => AccountType::EQUITY,
                'normal_balance' => AccountType::CREDIT,
                'sort_order' => 3,
            ],
            [
                'name' => AccountType::INCOME,
                'normal_balance' => AccountType::CREDIT,
                'sort_order' => 4,
            ],
            [
                'name' => AccountType::EXPENSE,
                'normal_balance' => AccountType::DEBIT,
                'sort_order' => 5,
            ],
        ];

        foreach ($types as $type) {
            AccountType::updateOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}
