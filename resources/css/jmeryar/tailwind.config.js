import preset from '../../../../filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        '/vendor/tomatophp/filament-simple-theme/resources/views/**/*.blade.php',
    ],
}