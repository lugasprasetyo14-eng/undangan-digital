<?php

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Elegant Ivory', 'slug' => 'elegant-ivory', 'description' => 'Ivory hangat dengan aksen gold yang tenang.', 'config' => ['primary' => '#1c1917', 'secondary' => '#fafaf9', 'accent' => '#a16207'], 'sort_order' => 1],
            ['name' => 'Modern Minimal', 'slug' => 'modern-minimal', 'description' => 'Ruang bersih dengan aksen sage yang modern.', 'config' => ['primary' => '#1c1917', 'secondary' => '#ffffff', 'accent' => '#50635a'], 'sort_order' => 2],
            ['name' => 'Romantic Blush', 'slug' => 'romantic-blush', 'description' => 'Palet blush lembut untuk cerita yang romantis.', 'config' => ['primary' => '#3f2929', 'secondary' => '#fff7f5', 'accent' => '#b76e79'], 'sort_order' => 3],
        ] as $theme) {
            Theme::updateOrCreate(['slug' => $theme['slug']], $theme + ['is_active' => true, 'is_premium' => false]);
        }
    }
}