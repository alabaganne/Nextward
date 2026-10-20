<?php

namespace Tests\Feature;

use App\Support\Countries;
use Tests\TestCase;

class CountriesTest extends TestCase
{
    public function test_english_and_french_names_come_from_the_saved_list(): void
    {
        $this->assertSame('Tunisia', Countries::name('TN'));
        $this->assertSame('Tunisie', Countries::name('tn', 'fr'));
        $this->assertSame('United States', Countries::name('US'));
        $this->assertSame('Allemagne', Countries::name('DE', 'fr'));
        $this->assertNull(Countries::name(null));
        $this->assertSame('XX', Countries::name('XX'));
    }

    public function test_the_saved_list_names_every_country_in_both_languages(): void
    {
        $names = require resource_path('data/countries.php');

        $this->assertSame(array_keys($names['en']), array_keys($names['fr']));
        $this->assertCount(249, $names['en']);
        foreach ($names as $list) {
            foreach ($list as $code => $name) {
                $this->assertNotSame($code, $name);
            }
        }
    }
}
