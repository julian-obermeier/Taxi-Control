<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Permission;
use App\Models\SaasPackage;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['module' => 'dashboard', 'action' => 'view', 'label' => 'Dashboard ansehen'],
            ['module' => 'users', 'action' => 'view', 'label' => 'Benutzer ansehen'],
            ['module' => 'users', 'action' => 'create', 'label' => 'Benutzer erstellen'],
            ['module' => 'users', 'action' => 'update', 'label' => 'Benutzer ändern'],
            ['module' => 'users', 'action' => 'delete', 'label' => 'Benutzer entfernen'],
            ['module' => 'roles', 'action' => 'view', 'label' => 'Rollen ansehen'],
            ['module' => 'roles', 'action' => 'create', 'label' => 'Rollen erstellen'],
            ['module' => 'roles', 'action' => 'update', 'label' => 'Rollen ändern'],
            ['module' => 'roles', 'action' => 'delete', 'label' => 'Rollen löschen'],
            ['module' => 'branding', 'action' => 'view', 'label' => 'Branding ansehen'],
            ['module' => 'branding', 'action' => 'update', 'label' => 'Branding ändern'],
            ['module' => 'settings', 'action' => 'view', 'label' => 'Einstellungen ansehen'],
            ['module' => 'settings', 'action' => 'update', 'label' => 'Einstellungen ändern'],
            ['module' => 'billing', 'action' => 'view', 'label' => 'Vertrag ansehen'],
            ['module' => 'api', 'action' => 'view', 'label' => 'API-Zugänge ansehen'],
            ['module' => 'api', 'action' => 'create', 'label' => 'API-Zugänge erstellen'],
            ['module' => 'api', 'action' => 'delete', 'label' => 'API-Zugänge löschen'],
            ['module' => 'webhooks', 'action' => 'view', 'label' => 'Webhooks ansehen'],
            ['module' => 'webhooks', 'action' => 'create', 'label' => 'Webhooks erstellen'],
            ['module' => 'webhooks', 'action' => 'update', 'label' => 'Webhooks ändern'],
            ['module' => 'webhooks', 'action' => 'delete', 'label' => 'Webhooks löschen'],
            ['module' => 'audit', 'action' => 'view', 'label' => 'Audit-Protokoll ansehen'],
            ['module' => 'privacy', 'action' => 'view', 'label' => 'Datenschutzvorgänge ansehen'],
            ['module' => 'privacy', 'action' => 'update', 'label' => 'Datenschutzvorgänge bearbeiten'],
            ['module' => 'onboarding', 'action' => 'view', 'label' => 'Onboarding ansehen'],
            ['module' => 'onboarding', 'action' => 'update', 'label' => 'Onboarding bearbeiten'],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                ['key' => $permission['module'].'.'.$permission['action']],
                [...$permission, 'data_scope' => 'tenant']
            );
        }

        $features = [
            'dispatch' => 'Disposition',
            'driver_pwa' => 'Fahrer-PWA',
            'crm' => 'CRM',
            'billing' => 'Abrechnung',
            'fleet' => 'Fuhrpark',
            'medical_trips' => 'Krankenfahrten',
            'threecx' => '3CX-Integration',
            'ptt' => 'PTT/Funk',
            'api' => 'REST-API',
            'white_label' => 'White Label',
        ];

        foreach ($features as $key => $name) {
            Feature::query()->updateOrCreate(['key' => $key], ['name' => $name, 'is_active' => true]);
        }

        $packageDefinitions = [
            'basic' => ['name' => 'Basic', 'limits' => ['users' => 10, 'vehicles' => 10]],
            'professional' => ['name' => 'Professional', 'limits' => ['users' => 35, 'vehicles' => 35]],
            'premium' => ['name' => 'Premium', 'limits' => ['users' => 100, 'vehicles' => 100]],
            'enterprise' => ['name' => 'Enterprise', 'limits' => ['users' => null, 'vehicles' => null]],
        ];

        foreach ($packageDefinitions as $slug => $definition) {
            SaasPackage::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $definition['name'], 'limits' => $definition['limits'], 'is_active' => true]
            );
        }
    }
}
