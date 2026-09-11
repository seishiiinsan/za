<?php

namespace Tests;

use App\Models\Alter;
use App\Models\System;
use App\Support\Front;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Connecte un système et sélectionne un front actif. */
    protected function actingAsFront(Alter $alter): static
    {
        /** @var System $system */
        $system = $alter->system;

        return $this->actingAs($system)->withSession([Front::SESSION_KEY => $alter->getKey()]);
    }
}
