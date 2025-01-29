<?php

namespace Database\Seeders;

use App\Models\CardCategory;
use App\Models\Contries;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

        $this->call([
            plan::class,
            KycLevelSeeder::class,
            teller_options::class,
            auto_renewals::class,
            esusu_types::class,
            UserTypeSeeder::class,
            instant_settlement_statuses::class,
            back_date_statuses::class,
            charge_customer_statuses::class,
            RolesAndPermissionsSeeder::class,
            PermissionTableSeeder::class,
            VerificationStatusSeeder::class,
            statusSeeder::class,
            BanksTableSeeder::class,
            ContriesSeeder::class,
            CreditDebitTypeSeeder::class,
            currencies::class,
            TransactionRemarksSeeder::class,
            TransactionTypeSeeder::class,
            company_typeseeder::class,
            frequenciesSeeder::class,
            CompanySeeder::class,
            CompanyWalletSeeder::class,
            UserCardStatusSeeder::class,
            CreateUserSeeder::class,
            StaffWalletSeeder::class,
            StaffRoleTableSeeder::class,
            CustomRoleAsPermissionSeeder::class,
            RoleHasPermissionTableSeeder::class,
            StaffPermissionTableSeeder::class,
            CardCategoryTableSeeder::class,
            CardTypeTableSeeder::class,
            SavingsProductSeeder::class,
            RequestMethodSeed::class,
            SMSSettingsSeeder::class,
            Company_status::class,
            CustomerPermissionSeeder::class,
            UssdStatusSeeder::class,
            // CarouselImageSeeder::class,
            WbaCompanyChargeSeeder::class,
            CompanySettingsSeeder::class,
            EmploymentTypeSeeder::class,
            RestrictionLogTypeSeeder::class,
            loan_period::class,
            CustomerCreditSeeder::class,
            loanstatus::class,
        ]);
    }
}
