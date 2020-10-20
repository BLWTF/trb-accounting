<?php

namespace Modules\DoubleEntry\Tests\Feature;

use Tests\Feature\FeatureTestCase;

class SettingsTest extends FeatureTestCase
{
    public function testItShouldSeeDoubleEntryInSettingsListPage()
    {
        $this->loginAs()
            ->get(route('settings.index'))
            ->assertStatus(200)
            ->assertSeeText(trans('double-entry::general.description'));
    }

    public function testItShouldSeeDoubleEntrySettingsUpdatePage()
    {
        $this->loginAs()
            ->get(route('double-entry.settings.edit'))
            ->assertStatus(200);
    }

    public function testItShouldUpdateDoubleEntrySettings()
    {
        $this->loginAs()
            ->patch(route('double-entry.settings.update'), $this->getRequest())
            ->assertStatus(200);

        $this->assertFlashLevel('success');
    }

    public function getRequest()
    {
        return [
            'accounts_receivable' => '120',
            'accounts_payable' => '200',
            'accounts_sales' => '400',
            'accounts_expenses' => '628',
            'types_bank'=>'6',
            'types_tax' => '17',
        ];
    }
}
